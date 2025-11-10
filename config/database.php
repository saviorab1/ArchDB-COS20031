<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'archery_score_db');
define('DB_USER', 'root'); // Change to 'archery_user' if you created a dedicated user
define('DB_PASS', ''); // Change to your password
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

// Helper function to calculate age from date of birth
function calculateAge($dateOfBirth) {
    $dob = new DateTime($dateOfBirth);
    $today = new DateTime('today');
    $age = $dob->diff($today)->y;
    return $age;
}

// Helper function to get age category
function getAgeCategory($dateOfBirth, $gender) {
    $age = calculateAge($dateOfBirth);
    
    if ($age >= 70) return "70+ " . ($gender == 'M' ? 'Male' : 'Female');
    if ($age >= 60) return "60+ " . ($gender == 'M' ? 'Male' : 'Female');
    if ($age >= 50) return "50+ " . ($gender == 'M' ? 'Male' : 'Female');
    if ($age < 14) return "Under 14 " . ($gender == 'M' ? 'Male' : 'Female');
    if ($age < 16) return "Under 16 " . ($gender == 'M' ? 'Male' : 'Female');
    if ($age < 18) return "Under 18 " . ($gender == 'M' ? 'Male' : 'Female');
    if ($age < 21) return "Under 21 " . ($gender == 'M' ? 'Male' : 'Female');
    
    return ($gender == 'M' ? 'Male' : 'Female') . " Open";
}

// Helper function to format equipment name
function formatEquipment($equipment) {
    $equipmentNames = [
        'RECURVE' => 'Recurve',
        'COMPOUND' => 'Compound',
        'RECURVE_BAREBOW' => 'Recurve Barebow',
        'COMPOUND_BAREBOW' => 'Compound Barebow',
        'LONGBOW' => 'Longbow'
    ];
    return $equipmentNames[$equipment] ?? $equipment;
}

// Helper function to get category (age + equipment)
function getCategory($dateOfBirth, $gender, $equipment) {
    $ageCategory = getAgeCategory($dateOfBirth, $gender);
    $equipmentName = formatEquipment($equipment);
    return $ageCategory . " " . $equipmentName;
}

// Helper function to calculate total score from multiple ends
function calculateTotalScore($pdo, $archerId, $registrationId, $roundId) {
    $sql = "SELECT SUM(end_total) as total 
            FROM score 
            WHERE archer_id = :archer_id 
            AND round_id = :round_id";
    
    $params = [
        ':archer_id' => $archerId,
        ':round_id' => $roundId
    ];
    
    if ($registrationId) {
        $sql .= " AND registration_id = :registration_id";
        $params[':registration_id'] = $registrationId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['total'] ?? 0;
}
?>

