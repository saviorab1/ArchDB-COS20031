<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$registrationId = $_GET['registration_id'] ?? '';
$archerId = $_GET['archer_id'] ?? '';

// Get scores based on filters
if ($registrationId) {
    // Get competition score details
    $scoresStmt = $pdo->prepare("
        SELECT 
            s.*,
            a.first_name,
            a.last_name,
            r.name as round_name,
            c.name as competition_name,
            c.competition_date
        FROM score s
        JOIN archer a ON s.archer_id = a.id
        JOIN round r ON s.round_id = r.id
        JOIN registration reg ON s.registration_id = reg.id
        JOIN competition c ON reg.competition_id = c.id
        WHERE s.registration_id = :registration_id
        ORDER BY s.range_number, s.end_number
    ");
    $scoresStmt->execute([':registration_id' => $registrationId]);
    $scores = $scoresStmt->fetchAll();
    
    $pageTitle = "Competition Scorecard";
} elseif ($archerId) {
    // Get all scores for an archer
    $scoresStmt = $pdo->prepare("
        SELECT 
            s.*,
            a.first_name,
            a.last_name,
            r.name as round_name,
            c.name as competition_name,
            c.competition_date
        FROM score s
        JOIN archer a ON s.archer_id = a.id
        JOIN round r ON s.round_id = r.id
        LEFT JOIN registration reg ON s.registration_id = reg.id
        LEFT JOIN competition c ON reg.competition_id = c.id
        WHERE s.archer_id = :archer_id
        ORDER BY s.score_date DESC, s.range_number, s.end_number
        LIMIT 50
    ");
    $scoresStmt->execute([':archer_id' => $archerId]);
    $scores = $scoresStmt->fetchAll();
    
    $pageTitle = "Archer Scores";
} else {
    // Get recent scores
    $scoresStmt = $pdo->query("
        SELECT 
            s.*,
            a.first_name,
            a.last_name,
            r.name as round_name,
            c.name as competition_name,
            c.competition_date
        FROM score s
        JOIN archer a ON s.archer_id = a.id
        JOIN round r ON s.round_id = r.id
        LEFT JOIN registration reg ON s.registration_id = reg.id
        LEFT JOIN competition c ON reg.competition_id = c.id
        ORDER BY s.score_date DESC, s.created_at DESC
        LIMIT 50
    ");
    $scores = $scoresStmt->fetchAll();
    
    $pageTitle = "Recent Scores";
}

// Calculate totals if viewing a specific registration
$totalScore = 0;
$totalEnds = 0;
if ($registrationId && count($scores) > 0) {
    foreach ($scores as $score) {
        $totalScore += $score['end_total'];
        $totalEnds++;
    }
}

// Helper function to display arrow scores with colors
function displayArrowScores($arrowScoresJson) {
    $arrows = json_decode($arrowScoresJson, true);
    $html = '<div class="score-display">';
    foreach ($arrows as $arrow) {
        $class = 'arrow-score ';
        if ($arrow == 10) $class .= 'arrow-score-10';
        elseif ($arrow == 9) $class .= 'arrow-score-9';
        elseif ($arrow == 8) $class .= 'arrow-score-8';
        elseif ($arrow == 7) $class .= 'arrow-score-7';
        else $class .= 'arrow-score-low';
        
        $html .= "<span class='$class'>$arrow</span>";
    }
    $html .= '</div>';
    return $html;
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ArchDB</title>
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
        <h2 class="page-title"><?php echo $pageTitle; ?></h2>

        <?php if ($registrationId && count($scores) > 0): ?>
            <!-- Competition Scorecard View -->
            <div class="card">
                <div class="card-header">Scorecard Summary</div>
                <table>
                    <tr>
                        <td style="width: 200px;"><strong>Archer:</strong></td>
                        <td><?php echo htmlspecialchars($scores[0]['first_name'] . ' ' . $scores[0]['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Competition:</strong></td>
                        <td><?php echo htmlspecialchars($scores[0]['competition_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td><?php echo date('d M Y', strtotime($scores[0]['competition_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Round:</strong></td>
                        <td><?php echo htmlspecialchars($scores[0]['round_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Ends:</strong></td>
                        <td><?php echo $totalEnds; ?> ends</td>
                    </tr>
                    <tr>
                        <td><strong>Total Score:</strong></td>
                        <td><strong style="color: #0004ff; font-size: 1.5rem;"><?php echo $totalScore; ?> points</strong></td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>

        <!-- Scores Table -->
        <div class="card">
            <div class="card-header">
                <?php echo $registrationId ? 'End-by-End Breakdown' : 'Scores'; ?>
            </div>
            
            <?php if (count($scores) > 0): ?>
                <div class="table-container">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <?php if (!$registrationId): ?>
                                    <th>Date</th>
                                    <th>Archer</th>
                                    <th>Competition</th>
                                <?php endif; ?>
                                <th>Round</th>
                                <th>Range</th>
                                <th>End</th>
                                <th>Arrow Scores</th>
                                <th>End Total</th>
                                <?php if (!$registrationId): ?>
                                    <th>Type</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scores as $score): ?>
                                <tr>
                                    <?php if (!$registrationId): ?>
                                        <td><?php echo date('d M Y', strtotime($score['score_date'])); ?></td>
                                        <td>
                                            <a href="../archers/view.php?id=<?php echo $score['archer_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <?php echo htmlspecialchars($score['first_name'] . ' ' . $score['last_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($score['competition_name']): ?>
                                                <?php echo htmlspecialchars($score['competition_name']); ?>
                                            <?php else: ?>
                                                <span class="badge badge-info">Practice</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($score['round_name']); ?></td>
                                    <td>Range <?php echo $score['range_number']; ?></td>
                                    <td>End <?php echo $score['end_number']; ?></td>
                                    <td><?php echo displayArrowScores($score['arrow_scores']); ?></td>
                                    <td><strong style="color: #0004ff; font-size: 1.1rem;"><?php echo $score['end_total']; ?></strong></td>
                                    <?php if (!$registrationId): ?>
                                        <td>
                                            <?php if ($score['registration_id']): ?>
                                                <span class="badge badge-yellow">Competition</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">Practice</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem;">No scores found.</p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="add.php" class="btn btn-primary">Record New Score</a>
                <?php if ($registrationId): ?>
                    <a href="add.php?registration_id=<?php echo $registrationId; ?>" class="btn btn-success">Add More Ends</a>
                <?php endif; ?>
                <a href="view.php" class="btn btn-secondary">View All Scores</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

