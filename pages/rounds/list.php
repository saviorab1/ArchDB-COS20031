<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

// Get all rounds with their ranges
$roundsStmt = $pdo->query("
    SELECT 
        r.id,
        r.name,
        r.round_type,
        r.description,
        COUNT(rr.id) as range_count,
        SUM(rr.number_of_ends) as total_ends
    FROM round r
    LEFT JOIN round_range rr ON r.id = rr.round_id
    GROUP BY r.id
    ORDER BY r.name
");
$rounds = $roundsStmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Rounds - ArchDB</title>
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
            <h2 class="page-title">All Rounds</h2>
            <a href="add.php" class="btn btn-success">Add New Round</a>
        </div>

        <!-- Rounds List -->
        <div class="card">
            <div class="card-header">Available Rounds (<?php echo count($rounds); ?>)</div>
            <div class="table-container">
                <table class="table-striped">
                    <thead>
                        <tr>
                            <th>Round Name</th>
                            <th>Type</th>
                            <th>Number of Ranges</th>
                            <th>Total Ends</th>
                            <th>Total Arrows</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rounds) > 0): ?>
                            <?php foreach ($rounds as $round): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="view.php?id=<?php echo $round['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <?php echo htmlspecialchars($round['name']); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($round['round_type']); ?></span></td>
                                    <td><?php echo $round['range_count']; ?> ranges</td>
                                    <td><?php echo $round['total_ends'] ?? 0; ?> ends</td>
                                    <td><?php echo ($round['total_ends'] ?? 0) * 6; ?> arrows</td>
                                    <td><?php echo htmlspecialchars(substr($round['description'] ?? '', 0, 50)) . (strlen($round['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $round['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No rounds found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Information Box -->
        <div class="card">
            <div class="card-header">About Rounds</div>
            <p>A round defines the format of an archery competition or practice session. Each round consists of one or more ranges, where each range specifies:</p>
            <ul style="margin-left: 2rem; line-height: 2;">
                <li><strong>Distance:</strong> How far the target is placed (e.g., 70m, 60m, 50m)</li>
                <li><strong>Number of Ends:</strong> How many sets of 6 arrows are shot at this distance</li>
                <li><strong>Target Face Size:</strong> The diameter of the target face (80cm, 122cm, etc.)</li>
            </ul>
            <p style="margin-top: 1rem;">Common rounds include WA 900, WA 720, Olympic Round, and various indoor rounds.</p>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

