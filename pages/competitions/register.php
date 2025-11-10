<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$success = '';
$error = '';

// Get all archers
$archersStmt = $pdo->query("SELECT id, first_name, last_name, equipment FROM archer ORDER BY last_name, first_name");
$archers = $archersStmt->fetchAll();

// Get upcoming competitions
$competitionsStmt = $pdo->query("SELECT id, name, competition_date, location FROM competition WHERE competition_date >= CURDATE() ORDER BY competition_date ASC");
$competitions = $competitionsStmt->fetchAll();

// Get all rounds
$roundsStmt = $pdo->query("SELECT id, name FROM round ORDER BY name");
$rounds = $roundsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archerId = $_POST['archer_id'] ?? 0;
    $competitionId = $_POST['competition_id'] ?? 0;
    $equipmentUsed = $_POST['equipment_used'] ?? '';
    $roundId = $_POST['round_id'] ?? null;
    
    if ($archerId && $competitionId && $equipmentUsed) {
        try {
            $sql = "INSERT INTO registration (archer_id, competition_id, equipment_used, round_id) 
                    VALUES (:archer_id, :competition_id, :equipment_used, :round_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':archer_id' => $archerId,
                ':competition_id' => $competitionId,
                ':equipment_used' => $equipmentUsed,
                ':round_id' => $roundId ?: null
            ]);
            
            $success = "Registration successful!";
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Error: Archer is already registered for this competition.";
            } else {
                $error = "Error registering: " . $e->getMessage();
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
    <title>Register for Competition - ArchDB</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script>
        function updateEquipment() {
            const archerSelect = document.getElementById('archer_id');
            const equipmentSelect = document.getElementById('equipment_used');
            const selectedOption = archerSelect.options[archerSelect.selectedIndex];
            const defaultEquipment = selectedOption.getAttribute('data-equipment');
            
            if (defaultEquipment) {
                equipmentSelect.value = defaultEquipment;
            }
        }
    </script>
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
                    <a href="#">Archers</a>
                    <div class="dropdown-content">
                        <a href="../archers/list.php">View All Archers</a>
                        <a href="../archers/add.php">Add New Archer</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="active">Competitions</a>
                    <div class="dropdown-content">
                        <a href="list.php">View Competitions</a>
                        <a href="add.php">Add Competition</a>
                        <a href="register.php">Register for Competition</a>
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
        <h2 class="page-title">Register for Competition</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (count($competitions) == 0): ?>
            <div class="alert alert-warning">
                No upcoming competitions available. <a href="add.php" style="color: #0004ff;">Add a new competition</a>
            </div>
        <?php elseif (count($archers) == 0): ?>
            <div class="alert alert-warning">
                No archers registered. <a href="../archers/add.php" style="color: #0004ff;">Add an archer</a> first.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">Registration Form</div>
                <form method="POST" action="register.php">
                    <div class="form-group">
                        <label for="archer_id">Select Archer *</label>
                        <select id="archer_id" name="archer_id" class="form-control" required onchange="updateEquipment()">
                            <option value="">-- Select Archer --</option>
                            <?php foreach ($archers as $archer): ?>
                                <option value="<?php echo $archer['id']; ?>" 
                                        data-equipment="<?php echo $archer['equipment']; ?>"
                                        <?php echo ($_POST['archer_id'] ?? '') == $archer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="competition_id">Select Competition *</label>
                        <select id="competition_id" name="competition_id" class="form-control" required>
                            <option value="">-- Select Competition --</option>
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>" <?php echo ($_POST['competition_id'] ?? '') == $comp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comp['name']); ?> - <?php echo date('d M Y', strtotime($comp['competition_date'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="equipment_used">Equipment to Use *</label>
                        <select id="equipment_used" name="equipment_used" class="form-control" required>
                            <option value="">-- Select Equipment --</option>
                            <option value="RECURVE" <?php echo ($_POST['equipment_used'] ?? '') === 'RECURVE' ? 'selected' : ''; ?>>Recurve</option>
                            <option value="COMPOUND" <?php echo ($_POST['equipment_used'] ?? '') === 'COMPOUND' ? 'selected' : ''; ?>>Compound</option>
                            <option value="RECURVE_BAREBOW" <?php echo ($_POST['equipment_used'] ?? '') === 'RECURVE_BAREBOW' ? 'selected' : ''; ?>>Recurve Barebow</option>
                            <option value="COMPOUND_BAREBOW" <?php echo ($_POST['equipment_used'] ?? '') === 'COMPOUND_BAREBOW' ? 'selected' : ''; ?>>Compound Barebow</option>
                            <option value="LONGBOW" <?php echo ($_POST['equipment_used'] ?? '') === 'LONGBOW' ? 'selected' : ''; ?>>Longbow</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="round_id">Round (Optional)</label>
                        <select id="round_id" name="round_id" class="form-control">
                            <option value="">-- Select Round (Optional) --</option>
                            <?php foreach ($rounds as $round): ?>
                                <option value="<?php echo $round['id']; ?>" <?php echo ($_POST['round_id'] ?? '') == $round['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($round['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

