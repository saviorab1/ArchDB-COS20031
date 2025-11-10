<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

// Get club records for all rounds
$clubRecordsStmt = $pdo->query("
    SELECT 
        r.id as round_id,
        r.name as round_name,
        r.round_type,
        a.id as archer_id,
        a.first_name,
        a.last_name,
        a.gender,
        a.date_of_birth,
        MAX(total_score) as record_score,
        score_date,
        equipment_category
    FROM (
        SELECT 
            s.archer_id,
            s.round_id,
            SUM(s.end_total) as total_score,
            s.score_date,
            COALESCE(reg.equipment_used, a.equipment) as equipment_category
        FROM score s
        JOIN archer a ON s.archer_id = a.id
        LEFT JOIN registration reg ON s.registration_id = reg.id
        GROUP BY s.archer_id, s.round_id, COALESCE(s.registration_id, CONCAT('practice_', s.score_date))
    ) as scores
    JOIN round r ON scores.round_id = r.id
    JOIN archer a ON scores.archer_id = a.id
    WHERE total_score = (
        SELECT MAX(total_score_inner)
        FROM (
            SELECT 
                SUM(end_total) as total_score_inner
            FROM score s2
            WHERE s2.round_id = r.id
            GROUP BY s2.archer_id, COALESCE(s2.registration_id, CONCAT('practice_', s2.score_date))
        ) as max_scores
    )
    GROUP BY r.id
    ORDER BY record_score DESC, r.name
");
$clubRecords = $clubRecordsStmt->fetchAll();

// Get overall statistics
$totalRecords = count($clubRecords);
$highestRecord = $totalRecords > 0 ? $clubRecords[0]['record_score'] : 0;

// Get most records by archer
$recordHoldersStmt = $pdo->query("
    SELECT 
        a.id,
        a.first_name,
        a.last_name,
        COUNT(*) as record_count
    FROM (
        SELECT 
            r.id as round_id,
            s.archer_id,
            MAX(total_score) as record_score
        FROM (
            SELECT 
                archer_id,
                round_id,
                SUM(end_total) as total_score
            FROM score
            GROUP BY archer_id, round_id, COALESCE(registration_id, CONCAT('practice_', score_date))
        ) as scores
        JOIN round r ON scores.round_id = r.id
        WHERE total_score = (
            SELECT MAX(total_score_inner)
            FROM (
                SELECT 
                    SUM(end_total) as total_score_inner
                FROM score s2
                WHERE s2.round_id = r.id
                GROUP BY s2.archer_id, COALESCE(s2.registration_id, CONCAT('practice_', s2.score_date))
            ) as max_scores
        )
        GROUP BY r.id
    ) as records
    JOIN archer a ON records.archer_id = a.id
    GROUP BY a.id
    ORDER BY record_count DESC
    LIMIT 5
");
$recordHolders = $recordHoldersStmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Records - ArchDB</title>
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
        <h2 class="page-title">Club Records</h2>

        <!-- Statistics Overview -->
        <div class="grid grid-3">
            <div class="stat-box">
                <div class="stat-value"><?php echo $totalRecords; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $highestRecord; ?></div>
                <div class="stat-label">Highest Score</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo count($recordHolders); ?></div>
                <div class="stat-label">Record Holders</div>
            </div>
        </div>

        <!-- Top Record Holders -->
        <?php if (count($recordHolders) > 0): ?>
            <div class="card">
                <div class="card-header">Top Record Holders</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Archer</th>
                                <th>Number of Records</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($recordHolders as $holder): 
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
                                        <a href="../archers/view.php?id=<?php echo $holder['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($holder['first_name'] . ' ' . $holder['last_name']); ?></strong>
                                        </a>
                                    </td>
                                    <td><strong style="color: #0004ff; font-size: 1.2rem;"><?php echo $holder['record_count']; ?> records</strong></td>
                                    <td>
                                        <a href="../archers/view.php?id=<?php echo $holder['id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
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

        <!-- All Club Records -->
        <?php if (count($clubRecords) > 0): ?>
            <div class="card">
                <div class="card-header">Club Records by Round</div>
                <div class="table-container">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th>Round Name</th>
                                <th>Type</th>
                                <th>Record Score</th>
                                <th>Record Holder</th>
                                <th>Category</th>
                                <th>Equipment</th>
                                <th>Date Achieved</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clubRecords as $record): ?>
                                <tr>
                                    <td>
                                        <a href="../rounds/view.php?id=<?php echo $record['round_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($record['round_name']); ?></strong>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($record['round_type']); ?></span></td>
                                    <td>
                                        <strong style="color: #ff0000; font-size: 1.3rem;">
                                            <?php echo $record['record_score']; ?> pts
                                        </strong>
                                    </td>
                                    <td>
                                        <a href="../archers/view.php?id=<?php echo $record['archer_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo getAgeCategory($record['date_of_birth'], $record['gender']); ?></td>
                                    <td><span class="badge badge-yellow"><?php echo formatEquipment($record['equipment_category']); ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($record['score_date'])); ?></td>
                                    <td>
                                        <a href="../rounds/view.php?id=<?php echo $record['round_id']; ?>" class="btn btn-primary btn-sm">View Round</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No club records available yet. <a href="../scores/add.php" style="color: #0004ff;">Start recording scores</a> to establish records!
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

