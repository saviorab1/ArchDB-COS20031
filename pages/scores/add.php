<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$success = '';
$error = '';

// Get parameters
$registrationId = $_GET['registration_id'] ?? ($_POST['registration_id'] ?? '');
$competitionId = $_GET['competition_id'] ?? '';

// Get all archers
$archersStmt = $pdo->query("SELECT id, first_name, last_name FROM archer ORDER BY last_name, first_name");
$archers = $archersStmt->fetchAll();

// Get all rounds
$roundsStmt = $pdo->query("SELECT id, name FROM round ORDER BY name");
$rounds = $roundsStmt->fetchAll();

// If registration_id is provided, get registration details
$registration = null;
if ($registrationId) {
    $regStmt = $pdo->prepare("
        SELECT reg.*, a.first_name, a.last_name, c.name as competition_name, r.name as round_name
        FROM registration reg
        JOIN archer a ON reg.archer_id = a.id
        JOIN competition c ON reg.competition_id = c.id
        LEFT JOIN round r ON reg.round_id = r.id
        WHERE reg.id = :id
    ");
    $regStmt->execute([':id' => $registrationId]);
    $registration = $regStmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_score'])) {
    $archerId = $_POST['archer_id'] ?? 0;
    $regId = $_POST['registration_id'] ?? null;
    $roundId = $_POST['round_id'] ?? 0;
    $rangeNumber = $_POST['range_number'] ?? 1;
    $endNumber = $_POST['end_number'] ?? 1;
    $scoreDate = $_POST['score_date'] ?? date('Y-m-d');
    $scoreTime = $_POST['score_time'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Get arrow scores
    $arrowScores = [];
    for ($i = 1; $i <= 6; $i++) {
        $score = $_POST["arrow_$i"] ?? 0;
        $arrowScores[] = (int)$score;
    }
    
    // Sort arrows highest to lowest as per requirements
    rsort($arrowScores);
    
    $endTotal = array_sum($arrowScores);
    $arrowScoresJson = json_encode($arrowScores);
    
    if ($archerId && $roundId) {
        try {
            $sql = "INSERT INTO score (archer_id, registration_id, round_id, range_number, end_number, arrow_scores, end_total, score_date, score_time, notes) 
                    VALUES (:archer_id, :registration_id, :round_id, :range_number, :end_number, :arrow_scores, :end_total, :score_date, :score_time, :notes)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':archer_id' => $archerId,
                ':registration_id' => $regId ?: null,
                ':round_id' => $roundId,
                ':range_number' => $rangeNumber,
                ':end_number' => $endNumber,
                ':arrow_scores' => $arrowScoresJson,
                ':end_total' => $endTotal,
                ':score_date' => $scoreDate,
                ':score_time' => $scoreTime ?: null,
                ':notes' => $notes
            ]);
            
            $success = "Score recorded successfully! End Total: $endTotal points";
            
            // Clear arrow scores but keep other fields
            for ($i = 1; $i <= 6; $i++) {
                unset($_POST["arrow_$i"]);
            }
            // Increment end number for next entry
            $_POST['end_number'] = $endNumber + 1;
            
        } catch (PDOException $e) {
            $error = "Error recording score: " . $e->getMessage();
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
    <title>Record Score - ArchDB</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script>
        function calculateTotal() {
            let total = 0;
            for (let i = 1; i <= 6; i++) {
                const value = parseInt(document.getElementById('arrow_' + i).value) || 0;
                total += value;
            }
            document.getElementById('end_total_display').textContent = total;
        }
        
        function setCurrentDateTime() {
            const now = new Date();
            document.getElementById('score_date').value = now.toISOString().split('T')[0];
            document.getElementById('score_time').value = now.toTimeString().split(' ')[0].substring(0, 5);
        }
        
        window.onload = function() {
            if (!document.getElementById('score_date').value) {
                setCurrentDateTime();
            }
            
            for (let i = 1; i <= 6; i++) {
                document.getElementById('arrow_' + i).addEventListener('input', calculateTotal);
            }
            calculateTotal();
        };
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
                    <a href="#" class="active">Scores</a>
                    <div class="dropdown-content">
                        <a href="add.php">Record Score</a>
                        <a href="view.php">View Scores</a>
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
        <h2 class="page-title">Record Score</h2>

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

        <?php if ($registration): ?>
            <div class="alert alert-info">
                Recording score for: <strong><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></strong>
                | Competition: <strong><?php echo htmlspecialchars($registration['competition_name']); ?></strong>
                <?php if ($registration['round_name']): ?>
                    | Round: <strong><?php echo htmlspecialchars($registration['round_name']); ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Score Entry Form</div>
            <form method="POST" action="add.php">
                <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($registrationId); ?>">
                
                <?php if ($registration): ?>
                    <input type="hidden" name="archer_id" value="<?php echo $registration['archer_id']; ?>">
                    <input type="hidden" name="round_id" value="<?php echo $registration['round_id']; ?>">
                <?php else: ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="archer_id">Select Archer *</label>
                            <select id="archer_id" name="archer_id" class="form-control" required>
                                <option value="">-- Select Archer --</option>
                                <?php foreach ($archers as $archer): ?>
                                    <option value="<?php echo $archer['id']; ?>" <?php echo ($_POST['archer_id'] ?? '') == $archer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="round_id">Select Round *</label>
                            <select id="round_id" name="round_id" class="form-control" required>
                                <option value="">-- Select Round --</option>
                                <?php foreach ($rounds as $round): ?>
                                    <option value="<?php echo $round['id']; ?>" <?php echo ($_POST['round_id'] ?? '') == $round['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($round['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="range_number">Range Number *</label>
                        <input type="number" id="range_number" name="range_number" class="form-control" min="1" max="10" value="<?php echo htmlspecialchars($_POST['range_number'] ?? 1); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_number">End Number *</label>
                        <input type="number" id="end_number" name="end_number" class="form-control" min="1" max="20" value="<?php echo htmlspecialchars($_POST['end_number'] ?? 1); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="score_date">Date *</label>
                        <input type="date" id="score_date" name="score_date" class="form-control" value="<?php echo htmlspecialchars($_POST['score_date'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="score_time">Time</label>
                        <input type="time" id="score_time" name="score_time" class="form-control" value="<?php echo htmlspecialchars($_POST['score_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="card" style="background-color: #f0f8ff; border-color: #0004ff;">
                    <h3 style="color: #0004ff; margin-bottom: 1rem;">Arrow Scores (Enter 6 arrows: 0-10)</h3>
                    <div class="form-row">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="form-group">
                                <label for="arrow_<?php echo $i; ?>">Arrow <?php echo $i; ?> *</label>
                                <input type="number" id="arrow_<?php echo $i; ?>" name="arrow_<?php echo $i; ?>" class="form-control" min="0" max="10" value="<?php echo htmlspecialchars($_POST["arrow_$i"] ?? ''); ?>" required>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: white; border-radius: 5px;">
                        <strong style="font-size: 1.2rem; color: #0004ff;">End Total: <span id="end_total_display" style="font-size: 1.5rem;">0</span> points</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Weather conditions, observations, etc."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" name="submit_score" class="btn btn-primary">Record This End</button>
                    <button type="button" onclick="setCurrentDateTime()" class="btn btn-warning">Set Current Date/Time</button>
                    <a href="view.php" class="btn btn-secondary">View Scores</a>
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

