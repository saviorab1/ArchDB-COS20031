<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

// Get selected archer
$selectedArcherId = $_GET['archer_id'] ?? '';

// Get all archers
$archersStmt = $pdo->query("SELECT id, first_name, last_name FROM archer ORDER BY last_name, first_name");
$archers = $archersStmt->fetchAll();

// Get personal bests if archer is selected
$personalBests = [];
if ($selectedArcherId) {
    $pbStmt = $pdo->prepare("
        SELECT 
            r.id as round_id,
            r.name as round_name,
            MAX(total_score) as best_score,
            score_date as date_achieved
        FROM (
            SELECT 
                round_id,
                SUM(end_total) as total_score,
                score_date
            FROM score
            WHERE archer_id = :archer_id
            GROUP BY round_id, COALESCE(registration_id, CONCAT('practice_', score_date))
        ) as scores
        JOIN round r ON scores.round_id = r.id
        GROUP BY round_id
        ORDER BY best_score DESC
    ");
    $pbStmt->execute([':archer_id' => $selectedArcherId]);
    $personalBests = $pbStmt->fetchAll();
    
    // Get archer details
    $archerStmt = $pdo->prepare("SELECT * FROM archer WHERE id = :id");
    $archerStmt->execute([':id' => $selectedArcherId]);
    $selectedArcher = $archerStmt->fetch();
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Bests - ArchDB</title>
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
                    <a href="#" class="active">Statistics</a>
                    <div class="dropdown-content">
                        <a href="personal-best.php">Personal Bests</a>
                        <a href="club-records.php">Club Records</a>
                        <a href="championships.php">Championships</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2 class="page-title">Personal Best Scores</h2>

        <!-- Archer Selection -->
        <div class="card">
            <div class="card-header">Select Archer</div>
            <form method="GET" action="personal-best.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="archer_id">Choose an Archer *</label>
                        <select id="archer_id" name="archer_id" class="form-control" onchange="this.form.submit()" required>
                            <option value="">-- Select Archer --</option>
                            <?php foreach ($archers as $archer): ?>
                                <option value="<?php echo $archer['id']; ?>" <?php echo $selectedArcherId == $archer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($selectedArcherId && isset($selectedArcher)): ?>
            <!-- Archer Profile Summary -->
            <div class="card">
                <div class="card-header">Archer Profile</div>
                <table>
                    <tr>
                        <td style="width: 200px;"><strong>Name:</strong></td>
                        <td>
                            <a href="../archers/view.php?id=<?php echo $selectedArcher['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                <?php echo htmlspecialchars($selectedArcher['first_name'] . ' ' . $selectedArcher['last_name']); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td><?php echo getCategory($selectedArcher['date_of_birth'], $selectedArcher['gender'], $selectedArcher['equipment']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Default Equipment:</strong></td>
                        <td><?php echo formatEquipment($selectedArcher['equipment']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Rounds Shot:</strong></td>
                        <td><?php echo count($personalBests); ?> different rounds</td>
                    </tr>
                </table>
            </div>

            <!-- Personal Bests Table -->
            <?php if (count($personalBests) > 0): ?>
                <div class="card">
                    <div class="card-header">Personal Best Scores by Round</div>
                    <div class="table-container">
                        <table class="table-striped">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Round Name</th>
                                    <th>Best Score</th>
                                    <th>Date Achieved</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($personalBests as $pb): 
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size: 1.2rem;">
                                                <?php 
                                                if ($rank == 1) echo '<span style="color: #ffff00; background: #000; padding: 0.25rem 0.5rem; border-radius: 3px;">🥇</span>';
                                                elseif ($rank == 2) echo '<span style="color: #c0c0c0;">🥈</span>';
                                                elseif ($rank == 3) echo '<span style="color: #cd7f32;">🥉</span>';
                                                else echo $rank;
                                                ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="../rounds/view.php?id=<?php echo $pb['round_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <strong><?php echo htmlspecialchars($pb['round_name']); ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <strong style="color: #0004ff; font-size: 1.3rem;">
                                                <?php echo $pb['best_score']; ?> points
                                            </strong>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($pb['date_achieved'])); ?></td>
                                        <td>
                                            <a href="../rounds/view.php?id=<?php echo $pb['round_id']; ?>" class="btn btn-primary btn-sm">View Round</a>
                                        </td>
                                    </tr>
                                <?php 
                                    $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-3">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $personalBests[0]['best_score']; ?></div>
                        <div class="stat-label">Highest Score</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo count($personalBests); ?></div>
                        <div class="stat-label">Rounds Shot</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo number_format(array_sum(array_column($personalBests, 'best_score')) / count($personalBests), 0); ?></div>
                        <div class="stat-label">Average PB Score</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No scores recorded for this archer yet.
                    <a href="../scores/add.php" style="color: #0004ff;">Record a score</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-header">Information</div>
                <p style="padding: 2rem; text-align: center;">
                    Please select an archer from the dropdown above to view their personal best scores.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

