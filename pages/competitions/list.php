<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

// Handle filters
$search = $_GET['search'] ?? '';
$championshipFilter = $_GET['championship'] ?? '';
$dateFilter = $_GET['date_filter'] ?? '';

$sql = "SELECT c.*, COUNT(DISTINCT r.archer_id) as participant_count
        FROM competition c
        LEFT JOIN registration r ON c.id = r.competition_id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.location) LIKE LOWER(:search))";
    $params[':search'] = "%$search%";
}

if ($championshipFilter === 'yes') {
    $sql .= " AND c.is_club_championship = 1";
} elseif ($championshipFilter === 'no') {
    $sql .= " AND c.is_club_championship = 0";
}

if ($dateFilter === 'upcoming') {
    $sql .= " AND c.competition_date >= CURDATE()";
} elseif ($dateFilter === 'past') {
    $sql .= " AND c.competition_date < CURDATE()";
}

$sql .= " GROUP BY c.id ORDER BY c.competition_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$competitions = $stmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Competitions - ArchDB</title>
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
        <h2 class="page-title">All Competitions</h2>

        <!-- Search and Filter -->
        <div class="card">
            <form method="GET" action="list.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="search">Search Competition</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Search by name or location..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="championship">Championship Filter</label>
                        <select id="championship" name="championship" class="form-control">
                            <option value="">All Competitions</option>
                            <option value="yes" <?php echo $championshipFilter === 'yes' ? 'selected' : ''; ?>>Championship Only</option>
                            <option value="no" <?php echo $championshipFilter === 'no' ? 'selected' : ''; ?>>Non-Championship</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_filter">Date Filter</label>
                        <select id="date_filter" name="date_filter" class="form-control">
                            <option value="">All Dates</option>
                            <option value="upcoming" <?php echo $dateFilter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="past" <?php echo $dateFilter === 'past' ? 'selected' : ''; ?>>Past</option>
                        </select>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="list.php" class="btn btn-secondary">Clear</a>
                    <a href="add.php" class="btn btn-success">Add New Competition</a>
                </div>
            </form>
        </div>

        <!-- Competitions List -->
        <div class="card">
            <div class="card-header">Competitions (<?php echo count($competitions); ?> found)</div>
            <div class="table-container">
                <table class="table-striped">
                    <thead>
                        <tr>
                            <th>Competition Name</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Participants</th>
                            <th>Championship</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($competitions) > 0): ?>
                            <?php foreach ($competitions as $comp): ?>
                                <?php
                                $isUpcoming = strtotime($comp['competition_date']) >= strtotime('today');
                                $isPast = strtotime($comp['competition_date']) < strtotime('today');
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="view.php?id=<?php echo $comp['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <?php echo htmlspecialchars($comp['name']); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($comp['competition_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($comp['location']); ?></td>
                                    <td><?php echo $comp['participant_count']; ?> archers</td>
                                    <td>
                                        <?php if ($comp['is_club_championship']): ?>
                                            <span class="badge badge-yellow">Championship</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isUpcoming): ?>
                                            <span class="badge badge-success">Upcoming</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $comp['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No competitions found matching your criteria</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

