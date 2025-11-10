<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dateOfBirth = $_POST['date_of_birth'] ?? '';
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $equipment = $_POST['equipment'] ?? 'RECURVE';
    
    if ($firstName && $lastName && $gender && $dateOfBirth) {
        try {
            $sql = "INSERT INTO archer (first_name, last_name, gender, date_of_birth, phone_number, email, address, equipment) 
                    VALUES (:first_name, :last_name, :gender, :date_of_birth, :phone_number, :email, :address, :equipment)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':gender' => $gender,
                ':date_of_birth' => $dateOfBirth,
                ':phone_number' => $phoneNumber,
                ':email' => $email,
                ':address' => $address,
                ':equipment' => $equipment
            ]);
            
            $newArcherId = $pdo->lastInsertId();
            $success = "Archer added successfully!";
            
            // Clear form after successful submission
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Error: Email address already exists.";
            } else {
                $error = "Error adding archer: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Archer - ArchDB</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-section">
                <img src="../../ArchDB_logo.png" alt="ArchDB Logo">
                <h1>ArchDB - Archery Score Recording</h1>
            </div>
        </div>
    </header>

    <nav>
        <div class="nav-container">
            <ul class="nav-menu">
                <li><a href="../../index.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="active">Archers</a>
                    <div class="dropdown-content">
                        <a href="list.php">View All Archers</a>
                        <a href="add.php">Add New Archer</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Competitions</a>
                    <div class="dropdown-content">
                        <a href="../competitions/list.php">View Competitions</a>
                        <a href="../competitions/add.php">Add Competition</a>
                        <a href="../competitions/register.php">Register for Competition</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Scores</a>
                    <div class="dropdown-content">
                        <a href="../scores/add.php">Record Score</a>
                        <a href="../scores/view.php">View Scores</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Rounds</a>
                    <div class="dropdown-content">
                        <a href="../rounds/list.php">View Rounds</a>
                        <a href="../rounds/add.php">Add Round</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Statistics</a>
                    <div class="dropdown-content">
                        <a href="../statistics/personal-best.php">Personal Bests</a>
                        <a href="../statistics/club-records.php">Club Records</a>
                        <a href="../statistics/championships.php">Championships</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2 class="page-title">Add New Archer</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if (isset($newArcherId)): ?>
                    <a href="view.php?id=<?php echo $newArcherId; ?>" style="color: #0004ff;">View Archer Profile</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Archer Information</div>
            <form method="POST" action="add.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="M" <?php echo ($_POST['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                            <option value="F" <?php echo ($_POST['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control" placeholder="0912345678" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="archer@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Street address, City" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="equipment">Default Equipment *</label>
                    <select id="equipment" name="equipment" class="form-control" required>
                        <option value="RECURVE" <?php echo ($_POST['equipment'] ?? 'RECURVE') === 'RECURVE' ? 'selected' : ''; ?>>Recurve</option>
                        <option value="COMPOUND" <?php echo ($_POST['equipment'] ?? '') === 'COMPOUND' ? 'selected' : ''; ?>>Compound</option>
                        <option value="RECURVE_BAREBOW" <?php echo ($_POST['equipment'] ?? '') === 'RECURVE_BAREBOW' ? 'selected' : ''; ?>>Recurve Barebow</option>
                        <option value="COMPOUND_BAREBOW" <?php echo ($_POST['equipment'] ?? '') === 'COMPOUND_BAREBOW' ? 'selected' : ''; ?>>Compound Barebow</option>
                        <option value="LONGBOW" <?php echo ($_POST['equipment'] ?? '') === 'LONGBOW' ? 'selected' : ''; ?>>Longbow</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Add Archer</button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

