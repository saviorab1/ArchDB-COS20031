<!DOCTYPE html>
<?php
require_once '../../config/database.php';
$pdo = getDBConnection();

// Handle search
$search = $_GET['search'] ?? '';
$equipmentFilter = $_GET['equipment'] ?? '';

$sql = "SELECT * FROM archer WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(:search) OR LOWER(email) LIKE LOWER(:search))";
    $params[':search'] = "%$search%";
}

if ($equipmentFilter) {
    $sql .= " AND equipment = :equipment";
    $params[':equipment'] = $equipmentFilter;
}

$sql .= " ORDER BY last_name, first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$archers = $stmt->fetchAll();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Archers - ArchDB</title>
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
        <h2 class="page-title">All Archers</h2>

        <!-- Search and Filter -->
        <div class="card">
            <form method="GET" action="list.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="search">Search by Name or Email</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Enter name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="equipment">Filter by Equipment</label>
                        <select id="equipment" name="equipment" class="form-control">
                            <option value="">All Equipment</option>
                            <option value="RECURVE" <?php echo $equipmentFilter === 'RECURVE' ? 'selected' : ''; ?>>Recurve</option>
                            <option value="COMPOUND" <?php echo $equipmentFilter === 'COMPOUND' ? 'selected' : ''; ?>>Compound</option>
                            <option value="RECURVE_BAREBOW" <?php echo $equipmentFilter === 'RECURVE_BAREBOW' ? 'selected' : ''; ?>>Recurve Barebow</option>
                            <option value="COMPOUND_BAREBOW" <?php echo $equipmentFilter === 'COMPOUND_BAREBOW' ? 'selected' : ''; ?>>Compound Barebow</option>
                            <option value="LONGBOW" <?php echo $equipmentFilter === 'LONGBOW' ? 'selected' : ''; ?>>Longbow</option>
                        </select>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="list.php" class="btn btn-secondary">Clear</a>
                    <a href="add.php" class="btn btn-success">Add New Archer</a>
                </div>
            </form>
        </div>

        <!-- Archers List -->
        <div class="card">
            <div class="card-header">Registered Archers (<?php echo count($archers); ?> found)</div>
            <div class="table-container">
                <table class="table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Category</th>
                            <th>Equipment</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($archers) > 0): ?>
                            <?php foreach ($archers as $archer): ?>
                                <?php
                                $age = calculateAge($archer['date_of_birth']);
                                $category = getAgeCategory($archer['date_of_birth'], $archer['gender']);
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="view.php?id=<?php echo $archer['id']; ?>" style="color: #0004ff; text-decoration: none;">
                                                <?php echo htmlspecialchars($archer['first_name'] . ' ' . $archer['last_name']); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo $archer['gender'] == 'M' ? 'Male' : 'Female'; ?></td>
                                    <td><?php echo $age; ?></td>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td><span class="badge badge-blue"><?php echo formatEquipment($archer['equipment']); ?></span></td>
                                    <td><?php echo htmlspecialchars($archer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($archer['phone_number']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $archer['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No archers found matching your criteria</td>
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

