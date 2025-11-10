<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

$competitionId = $_GET['id'] ?? 0;

// Get competition details
$stmt = $pdo->prepare("SELECT * FROM competition WHERE id = :id");
$stmt->execute([':id' => $competitionId]);
$competition = $stmt->fetch();

if (!$competition) {
    header('Location: list.php');
    exit;
}

// Get participants and their scores
$participantsStmt = $pdo->prepare("
    SELECT 
        reg.id as registration_id,
        a.id as archer_id,
        a.first_name,
        a.last_name,
        a.gender,
        a.date_of_birth,
        reg.equipment_used,
        r.name as round_name,
        COALESCE(SUM(s.end_total), 0) as total_score,
        COUNT(s.id) as ends_shot
    FROM registration reg
    JOIN archer a ON reg.archer_id = a.id
    LEFT JOIN round r ON reg.round_id = r.id
    LEFT JOIN score s ON reg.id = s.registration_id
    WHERE reg.competition_id = :competition_id
    GROUP BY reg.id
    ORDER BY total_score DESC
");
$participantsStmt->execute([':competition_id' => $competitionId]);
$participants = $participantsStmt->fetchAll();

$isUpcoming = strtotime($competition['competition_date']) >= strtotime('today');
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($competition['name']); ?> - ArchDB</title>
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
        <div class="flex justify-between align-center">
            <h2 class="page-title"><?php echo htmlspecialchars($competition['name']); ?></h2>
            <a href="list.php" class="btn btn-secondary">Back to List</a>
        </div>

        <!-- Competition Details -->
        <div class="card">
            <div class="card-header">Competition Information</div>
            <table>
                <tr>
                    <td style="width: 200px;"><strong>Competition Name:</strong></td>
                    <td><?php echo htmlspecialchars($competition['name']); ?></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td><?php echo date('l, d F Y', strtotime($competition['competition_date'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Location:</strong></td>
                    <td><?php echo htmlspecialchars($competition['location']); ?></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <?php if ($isUpcoming): ?>
                            <span class="badge badge-success">Upcoming</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>
                        <?php if ($competition['is_club_championship']): ?>
                            <span class="badge badge-yellow">Club Championship</span>
                        <?php else: ?>
                            <span class="badge badge-info">Regular Competition</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Total Participants:</strong></td>
                    <td><?php echo count($participants); ?> archers</td>
                </tr>
                <?php if ($competition['description']): ?>
                <tr>
                    <td><strong>Description:</strong></td>
                    <td><?php echo nl2br(htmlspecialchars($competition['description'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Participants and Results -->
        <div class="card">
            <div class="card-header">
                <?php echo $isUpcoming ? 'Registered Participants' : 'Competition Results'; ?>
            </div>
            
            <?php if (count($participants) > 0): ?>
                <div class="table-container">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Archer Name</th>
                                <th>Category</th>
                                <th>Equipment</th>
                                <th>Round</th>
                                <th>Total Score</th>
                                <th>Ends Shot</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            $prevScore = null;
                            $displayRank = 1;
                            foreach ($participants as $participant): 
                                $category = getAgeCategory($participant['date_of_birth'], $participant['gender']);
                                $equipment = formatEquipment($participant['equipment_used']);
                                
                                // Handle tied scores
                                if ($prevScore !== null && $participant['total_score'] != $prevScore) {
                                    $displayRank = $rank;
                                }
                                $prevScore = $participant['total_score'];
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!$isUpcoming && $participant['total_score'] > 0): ?>
                                            <strong style="font-size: 1.2rem;">
                                                <?php 
                                                if ($displayRank == 1) echo '<span style="color: #ffff00; background: #000; padding: 0.25rem 0.5rem; border-radius: 3px;">🥇 1st</span>';
                                                elseif ($displayRank == 2) echo '<span style="color: #c0c0c0;">🥈 2nd</span>';
                                                elseif ($displayRank == 3) echo '<span style="color: #cd7f32;">🥉 3rd</span>';
                                                else echo $displayRank . 'th';
                                                ?>
                                            </strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../archers/view.php?id=<?php echo $participant['archer_id']; ?>" style="color: #0004ff; text-decoration: none;">
                                            <strong><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td><span class="badge badge-blue"><?php echo $equipment; ?></span></td>
                                    <td><?php echo htmlspecialchars($participant['round_name'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php if ($participant['total_score'] > 0): ?>
                                            <strong style="color: #0004ff; font-size: 1.2rem;"><?php echo $participant['total_score']; ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">Not scored yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $participant['ends_shot']; ?> ends</td>
                                    <td>
                                        <?php if ($participant['total_score'] > 0): ?>
                                            <a href="../scores/view.php?registration_id=<?php echo $participant['registration_id']; ?>" class="btn btn-primary btn-sm">View Scorecard</a>
                                        <?php else: ?>
                                            <a href="../scores/add.php?registration_id=<?php echo $participant['registration_id']; ?>" class="btn btn-success btn-sm">Record Score</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center" style="padding: 2rem;">No participants registered yet.</p>
                <div class="text-center">
                    <a href="register.php" class="btn btn-success">Register Participants</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Quick Actions</div>
            <div class="btn-group">
                <a href="register.php" class="btn btn-success">Register More Participants</a>
                <?php if (count($participants) > 0): ?>
                    <a href="../scores/add.php?competition_id=<?php echo $competitionId; ?>" class="btn btn-primary">Record Scores</a>
                <?php endif; ?>
                <a href="list.php" class="btn btn-secondary">View All Competitions</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 ArchDB - Archery Score Recording System</p>
        <p>Developed by: Aiden Dinh, Thien Anh Doan, Dat Phong Luu, Vo Huy, Nguy Do Gia Huy</p>
    </footer>
</body>
</html>

