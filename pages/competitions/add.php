<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $competitionDate = $_POST['competition_date'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isChampionship = isset($_POST['is_club_championship']) ? 1 : 0;
    
    if ($name && $competitionDate && $location) {
        try {
            $sql = "INSERT INTO competition (name, competition_date, location, description, is_club_championship) 
                    VALUES (:name, :competition_date, :location, :description, :is_championship)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':competition_date' => $competitionDate,
                ':location' => $location,
                ':description' => $description,
                ':is_championship' => $isChampionship
            ]);
            
            $newCompId = $pdo->lastInsertId();
            $success = "Competition added successfully!";
            
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            $error = "Error adding competition: " . $e->getMessage();
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
    <title>Add New Competition - ArchDB</title>
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
        <h2 class="page-title">Add New Competition</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <?php if (isset($newCompId)): ?>
                    <a href="view.php?id=<?php echo $newCompId; ?>" style="color: #0004ff;">View Competition</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Competition Information</div>
            <form method="POST" action="add.php">
                <div class="form-group">
                    <label for="name">Competition Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="e.g., Spring Championship 2025">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="competition_date">Competition Date *</label>
                        <input type="date" id="competition_date" name="competition_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['competition_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" class="form-control" required value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" placeholder="e.g., Archery Range A, District 1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Competition details, rules, etc."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_club_championship" value="1" <?php echo isset($_POST['is_club_championship']) ? 'checked' : ''; ?>>
                        <span>This is a Club Championship event</span>
                    </label>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Add Competition</button>
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

