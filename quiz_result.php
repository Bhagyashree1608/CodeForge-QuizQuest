<?php
session_start();

if(!isset($_SESSION['quiz'])){
    header("Location: quiz.php");
    exit;
}

// Fetch quiz stats
$quiz = $_SESSION['quiz'];
$totalQuestions = count($quiz['questions'] ?? []);
$correct = $quiz['correct_count'] ?? 0;
$wrong = $quiz['wrong_count'] ?? 0;
$xp = $quiz['xp'] ?? 0;
$streak = $quiz['streak'] ?? 0;
$lives = $quiz['lives'] ?? 0;

// Clear session quiz to allow new quiz
unset($_SESSION['quiz']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f8; }
.card { border-radius: 15px; margin-top: 50px; padding: 30px; text-align:center; }
h2 { margin-bottom: 30px; }
.stats { font-size: 1.2rem; margin: 10px 0; }
</style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <h2>Quiz Result</h2>
                <div class="stats"><strong>Total Questions:</strong> <?php echo $totalQuestions; ?></div>
                <div class="stats text-success"><strong>Correct:</strong> <?php echo $correct; ?></div>
                <div class="stats text-danger"><strong>Wrong:</strong> <?php echo $wrong; ?></div>
                <div class="stats"><strong>XP Earned:</strong> <?php echo $xp; ?></div>
                <div class="stats"><strong>Max Streak:</strong> <?php echo $streak; ?></div>
                <div class="stats"><strong>Lives Remaining:</strong> <?php echo str_repeat('❤', $lives); ?></div>
                <a href="quiz.php" class="btn btn-primary mt-3">Take Another Quiz</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
