<?php
/**
 * Authentication Helper Functions
 * Handles session management, authentication checks, and role validation
 */

session_start();

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('COOKIE_SECURE', false); // Set to true in production with HTTPS
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');

/**
 * Check if user is authenticated
 * @return bool True if user is authenticated, false otherwise
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && isset($_SESSION['authenticated']);
}

/**
 * Check if user has specific role
 * @param string $role The role to check ('archer' or 'manager')
 * @return bool True if user has the role, false otherwise
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if user is archer
 * @return bool True if user is an archer
 */
function isArcher() {
    return hasRole('archer');
}

/**
 * Check if user is manager
 * @return bool True if user is a manager
 */
function isManager() {
    return hasRole('manager');
}

/**
 * Get current user ID
 * @return int|null User ID or null if not authenticated
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user role
 * @return string|null User role or null if not authenticated
 */
function getCurrentUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

/**
 * Get current archer ID (if user is an archer)
 * @return int|null Archer ID or null if not an archer
 */
function getCurrentArcherId() {
    return isset($_SESSION['archer_id']) ? $_SESSION['archer_id'] : null;
}

/**
 * Get current manager ID (if user is a manager)
 * @return int|null Manager ID or null if not a manager
 */
function getCurrentManagerId() {
    return isset($_SESSION['manager_id']) ? $_SESSION['manager_id'] : null;
}

/**
 * Create user session after successful login
 * @param int $userId User ID from database
 * @param string $role User role ('archer' or 'manager')
 * @param int|null $archerId Archer ID (if role is archer)
 * @param int|null $managerId Manager ID (if role is manager)
 * @return bool True if session created successfully
 */
function createSession($userId, $role, $archerId = null, $managerId = null) {
    try {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $role;
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($archerId) {
            $_SESSION['archer_id'] = $archerId;
        }
        if ($managerId) {
            $_SESSION['manager_id'] = $managerId;
        }
        
        // Set secure session cookie
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => time() + SESSION_TIMEOUT,
                'path' => '/',
                'secure' => COOKIE_SECURE,
                'httponly' => COOKIE_HTTPONLY,
                'samesite' => COOKIE_SAMESITE
            ]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("Error creating session: " . $e->getMessage());
        return false;
    }
}

/**
 * Destroy user session (logout)
 * @return bool True if session destroyed
 */
function destroySession() {
    try {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
        return true;
    } catch (Exception $e) {
        error_log("Error destroying session: " . $e->getMessage());
        return false;
    }
}

/**
 * Check and validate session timeout
 * @return bool True if session is still valid, false if expired
 */
function validateSessionTimeout() {
    if (!isset($_SESSION['login_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        destroySession();
        return false;
    }
    
    // Update login time to extend session
    $_SESSION['login_time'] = time();
    return true;
}

/**
 * Require authentication - redirect to login if not authenticated
 * @param string $redirectTo URL to redirect to after login (optional)
 */
function requireAuth($redirectTo = null) {
    if (!isAuthenticated() || !validateSessionTimeout()) {
        $_SESSION = array();
        
        $redirect = $redirectTo ?? $_SERVER['REQUEST_URI'];
        header('Location: /ArchDB/login.html?redirect=' . urlencode($redirect));
        exit;
    }
}

/**
 * Require specific role - redirect to unauthorized page if not correct role
 * @param string|array $requiredRole Role or array of roles required
 */
function requireRole($requiredRole) {
    requireAuth();
    
    $roles = is_array($requiredRole) ? $requiredRole : [$requiredRole];
    
    if (!in_array(getCurrentUserRole(), $roles)) {
        header('HTTP/1.1 403 Forbidden');
        header('Location: /ArchDB/unauthorized.html');
        exit;
    }
}

/**
 * Check if user is authenticated (API endpoint)
 * Returns JSON response for AJAX requests
 */
function checkAuthAPI() {
    if (!isAuthenticated() || !validateSessionTimeout()) {
        header('Content-Type: application/json');
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'authenticated' => false
        ]);
        exit;
    }
}

/**
 * Get user info from database
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return array|null User info or null if not found
 */
function getUserInfo($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, username, email, role, archer_id, manager_id, created_at FROM users WHERE id = ? AND is_active = TRUE");
    if (!$stmt) {
        error_log("Prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    $stmt->close();
    return null;
}

/**
 * Get archer info from database
 * @param mysqli $conn Database connection
 * @param int $archerId Archer ID
 * @return array|null Archer info or null if not found
 */
function getArcherInfo($conn, $archerId) {
    $stmt = $conn->prepare("SELECT * FROM archer WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare error: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    $stmt->close();
    return null;
}

/**
 * Log user activity
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param string $action Action performed
 * @param string $details Additional details (optional)
 */
function logActivity($conn, $userId, $action, $details = null) {
    // This would typically log to an audit table
    error_log("User $userId: $action - $details");
}

/**
 * Sanitize user input
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array Array with 'valid' bool and 'errors' array
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => count($errors) === 0,
        'errors' => $errors
    ];
}

?>

