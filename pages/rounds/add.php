<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $roundType = trim($_POST['round_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Get ranges data
    $rangeCount = $_POST['range_count'] ?? 0;
    $ranges = [];
    
    for ($i = 1; $i <= $rangeCount; $i++) {
        if (isset($_POST["distance_$i"]) && isset($_POST["ends_$i"]) && isset($_POST["face_size_$i"])) {
            $ranges[] = [
                'distance' => (int)$_POST["distance_$i"],
                'ends' => (int)$_POST["ends_$i"],
                'face_size' => (int)$_POST["face_size_$i"]
            ];
        }
    }
    
    if ($name && $roundType && count($ranges) > 0) {
        try {
            $pdo->beginTransaction();
            
            // Insert round
            $sql = "INSERT INTO round (name, round_type, description) VALUES (:name, :round_type, :description)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':round_type' => $roundType,
                ':description' => $description
            ]);
            
            $roundId = $pdo->lastInsertId();
            
            // Insert ranges
            $rangeSql = "INSERT INTO round_range (round_id, range_number, distance_meters, number_of_ends, target_face_size_cm) 
                         VALUES (:round_id, :range_number, :distance, :ends, :face_size)";
            $rangeStmt = $pdo->prepare($rangeSql);
            
            foreach ($ranges as $index => $range) {
                $rangeStmt->execute([
                    ':round_id' => $roundId,
                    ':range_number' => $index + 1,
                    ':distance' => $range['distance'],
                    ':ends' => $range['ends'],
                    ':face_size' => $range['face_size']
                ]);
            }
            
            $pdo->commit();
            $success = "Round added successfully!";
            $_POST = [];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Error: A round with this name already exists.";
            } else {
                $error = "Error adding round: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields and add at least one range.";
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Round - ArchDB</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script>
        let rangeCount = 1;
        
        function addRange() {
            rangeCount++;
            const container = document.getElementById('ranges-container');
            const rangeDiv = document.createElement('div');
            rangeDiv.className = 'card';
            rangeDiv.style.backgroundColor = '#f8f9fa';
            rangeDiv.innerHTML = `
                <h4 style="color: #0004ff; margin-bottom: 1rem;">Range ${rangeCount}</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="distance_${rangeCount}">Distance (meters) *</label>
                        <input type="number" id="distance_${rangeCount}" name="distance_${rangeCount}" class="form-control" min="10" max="90" required>
                    </div>
                    <div class="form-group">
                        <label for="ends_${rangeCount}">Number of Ends *</label>
                        <input type="number" id="ends_${rangeCount}" name="ends_${rangeCount}" class="form-control" min="1" max="20" required>
                    </div>
                    <div class="form-group">
                        <label for="face_size_${rangeCount}">Target Face Size (cm) *</label>
                        <select id="face_size_${rangeCount}" name="face_size_${rangeCount}" class="form-control" required>
                            <option value="">Select Size</option>
                            <option value="40">40cm (Indoor)</option>
                            <option value="60">60cm (Indoor)</option>
                            <option value="80">80cm</option>
                            <option value="122">122cm</option>
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(rangeDiv);
            document.getElementById('range_count').value = rangeCount;
        }
        
        function removeRange() {
            if (rangeCount > 1) {
                const container = document.getElementById('ranges-container');
                container.removeChild(container.lastChild);
                rangeCount--;
                document.getElementById('range_count').value = rangeCount;
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
                    <a href="#" class="active">Rounds</a>
                    <div class="dropdown-content">
                        <a href="list.php">View Rounds</a>
                        <a href="add.php">Add Round</a>
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
        <h2 class="page-title">Add New Round</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <a href="list.php" style="color: #0004ff;">View All Rounds</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Round Information</div>
            <form method="POST" action="add.php">
                <input type="hidden" id="range_count" name="range_count" value="1">
                
                <div class="form-group">
                    <label for="name">Round Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="e.g., WA 900, Olympic Round">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="round_type">Round Type *</label>
                        <input type="text" id="round_type" name="round_type" class="form-control" required value="<?php echo htmlspecialchars($_POST['round_type'] ?? ''); ?>" placeholder="e.g., WA90, OLYMPIC">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Description of the round..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <hr style="margin: 2rem 0;">

                <div class="flex justify-between align-center" style="margin-bottom: 1rem;">
                    <h3 style="color: #0004ff;">Ranges</h3>
                    <div class="btn-group">
                        <button type="button" onclick="addRange()" class="btn btn-success btn-sm">+ Add Range</button>
                        <button type="button" onclick="removeRange()" class="btn btn-danger btn-sm">- Remove Last Range</button>
                    </div>
                </div>

                <div id="ranges-container">
                    <div class="card" style="background-color: #f8f9fa;">
                        <h4 style="color: #0004ff; margin-bottom: 1rem;">Range 1</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="distance_1">Distance (meters) *</label>
                                <input type="number" id="distance_1" name="distance_1" class="form-control" min="10" max="90" required value="<?php echo htmlspecialchars($_POST['distance_1'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ends_1">Number of Ends *</label>
                                <input type="number" id="ends_1" name="ends_1" class="form-control" min="1" max="20" required value="<?php echo htmlspecialchars($_POST['ends_1'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="face_size_1">Target Face Size (cm) *</label>
                                <select id="face_size_1" name="face_size_1" class="form-control" required>
                                    <option value="">Select Size</option>
                                    <option value="40" <?php echo ($_POST['face_size_1'] ?? '') == '40' ? 'selected' : ''; ?>>40cm (Indoor)</option>
                                    <option value="60" <?php echo ($_POST['face_size_1'] ?? '') == '60' ? 'selected' : ''; ?>>60cm (Indoor)</option>
                                    <option value="80" <?php echo ($_POST['face_size_1'] ?? '') == '80' ? 'selected' : ''; ?>>80cm</option>
                                    <option value="122" <?php echo ($_POST['face_size_1'] ?? '') == '122' ? 'selected' : ''; ?>>122cm</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Add Round</button>
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

