<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Logged-in user info
$stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();
$stmt->close();

// ----------------------
// User Engagement Over Time
// ----------------------
$userId = $_SESSION['user_id'];

$activityQuery = $conn->prepare("
    SELECT DATE(attempted_on) as day, COUNT(*) as quizzes, 
           ROUND(SUM(correct_answers)/SUM(total_questions)*100,2) as accuracy
    FROM quiz_attempts
    WHERE user_id = ?
    GROUP BY day
    ORDER BY day ASC
");
$activityQuery->bind_param("i", $userId);
$activityQuery->execute();
$result = $activityQuery->get_result();

$days = [];
$quizzesPerDay = [];
$accuracyPerDay = [];

while($row = $result->fetch_assoc()){
    $days[] = $row['day'];
    $quizzesPerDay[] = (int)$row['quizzes'];
    $accuracyPerDay[] = (float)$row['accuracy'];
}
$activityQuery->close();

// ----------------------
// Subject-wise Analysis
// ----------------------
$subjectQuery = $conn->prepare("
    SELECT subject, COUNT(*) as total_attempts,
           ROUND(SUM(correct_answers)/SUM(total_questions)*100,2) as accuracy
    FROM quiz_attempts
    WHERE user_id = ?
    GROUP BY subject
");
$subjectQuery->bind_param("i", $userId);
$subjectQuery->execute();
$result = $subjectQuery->get_result();

$subjects = [];
$subjectAttempts = [];
$subjectAccuracy = [];

while($row = $result->fetch_assoc()){
    $subjects[] = $row['subject'];
    $subjectAttempts[] = (int)$row['total_attempts'];
    $subjectAccuracy[] = (float)$row['accuracy'];
}
$subjectQuery->close();

// ----------------------
// Top Active Users (7 days)
// ----------------------
$leaderboard = [];
$lbQuery = $conn->query("
    SELECT u.username, u.profile_pic, COUNT(a.id) as quizzes
    FROM users u
    LEFT JOIN quiz_attempts a ON u.id = a.user_id AND a.attempted_on >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY u.id
    ORDER BY quizzes DESC
    LIMIT 10
");
while($row = $lbQuery->fetch_assoc()){
    $leaderboard[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background: #1e1e2f; color: #fff; }
.card { border-radius: 15px; }
.avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
.table thead { background: #292946; }
.table tbody tr:nth-child(odd) { background: #2d2d58; }
.table tbody tr:nth-child(even) { background: #38386b; }
.table tbody tr.highlight { background: #ff6f61 !important; color: #fff; font-weight: bold; }
.badge { font-size: 0.8rem; }
</style>
</head>
<body>
<div class="container mt-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="uploads/<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" class="avatar me-3">
                <h4>Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h4>
            </div>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <!-- Engagement Chart -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow-lg p-4">
                <h4 class="mb-3">Your Engagement Over Time</h4>
                <canvas id="engagementChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Subject-wise Analysis Chart -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow-lg p-4">
                <h4 class="mb-3">Your Performance by Subject</h4>
                <canvas id="subjectChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow-lg p-4">
                <h4 class="mb-3">Top Active Users (Last 7 Days)</h4>
                <div class="table-responsive">
                    <table class="table table-borderless text-white align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Avatar</th>
                                <th>Username</th>
                                <th>Quizzes Attempted</th>
                                <th>Badges</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($leaderboard as $index => $user): 
                                $highlight = ($user['username'] == $currentUser['username']) ? "highlight" : "";
                                $badges = [];
                                if($user['quizzes'] >= 3) $badges[] = "⭐ Rising Star";
                                if($user['quizzes'] >= 5) $badges[] = "🏆 Quiz Master";
                                if($user['quizzes'] >= 10) $badges[] = "🔥 Champion";
                            ?>
                                <tr class="<?php echo $highlight; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="avatar"></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo $user['quizzes']; ?></td>
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

<script>
// Engagement Chart
new Chart(document.getElementById('engagementChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($days); ?>,
        datasets: [
            {
                label: 'Quizzes Attempted',
                data: <?php echo json_encode($quizzesPerDay); ?>,
                borderColor: '#4caf50',
                backgroundColor: '#4caf50a0',
                yAxisID: 'y'
            },
            {
                label: 'Accuracy (%)',
                data: <?php echo json_encode($accuracyPerDay); ?>,
                borderColor: '#ff9800',
                backgroundColor: '#ff9800a0',
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        stacked: false,
        scales: {
            y: { type: 'linear', position: 'left', title: { display: true, text: 'Quizzes Attempted', color: '#fff' }, ticks: { color: '#fff', stepSize: 1 }, beginAtZero:true },
            y1: { type: 'linear', position: 'right', title: { display: true, text: 'Accuracy %', color: '#fff' }, ticks: { color: '#fff', stepSize: 10 }, beginAtZero:true },
            x: { ticks: { color: '#fff' } }
        },
        plugins: { legend: { labels: { color: '#fff' } } }
    }
});

// Subject-wise Chart
new Chart(document.getElementById('subjectChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($subjects); ?>,
        datasets: [
            {
                label: 'Attempts',
                data: <?php echo json_encode($subjectAttempts); ?>,
                backgroundColor: '#2196f3'
            },
            {
                label: 'Accuracy %',
                data: <?php echo json_encode($subjectAccuracy); ?>,
                backgroundColor: '#ffeb3b'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { labels: { color: '#fff' } } },
        scales: { y: { ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } }
    }
});
</script>
</body>
</html>
