<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$roundId = $_GET['id'] ?? 0;

// Get round details
$stmt = $pdo->prepare("SELECT * FROM round WHERE id = :id");
$stmt->execute([':id' => $roundId]);
$round = $stmt->fetch();

if (!$round) {
    header('Location: list.php');
    exit;
}

// Get round ranges
$rangesStmt = $pdo->prepare("
    SELECT * FROM round_range 
    WHERE round_id = :round_id 
    ORDER BY range_number
");
$rangesStmt->execute([':round_id' => $roundId]);
$ranges = $rangesStmt->fetchAll();

// Calculate totals
$totalEnds = 0;
$totalArrows = 0;
foreach ($ranges as $range) {
    $totalEnds += $range['number_of_ends'];
    $totalArrows += $range['number_of_ends'] * 6;
}

// Get club record for this round
$recordStmt = $pdo->prepare("
    SELECT 
        a.id as archer_id,
        a.first_name,
        a.last_name,
        SUM(s.end_total) as total_score,
        s.score_date
    FROM score s
    JOIN archer a ON s.archer_id = a.id
    WHERE s.round_id = :round_id
    GROUP BY s.archer_id, s.registration_id, s.score_date
    ORDER BY total_score DESC
    LIMIT 1
");
$recordStmt->execute([':round_id' => $roundId]);
$clubRecord = $recordStmt->fetch();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($round['name']); ?> - ArchDB</title>
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
        <div class="flex justify-between align-center">
            <h2 class="page-title"><?php echo htmlspecialchars($round['name']); ?></h2>
            <a href="list.php" class="btn btn-secondary">Back to List</a>
        </div>

        <!-- Round Details -->
        <div class="grid grid-2">
            <div class="card">
                <div class="card-header">Round Information</div>
                <table>
                    <tr>
                        <td style="width: 180px;"><strong>Round Name:</strong></td>
                        <td><?php echo htmlspecialchars($round['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Round Type:</strong></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($round['round_type']); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Number of Ranges:</strong></td>
                        <td><?php echo count($ranges); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Ends:</strong></td>
                        <td><?php echo $totalEnds; ?> ends</td>
                    </tr>
                    <tr>
                        <td><strong>Total Arrows:</strong></td>
                        <td><?php echo $totalArrows; ?> arrows</td>
                    </tr>
                    <?php if ($round['description']): ?>
                    <tr>
                        <td><strong>Description:</strong></td>
                        <td><?php echo nl2br(htmlspecialchars($round['description'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php if ($clubRecord): ?>
            <div class="card">
                <div class="card-header">Club Record</div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $clubRecord['total_score']; ?></div>
                    <div class="stat-label">Points</div>
                </div>
                <table style="margin-top: 1rem;">
                    <tr>
                        <td><strong>Record Holder:</strong></td>
                        <td>
                            <a href="../archers/view.php?id=<?php echo $clubRecord['archer_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                <?php echo htmlspecialchars($clubRecord['first_name'] . ' ' . $clubRecord['last_name']); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Date Achieved:</strong></td>
                        <td><?php echo date('d M Y', strtotime($clubRecord['score_date'])); ?></td>
                    </tr>
                </table>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">Club Record</div>
                <p class="text-center" style="padding: 2rem;">No scores recorded for this round yet.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Range Details -->
        <div class="card">
            <div class="card-header">Range Breakdown</div>
            <?php if (count($ranges) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Range Number</th>
                                <th>Distance</th>
                                <th>Number of Ends</th>
                                <th>Arrows per Range</th>
                                <th>Target Face Size</th>
                                <th>Maximum Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranges as $range): ?>
                                <?php 
                                $arrowsPerRange = $range['number_of_ends'] * 6;
                                $maxScore = $arrowsPerRange * 10;
                                ?>
                                <tr>
                                    <td><strong>Range <?php echo $range['range_number']; ?></strong></td>
                                    <td><?php echo $range['distance_meters']; ?>m</td>
                                    <td><?php echo $range['number_of_ends']; ?> ends</td>
                                    <td><?php echo $arrowsPerRange; ?> arrows</td>
                                    <td><?php echo $range['target_face_size_cm']; ?>cm face</td>
                                    <td><strong style="color: #0004ff;"><?php echo $maxScore; ?> points</strong></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: #f0f8ff; font-weight: bold;">
                                <td colspan="2"><strong>TOTAL</strong></td>
                                <td><strong><?php echo $totalEnds; ?> ends</strong></td>
                                <td><strong><?php echo $totalArrows; ?> arrows</strong></td>
                                <td>-</td>
                                <td><strong style="color: #ff0000; font-size: 1.1rem;"><?php echo $totalArrows * 10; ?> points</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem;">No ranges defined for this round.</p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="../scores/add.php" class="btn btn-primary">Record Score for This Round</a>
                <a href="list.php" class="btn btn-secondary">View All Rounds</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

