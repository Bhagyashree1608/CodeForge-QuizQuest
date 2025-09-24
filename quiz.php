<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Subject selection (default: Coding)
$subject = isset($_GET['subject']) ? $_GET['subject'] : 'Coding';

// Initialize quiz session if not exists
if(!isset($_SESSION['quiz']) || empty($_SESSION['quiz']['questions'])){
    // Fetch questions from your new `questions` table
    $stmt = $conn->prepare("SELECT id, subject, question_text, option1, option2, option3, option4, correct_option 
                            FROM questions WHERE subject=? ORDER BY RAND() LIMIT 10");
    $stmt->bind_param("s", $subject);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = [];
    while($row = $result->fetch_assoc()){
        $questions[] = $row;
    }
    $stmt->close();

    if(empty($questions)){
        die("No questions available for this subject.");
    }

    $_SESSION['quiz'] = [
        'questions' => $questions,
        'current' => 0,
        'xp' => 0,
        'lives' => 3,
        'streak' => 0
    ];
}

// Current question
$currentIndex = $_SESSION['quiz']['current'];
$totalQuestions = count($_SESSION['quiz']['questions']);
$currentQuestion = $_SESSION['quiz']['questions'][$currentIndex];

// Next level XP
$level = floor($_SESSION['quiz']['xp'] / 100) + 1;
$nextLevelXP = $level * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gamified Quiz</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f8; }
.card { border-radius: 15px; transition: transform 0.3s; }
.card:hover { transform: scale(1.02); }
.progress-bar { transition: width 0.5s; font-weight: bold; }
.timer { font-weight: bold; font-size: 1.1em; margin-bottom:10px; }
.option { cursor: pointer; transition: all 0.2s; padding:10px; border-radius:8px; margin:5px 0; background:#eee; }
.option.correct { background:#a6e6a6 !important; }
.option.wrong { background:#f8a6a6 !important; }
.option:hover { background:#ddd; }
#nextBtn { display:none; margin-top:15px; }
</style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg p-4">

                <!-- User Stats -->
                <div class="d-flex justify-content-between mb-3">
                    <div>Lives: <span id="lives"><?php echo str_repeat('❤', $_SESSION['quiz']['lives']); ?></span></div>
                    <div>Streak: <span id="streak"><?php echo $_SESSION['quiz']['streak']; ?></span></div>
                    <div>XP: <span id="xp"><?php echo $_SESSION['quiz']['xp']; ?></span></div>
                </div>

                <!-- XP Bar -->
                <div class="progress mb-3" style="height: 25px;">
                    <div id="xp-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                         role="progressbar" style="width:0%">0 / <?php echo $nextLevelXP; ?> XP</div>
                </div>

                <!-- Badges -->
                <div id="streak-badges" class="d-flex flex-wrap mb-4"></div>

                <!-- Timer -->
                <div class="timer" id="timer">Time left: 30s</div>

                <!-- Question -->
                <h5 id="question-text"><?php echo ($currentIndex+1).'. '.$currentQuestion['question_text']; ?></h5>

                <!-- Options -->
                <div id="options-container">
                    <?php for($i=1;$i<=4;$i++): ?>
                        <div class="option" data-value="<?php echo $i; ?>"><?php echo $currentQuestion['option'.$i]; ?></div>
                    <?php endfor; ?>
                </div>

                <button class="btn btn-primary" id="nextBtn">Next</button>

            </div>
        </div>
    </div>
</div>

<!-- Sounds -->
<audio id="correct-sound" src="sounds/correct.mp3"></audio>
<audio id="wrong-sound" src="sounds/wrong.mp3"></audio>

<script>
let quizData = <?php echo json_encode($_SESSION['quiz']); ?>;
let currentIndex = <?php echo $currentIndex; ?>;
let totalQuestions = <?php echo $totalQuestions; ?>;
let lives = <?php echo $_SESSION['quiz']['lives']; ?>;
let streak = <?php echo $_SESSION['quiz']['streak']; ?>;
let xp = <?php echo $_SESSION['quiz']['xp']; ?>;
let nextLevelXP = <?php echo $nextLevelXP; ?>;
let timerDuration = 30;
let timer;

const correctSound = document.getElementById('correct-sound');
const wrongSound = document.getElementById('wrong-sound');

function startTimer() {
    let timeLeft = timerDuration;
    const timerEl = document.getElementById('timer');
    timerEl.textContent = `Time left: ${timeLeft}s`;
    timer = setInterval(() => {
        timeLeft--;
        timerEl.textContent = `Time left: ${timeLeft}s`;
        if(timeLeft <= 0){
            clearInterval(timer);
            alert("Time's up! Moving to next question.");
            nextQuestion();
        }
    }, 1000);
}

function updateStats() {
    document.getElementById('lives').innerText = '❤'.repeat(lives);
    document.getElementById('streak').innerText = streak;
    document.getElementById('xp').innerText = xp;
}

function saveProgress() {
    fetch('update_quiz_stats.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ xp: xp, streak: streak, lives: lives })
    })
    .then(res => res.json())
    .then(data => console.log('Progress saved', data))
    .catch(err => console.error('Error saving progress', err));
}

function addXPAnimation() {
    const bar = document.getElementById('xp-bar');
    let width = parseInt(bar.style.width) || 0;
    let target = Math.min(100, (xp/nextLevelXP)*100);
    const anim = setInterval(() => {
        if(width >= target) clearInterval(anim);
        else {
            width++;
            bar.style.width = width + '%';
            bar.innerText = Math.round((width/100)*nextLevelXP) + ' / ' + nextLevelXP + ' XP';
        }
    }, 20);
}

function updateBadges() {
    const container = document.getElementById('streak-badges');
    container.innerHTML = '';
    if(streak >= 5){
        const span = document.createElement('span');
        span.className = "badge bg-warning text-dark me-2 mb-2";
        span.innerText = "⭐ Rising Star";
        container.appendChild(span);
    }
    if(streak >= 10){
        const span = document.createElement('span');
        span.className = "badge bg-primary me-2 mb-2";
        span.innerText = "🏆 Quiz Master";
        container.appendChild(span);
    }
    if(streak >= 20){
        const span = document.createElement('span');
        span.className = "badge bg-danger me-2 mb-2";
        span.innerText = "🔥 Champion";
        container.appendChild(span);
    }
}

function handleOptionClick(optDiv, q) {
    clearInterval(timer);
    const value = optDiv.dataset.value;
    const correct = q.correct_option;
    const options = document.querySelectorAll('.option');
    options.forEach(o => o.style.pointerEvents='none');

    if(value == correct){
        optDiv.classList.add('correct');
        correctSound.play();
        xp += 10;
        streak++;
    } else {
        optDiv.classList.add('wrong');
        wrongSound.play();
        streak = 0;
        lives--;
        options.forEach(o => { if(o.dataset.value == correct) o.classList.add('correct'); });
    }
    updateStats();
    addXPAnimation();
    updateBadges();

    saveProgress(); // <-- Save in DB after each answer
    document.getElementById('nextBtn').style.display='inline-block';
}

function loadQuestion() {
    if(currentIndex >= totalQuestions || lives <= 0){
        alert("Quiz Over! Reloading page...");
        location.reload();
        return;
    }

    const q = quizData.questions[currentIndex];
    document.getElementById('question-text').innerText = (currentIndex+1)+'. '+q.question_text;
    const optionsContainer = document.getElementById('options-container');
    optionsContainer.innerHTML = '';
    for(let i=1;i<=4;i++){
        const optDiv = document.createElement('div');
        optDiv.className='option';
        optDiv.dataset.value=i;
        optDiv.innerText = q['option'+i];
        optionsContainer.appendChild(optDiv);
        optDiv.addEventListener('click', () => handleOptionClick(optDiv, q));
    }
    document.getElementById('nextBtn').style.display='none';
    startTimer();
}

function nextQuestion() {
    currentIndex++;
    loadQuestion();
}

document.getElementById('nextBtn').addEventListener('click', nextQuestion);

// Initialize
window.addEventListener('load', () => {
    updateStats();
    addXPAnimation();
    updateBadges();
    loadQuestion();
});
</script>
</body>
</html>
