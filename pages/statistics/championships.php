<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

// Get all championship competitions
$championshipsStmt = $pdo->query("
    SELECT 
        c.id,
        c.name,
        c.competition_date,
        c.location,
        COUNT(DISTINCT reg.archer_id) as participant_count,
        MAX(total_score) as highest_score,
        winner.first_name as winner_first_name,
        winner.last_name as winner_last_name,
        winner.id as winner_id
    FROM competition c
    LEFT JOIN registration reg ON c.id = reg.competition_id
    LEFT JOIN (
        SELECT 
            reg2.competition_id,
            reg2.archer_id,
            SUM(s.end_total) as total_score
        FROM registration reg2
        LEFT JOIN score s ON reg2.id = s.registration_id
        GROUP BY reg2.id
    ) scores ON c.id = scores.competition_id
    LEFT JOIN archer winner ON scores.archer_id = winner.id
    WHERE c.is_club_championship = 1
    AND (scores.total_score IS NULL OR scores.total_score = (
        SELECT MAX(SUM(s2.end_total))
        FROM score s2
        JOIN registration reg3 ON s2.registration_id = reg3.id
        WHERE reg3.competition_id = c.id
        GROUP BY reg3.id
    ))
    GROUP BY c.id
    ORDER BY c.competition_date DESC
");
$championships = $championshipsStmt->fetchAll();

// Get championship winners statistics
$winnersStmt = $pdo->query("
    SELECT 
        a.id,
        a.first_name,
        a.last_name,
        COUNT(*) as championship_wins
    FROM (
        SELECT 
            c.id as competition_id,
            reg.archer_id,
            SUM(s.end_total) as total_score
        FROM competition c
        JOIN registration reg ON c.id = reg.competition_id
        LEFT JOIN score s ON reg.id = s.registration_id
        WHERE c.is_club_championship = 1
        GROUP BY reg.id
        HAVING total_score = (
            SELECT MAX(SUM(s2.end_total))
            FROM score s2
            JOIN registration reg2 ON s2.registration_id = reg2.id
            WHERE reg2.competition_id = c.id
            GROUP BY reg2.id
        )
    ) as wins
    JOIN archer a ON wins.archer_id = a.id
    GROUP BY a.id
    ORDER BY championship_wins DESC
    LIMIT 10
");
$winners = $winnersStmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Championships - ArchDB</title>
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
        <h2 class="page-title">Club Championships</h2>

        <!-- Statistics Overview -->
        <div class="grid grid-3">
            <div class="stat-box">
                <div class="stat-value"><?php echo count($championships); ?></div>
                <div class="stat-label">Total Championships</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo count($winners); ?></div>
                <div class="stat-label">Unique Winners</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">
                    <?php 
                    $upcomingChampionships = array_filter($championships, function($c) {
                        return strtotime($c['competition_date']) >= strtotime('today');
                    });
                    echo count($upcomingChampionships);
                    ?>
                </div>
                <div class="stat-label">Upcoming</div>
            </div>
        </div>

        <!-- Championship Winners Hall of Fame -->
        <?php if (count($winners) > 0): ?>
            <div class="card">
                <div class="card-header">Championship Winners Hall of Fame</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Archer</th>
                                <th>Championship Wins</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($winners as $winner): 
                            ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 1.2rem;">
                                            <?php 
                                            if ($rank == 1) echo '<span style="color: #ffff00; background: #000; padding: 0.25rem 0.5rem; border-radius: 3px;">🥇 1st</span>';
                                            elseif ($rank == 2) echo '<span style="color: #c0c0c0;">🥈 2nd</span>';
                                            elseif ($rank == 3) echo '<span style="color: #cd7f32;">🥉 3rd</span>';
                                            else echo $rank . 'th';
                                            ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <a href="../archers/view.php?id=<?php echo $winner['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <strong style="color: #0004ff; font-size: 1.2rem;">
                                            <?php echo $winner['championship_wins']; ?> 
                                            <?php echo $winner['championship_wins'] == 1 ? 'championship' : 'championships'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <a href="../archers/view.php?id=<?php echo $winner['id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
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
        <?php endif; ?>

        <!-- All Championships -->
        <?php if (count($championships) > 0): ?>
            <div class="card">
                <div class="card-header">Championship History</div>
                <div class="table-container">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th>Championship Name</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Participants</th>
                                <th>Winner</th>
                                <th>Winning Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($championships as $championship): ?>
                                <?php
                                $isUpcoming = strtotime($championship['competition_date']) >= strtotime('today');
                                $hasWinner = $championship['winner_id'] && $championship['highest_score'] > 0;
                                ?>
                                <tr>
                                    <td>
                                        <a href="../competitions/view.php?id=<?php echo $championship['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($championship['name']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($championship['competition_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($championship['location']); ?></td>
                                    <td><?php echo $championship['participant_count']; ?> archers</td>
                                    <td>
                                        <?php if ($hasWinner): ?>
                                            <a href="../archers/view.php?id=<?php echo $championship['winner_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <strong><?php echo htmlspecialchars($championship['winner_first_name'] . ' ' . $championship['winner_last_name']); ?></strong>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">TBD</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasWinner): ?>
                                            <strong style="color: #ff0000; font-size: 1.1rem;"><?php echo $championship['highest_score']; ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
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
                                        <a href="../competitions/view.php?id=<?php echo $championship['id']; ?>" class="btn btn-primary btn-sm">View Results</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No club championships available yet. <a href="../competitions/add.php" style="color: #0004ff;">Add a championship competition</a>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="../competitions/add.php" class="btn btn-success">Create New Championship</a>
                <a href="../competitions/list.php" class="btn btn-primary">View All Competitions</a>
                <a href="personal-best.php" class="btn btn-secondary">View Personal Bests</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

