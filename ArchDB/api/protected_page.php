<?php
/**
 * Protected Page Middleware
 * Include this in any page that requires authentication
 * Usage: require_once 'api/protected_page.php';
 */

require_once '../../db.php';
require_once 'auth_helper.php';

/**
 * Protect a page - requires authentication
 * Optional: specify required role
 */
function protectPage($requiredRole = null) {
    // Check authentication
    if (!isAuthenticated() || !validateSessionTimeout()) {
        header('HTTP/1.1 401 Unauthorized');
        header('Location: login.html');
        exit;
    }
    
    // Check role if specified
    if ($requiredRole !== null) {
        if (!hasRole($requiredRole)) {
            header('HTTP/1.1 403 Forbidden');
            header('Location: unauthorized.html');
            exit;
        }
    }
}

/**
 * Get current user data (for API endpoints)
 * Returns null if not authenticated
 */
function getCurrentUser() {
    if (!isAuthenticated() || !validateSessionTimeout()) {
        return null;
    }
    
    return [
        'id' => getCurrentUserId(),
        'role' => getCurrentUserRole(),
        'archer_id' => getCurrentArcherId(),
        'manager_id' => getCurrentManagerId()
    ];
}

/**
 * Check role and return error if not authorized
 * For API endpoints
 */
function checkRoleAPI($requiredRole) {
    $user = getCurrentUser();
    
    if (!$user) {
        header('HTTP/1.1 401 Unauthorized');
        return json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
    }
    
    if (!hasRole($requiredRole)) {
        header('HTTP/1.1 403 Forbidden');
        return json_encode([
            'success' => false,
            'message' => 'You do not have permission to perform this action'
        ]);
    }
    
    return null;
}

?>

