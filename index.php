<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Fetch all users for leaderboard
$users = [];
$res = $conn->query("SELECT username, profile_pic, xp, level FROM users ORDER BY xp DESC");
while($row = $res->fetch_assoc()){
    $users[] = $row;
}

// Fetch subjects dynamically
$subjects = [];
$subResult = $conn->query("SELECT DISTINCT subject FROM questions");
while($row = $subResult->fetch_assoc()){
    $subjects[] = $row['subject'];
}

// Logged-in user info
$stmt = $conn->prepare("SELECT username, profile_pic, xp, level FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gamified Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
/* Body and background */
body {
    background: #3b2e2e url('images/leather-texture.jpg') repeat; /* leather texture image */
    color: #fff;
    font-family: 'Arial', sans-serif;
}

/* Sidebar */
.sidebar {
    height: 100vh;
    background: rgba(30, 30, 47, 0.95);
    color: #fff;
    padding-top: 20px;
}
.sidebar a {
    color: #fff;
    display: block;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 5px;
}
.sidebar a:hover {
    background: #5a4b4b;
}

/* Avatar */
.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

/* Cards */
.card {
    border-radius: 15px;
    background: rgba(0, 0, 0, 0.5);
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}

/* Progress bar */
.progress-bar {
    transition: width 0.5s;
    font-weight: bold;
}

/* Badges */
.badge {
    font-size: 0.75rem;
}

/* Table */
.table thead {
    background: rgba(0,0,0,0.6);
}
.table tbody tr:nth-child(odd) {
    background: rgba(0,0,0,0.3);
}
.table tbody tr:nth-child(even) {
    background: rgba(0,0,0,0.4);
}
.table tbody tr.highlight {
    background: #ff6f61 !important;
    color: #fff;
    font-weight: bold;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sidebar { height: auto; }
    .profile-pic { width: 35px; height: 35px; }
    .table td, .table th { font-size: 0.85rem; }
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-flex flex-column">
            <div class="d-flex align-items-center mb-3 px-2">
                <img src="uploads/<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="avatar" class="profile-pic me-2">
                <div>
                    <div><?php echo htmlspecialchars($currentUser['username']); ?></div>
                    <small>Level <?php echo $currentUser['level']; ?></small>
                </div>
            </div>
            <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="quiz.php"><i class="bi bi-journal-text me-2"></i> Start Quiz</a>
            <a href="#"><i class="bi bi-award me-2"></i> Achievements</a>
            <a href="#"><i class="bi bi-gear me-2"></i> Settings</a>
             <a href="analytics.php"><i class="bi bi-box-arrow-right me-2"></i> Analysis</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <h3 class="mb-3">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h3>

            <!-- XP Bar -->
            <div class="mb-3">
                <p>XP: <?php echo $currentUser['xp']; ?> | Level: <?php echo $currentUser['level']; ?></p>
                <div class="progress" style="height:25px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width:<?php echo ($currentUser['xp'] % 100); ?>%">
                         <?php echo ($currentUser['xp'] % 100); ?> / 100 XP
                    </div>
                </div>
            </div>

          <!-- Subject Selection Card -->
<div class="card shadow-lg p-4 mb-4 text-center">
    <h5>Select a Subject to Start Quiz</h5>
    <p>🎯 Choose your subject in a dedicated page!</p>
    <a href="subject_selection.php" class="btn btn-primary mt-2">Go to Subject Selection</a>
</div>

            <!-- Leaderboard -->
            <div class="card shadow-lg p-4">
                <h4 class="mb-3 text-white">Leaderboard</h4>
                <div class="table-responsive">
                    <table class="table table-borderless text-white align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Avatar</th>
                                <th>Username</th>
                                <th>Level</th>
                                <th>XP</th>
                                <th>Badges</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $index => $user): 
                                $progress = min(100, ($user['xp'] % 100));
                                $highlight = ($user['username'] == $currentUser['username']) ? "highlight" : "";
                                $badges = [];
                                if($user['xp'] >= 50) $badges[] = "⭐ Rising Star";
                                if($user['xp'] >= 100) $badges[] = "🏆 Quiz Master";
                                if($user['xp'] >= 200) $badges[] = "🔥 Champion";
                            ?>
                                <tr class="<?php echo $highlight; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="profile-pic"></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo $user['level']; ?></td>
                                    <td style="width:250px;">
                                        <div class="progress" style="height:20px;">
                                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: <?php echo $progress; ?>%;">
                                                <?php echo $user['xp']; ?> / <?php echo $user['level']*100; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php foreach($badges as $b): ?>
                                            <span class="badge bg-warning text-dark me-1 mb-1"><?php echo $b; ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
