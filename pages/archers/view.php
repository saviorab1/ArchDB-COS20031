<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$archerId = $_GET['id'] ?? 0;

// Get archer details
$stmt = $pdo->prepare("SELECT * FROM archer WHERE id = :id");
$stmt->execute([':id' => $archerId]);
$archer = $stmt->fetch();

if (!$archer) {
    header('Location: list.php');
    exit;
}

$age = calculateAge($archer['date_of_birth']);
$category = getCategory($archer['date_of_birth'], $archer['gender'], $archer['equipment']);

// Get archer's competition scores
$competitionScoresStmt = $pdo->prepare("
    SELECT 
        c.name as competition_name,
        c.competition_date,
        r.name as round_name,
        SUM(s.end_total) as total_score,
        COUNT(s.id) as ends_completed,
        reg.id as registration_id
    FROM score s
    JOIN registration reg ON s.registration_id = reg.id
    JOIN competition c ON reg.competition_id = c.id
    JOIN round r ON s.round_id = r.id
    WHERE s.archer_id = :archer_id
    GROUP BY reg.id
    ORDER BY c.competition_date DESC
");
$competitionScoresStmt->execute([':archer_id' => $archerId]);
$competitionScores = $competitionScoresStmt->fetchAll();

// Get archer's practice scores
$practiceScoresStmt = $pdo->prepare("
    SELECT 
        r.name as round_name,
        s.score_date,
        SUM(s.end_total) as total_score,
        COUNT(s.id) as ends_completed,
        s.round_id
    FROM score s
    JOIN round r ON s.round_id = r.id
    WHERE s.archer_id = :archer_id AND s.registration_id IS NULL
    GROUP BY s.round_id, s.score_date
    ORDER BY s.score_date DESC
    LIMIT 10
");
$practiceScoresStmt->execute([':archer_id' => $archerId]);
$practiceScores = $practiceScoresStmt->fetchAll();

// Get personal bests
$personalBestsStmt = $pdo->prepare("
    SELECT 
        r.name as round_name,
        MAX(total_score) as best_score,
        MAX(score_date) as date_achieved
    FROM (
        SELECT 
            round_id,
            SUM(end_total) as total_score,
            score_date
        FROM score
        WHERE archer_id = :archer_id
        GROUP BY round_id, COALESCE(registration_id, 0), score_date
    ) as scores
    JOIN round r ON scores.round_id = r.id
    GROUP BY round_id
    ORDER BY best_score DESC
    LIMIT 5
");
$personalBestsStmt->execute([':archer_id' => $archerId]);
$personalBests = $personalBestsStmt->fetchAll();

// Get upcoming competitions archer is registered for
$upcomingCompsStmt = $pdo->prepare("
    SELECT 
        c.id,
        c.name,
        c.competition_date,
        c.location,
        r.name as round_name
    FROM registration reg
    JOIN competition c ON reg.competition_id = c.id
    LEFT JOIN round r ON reg.round_id = r.id
    WHERE reg.archer_id = :archer_id AND c.competition_date >= CURDATE()
    ORDER BY c.competition_date ASC
");
$upcomingCompsStmt->execute([':archer_id' => $archerId]);
$upcomingComps = $upcomingCompsStmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?> - ArchDB</title>
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
        <div class="flex justify-between align-center">
            <h2 class="page-title"><?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?></h2>
            <a href="list.php" class="btn btn-secondary">Back to List</a>
        </div>

        <!-- Archer Profile -->
        <div class="grid grid-2">
            <div class="card">
                <div class="card-header">Personal Information</div>
                <table>
                    <tr>
                        <td><strong>Full Name:</strong></td>
                        <td><?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Gender:</strong></td>
                        <td><?php echo $archer['gender'] == 'M' ? 'Male' : 'Female'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date of Birth:</strong></td>
                        <td><?php echo date('d M Y', strtotime($archer['date_of_birth'])); ?> (Age: <?php echo $age; ?>)</td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($category); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($archer['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td><?php echo htmlspecialchars($archer['phone_number']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address:</strong></td>
                        <td><?php echo htmlspecialchars($archer['address']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Default Equipment:</strong></td>
                        <td><span class="badge badge-yellow"><?php echo formatEquipment($archer['equipment']); ?></span></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <div class="card-header">Statistics Overview</div>
                <div class="grid grid-2">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo count($competitionScores); ?></div>
                        <div class="stat-label">Competitions</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo count($practiceScores); ?></div>
                        <div class="stat-label">Practice Sessions</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo count($upcomingComps); ?></div>
                        <div class="stat-label">Upcoming Events</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo count($personalBests); ?></div>
                        <div class="stat-label">Rounds Shot</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Bests -->
        <?php if (count($personalBests) > 0): ?>
        <div class="card">
            <div class="card-header">Personal Best Scores</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Best Score</th>
                            <th>Date Achieved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personalBests as $pb): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pb['round_name']); ?></td>
                            <td><strong style="color: #0004ff; font-size: 1.2rem;"><?php echo $pb['best_score']; ?></strong></td>
                            <td><?php echo date('d M Y', strtotime($pb['date_achieved'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upcoming Competitions -->
        <?php if (count($upcomingComps) > 0): ?>
        <div class="card">
            <div class="card-header">Upcoming Competitions</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Competition</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Round</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingComps as $comp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comp['name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($comp['competition_date'])); ?></td>
                            <td><?php echo htmlspecialchars($comp['location']); ?></td>
                            <td><?php echo htmlspecialchars($comp['round_name']); ?></td>
                            <td>
                                <a href="../competitions/view.php?id=<?php echo $comp['id']; ?>" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Competition Scores -->
        <?php if (count($competitionScores) > 0): ?>
        <div class="card">
            <div class="card-header">Competition Score History</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Competition</th>
                            <th>Date</th>
                            <th>Round</th>
                            <th>Total Score</th>
                            <th>Ends Shot</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competitionScores as $score): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($score['competition_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($score['competition_date'])); ?></td>
                            <td><?php echo htmlspecialchars($score['round_name']); ?></td>
                            <td><strong style="color: #0004ff; font-size: 1.1rem;"><?php echo $score['total_score']; ?></strong></td>
                            <td><?php echo $score['ends_completed']; ?> ends</td>
                            <td>
                                <a href="../scores/view.php?registration_id=<?php echo $score['registration_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Practice Scores -->
        <?php if (count($practiceScores) > 0): ?>
        <div class="card">
            <div class="card-header">Recent Practice Scores</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Round</th>
                            <th>Total Score</th>
                            <th>Ends Shot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($practiceScores as $score): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($score['score_date'])); ?></td>
                            <td><?php echo htmlspecialchars($score['round_name']); ?></td>
                            <td><strong style="color: #0004ff;"><?php echo $score['total_score']; ?></strong></td>
                            <td><?php echo $score['ends_completed']; ?> ends</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

