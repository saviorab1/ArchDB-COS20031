<!DOCTYPE html>
<?php
require_once 'config/database.php';
$pdo = getDBConnection();

// Get statistics for dashboard
$totalArchers = $pdo->query("SELECT COUNT(*) as count FROM archer")->fetch()['count'];
$totalCompetitions = $pdo->query("SELECT COUNT(*) as count FROM competition")->fetch()['count'];
$totalScores = $pdo->query("SELECT COUNT(DISTINCT registration_id, archer_id, round_id, score_date) as count FROM score WHERE registration_id IS NOT NULL")->fetch()['count'];
$upcomingCompetitions = $pdo->query("SELECT COUNT(*) as count FROM competition WHERE competition_date >= CURDATE()")->fetch()['count'];

// Get recent competitions
$recentCompetitionsStmt = $pdo->query("
    SELECT c.*, COUNT(DISTINCT r.archer_id) as participant_count
    FROM competition c
    LEFT JOIN registration r ON c.id = r.competition_id
    GROUP BY c.id
    ORDER BY c.competition_date DESC
    LIMIT 5
");
$recentCompetitions = $recentCompetitionsStmt->fetchAll();

// Get top performers (highest single round total)
$topPerformersStmt = $pdo->query("
    SELECT 
        a.id, a.first_name, a.last_name,
        r.name as round_name,
        SUM(s.end_total) as total_score,
        s.score_date
    FROM score s
    JOIN archer a ON s.archer_id = a.id
    JOIN round r ON s.round_id = r.id
    WHERE s.registration_id IS NOT NULL
    GROUP BY s.archer_id, s.registration_id, s.round_id
    ORDER BY total_score DESC
    LIMIT 5
");
$topPerformers = $topPerformersStmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArchDB - Archery Score Recording System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-section">
                <img src="ArchDB_logo.png" alt="ArchDB Logo">
                <h1>ArchDB - Archery Score Recording</h1>
            </div>
        </div>
    </header>

    <nav>
        <div class="nav-container">
            <ul class="nav-menu">
                <li><a href="index.php" class="active">Home</a></li>
                <li class="dropdown">
                    <a href="#">Archers</a>
                    <div class="dropdown-content">
                        <a href="pages/archers/list.php">View All Archers</a>
                        <a href="pages/archers/add.php">Add New Archer</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Competitions</a>
                    <div class="dropdown-content">
                        <a href="pages/competitions/list.php">View Competitions</a>
                        <a href="pages/competitions/add.php">Add Competition</a>
                        <a href="pages/competitions/register.php">Register for Competition</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Scores</a>
                    <div class="dropdown-content">
                        <a href="pages/scores/add.php">Record Score</a>
                        <a href="pages/scores/view.php">View Scores</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Rounds</a>
                    <div class="dropdown-content">
                        <a href="pages/rounds/list.php">View Rounds</a>
                        <a href="pages/rounds/add.php">Add Round</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Statistics</a>
                    <div class="dropdown-content">
                        <a href="pages/statistics/personal-best.php">Personal Bests</a>
                        <a href="pages/statistics/club-records.php">Club Records</a>
                        <a href="pages/statistics/championships.php">Championships</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h2 class="page-title">Dashboard</h2>

        <!-- Statistics Overview -->
        <div class="grid grid-4">
            <div class="stat-box">
                <div class="stat-value"><?php echo $totalArchers; ?></div>
                <div class="stat-label">Total Archers</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $totalCompetitions; ?></div>
                <div class="stat-label">Total Competitions</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $totalScores; ?></div>
                <div class="stat-label">Competition Scores</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $upcomingCompetitions; ?></div>
                <div class="stat-label">Upcoming Events</div>
            </div>
        </div>

        <!-- Recent Competitions and Top Performers -->
        <div class="grid grid-2">
            <!-- Recent Competitions -->
            <div class="card">
                <div class="card-header">Recent Competitions</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Competition Name</th>
                                <th>Date</th>
                                <th>Participants</th>
                                <th>Championship</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentCompetitions) > 0): ?>
                                <?php foreach ($recentCompetitions as $comp): ?>
                                    <tr>
                                        <td>
                                            <a href="pages/competitions/view.php?id=<?php echo $comp['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <?php echo htmlspecialchars($comp['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($comp['competition_date'])); ?></td>
                                        <td><?php echo $comp['participant_count']; ?></td>
                                        <td>
                                            <?php if ($comp['is_club_championship']): ?>
                                                <span class="badge badge-yellow">Yes</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">No</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No competitions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-2">
                    <a href="pages/competitions/list.php" class="btn btn-primary btn-sm">View All Competitions</a>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="card">
                <div class="card-header">Top Competition Performances</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Archer</th>
                                <th>Round</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($topPerformers) > 0): ?>
                                <?php foreach ($topPerformers as $performer): ?>
                                    <tr>
                                        <td>
                                            <a href="pages/archers/view.php?id=<?php echo $performer['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <?php echo htmlspecialchars($performer['first_name'] . ' ' . $performer['last_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($performer['round_name']); ?></td>
                                        <td><strong><?php echo $performer['total_score']; ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($performer['score_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No scores recorded yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-2">
                    <a href="pages/statistics/club-records.php" class="btn btn-primary btn-sm">View All Records</a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="pages/scores/add.php" class="btn btn-primary">Record New Score</a>
                <a href="pages/competitions/register.php" class="btn btn-success">Register for Competition</a>
                <a href="pages/archers/add.php" class="btn btn-warning">Add New Archer</a>
                <a href="pages/rounds/list.php" class="btn btn-secondary">Browse Rounds</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

