<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Handle first-time selection
if(isset($_POST['start_quiz'])){
    $_SESSION['quiz_subject'] = $_POST['subject'];
    $_SESSION['quiz_difficulty'] = (int)$_POST['difficulty']; // cast to int
    $_SESSION['quiz'] = null; // reset quiz
}

// Get subject and difficulty from session
$subject = $_SESSION['quiz_subject'] ?? null;
$difficulty = $_SESSION['quiz_difficulty'] ?? null;

// Fetch questions if selection exists and quiz not initialized
if($subject && $difficulty && (!isset($_SESSION['quiz']) || empty($_SESSION['quiz']['questions']))){
    $stmt = $conn->prepare("
        SELECT id, subject, question_text, option1, option2, option3, option4, correct_option, difficulty
        FROM questions
        WHERE subject=? AND difficulty=?
    ");
    $stmt->bind_param("si", $subject, $difficulty);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while($row = $result->fetch_assoc()){
        $questions[] = $row;
    }
    $stmt->close();

    if(count($questions) < 1){
        die("No questions available for $subject at selected difficulty.");
    }

    shuffle($questions); // shuffle in PHP
    $questions = array_slice($questions, 0, 10); // pick exactly 10

    $_SESSION['quiz'] = [
        'questions' => $questions,
        'current' => 0,
        'xp' => 0,
        'lives' => 3,
        'streak' => 0,
        'correct_count' => 0,
        'wrong_count' => 0
    ];
}

// Current question info
$currentIndex = $_SESSION['quiz']['current'] ?? 0;
$totalQuestions = count($_SESSION['quiz']['questions'] ?? []);
$currentQuestion = $_SESSION['quiz']['questions'][$currentIndex] ?? null;

// Next level XP
$level = floor(($_SESSION['quiz']['xp'] ?? 0) / 100) + 1;
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

                <?php if(!$subject || !$difficulty): ?>
                <!-- Subject/Difficulty Selection -->
                <h4>Select Subject & Difficulty</h4>
                <form method="post">
                    <div class="mb-3">
                        <label>Subject</label>
                        <select name="subject" class="form-control" required>
                            <option value="Coding">Coding</option>
                            <option value="Math">Math</option>
                            <option value="Science">Science</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Difficulty</label>
                        <select name="difficulty" class="form-control" required>
                            <option value="1">Low</option>
                            <option value="2">Medium</option>
                            <option value="3">High</option>
                        </select>
                    </div>
                    <button type="submit" name="start_quiz" class="btn btn-primary">Start Quiz</button>
                </form>

                <?php else: ?>
                <!-- Locked Display -->
                <div class="mb-3">
                    <strong>Subject:</strong> <?php echo $subject; ?> |
                    <strong>Difficulty:</strong> <?php echo ($difficulty==1?'Low':($difficulty==2?'Medium':'High')); ?>
                </div>

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
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
    <div id="quiz-toast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Sounds -->
<audio id="correct-sound" src="sounds/correct.mp3"></audio>
<audio id="wrong-sound" src="sounds/wrong.mp3"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if($subject && $difficulty): ?>
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
const toastEl = document.getElementById('quiz-toast');
const toastBody = document.getElementById('toast-body');
const toast = new bootstrap.Toast(toastEl);

function showBootstrapToast(msg,type='info'){
    toastBody.innerText = msg;
    toastEl.className = 'toast align-items-center text-white border-0';
    toastEl.classList.add(`bg-${type}`);
    toast.show();
}

function loadQuestion(){
    if(currentIndex>=totalQuestions || lives<=0){
        showBootstrapToast("Quiz Over! Redirecting...", "info");
        setTimeout(()=>window.location.href='quiz_result.php',1500);
        return;
    }
    const q = quizData.questions[currentIndex];
    document.getElementById('question-text').innerText = (currentIndex+1)+'. '+q.question_text;
    const optionsContainer = document.getElementById('options-container');
    optionsContainer.innerHTML='';
    for(let i=1;i<=4;i++){
        const optDiv = document.createElement('div');
        optDiv.className='option';
        optDiv.dataset.value=i;
        optDiv.innerText=q['option'+i];
        optionsContainer.appendChild(optDiv);
        optDiv.addEventListener('click',()=>handleOptionClick(optDiv,q));
    }
    document.getElementById('nextBtn').style.display='none';
    startTimer();
}

function startTimer(){
    let timeLeft = timerDuration;
    const timerEl = document.getElementById('timer');
    timerEl.textContent = `Time left: ${timeLeft}s`;
    timer = setInterval(()=>{
        timeLeft--;
        timerEl.textContent = `Time left: ${timeLeft}s`;
        if(timeLeft<=0){
            clearInterval(timer);
            showBootstrapToast("Time's up!", "warning");
            nextQuestion();
        }
    },1000);
}

function updateStats(){
    document.getElementById('lives').innerText='❤'.repeat(lives);
    document.getElementById('streak').innerText=streak;
    document.getElementById('xp').innerText=xp;
}

function addXPAnimation(){
    const bar = document.getElementById('xp-bar');
    let width = parseInt(bar.style.width)||0;
    let target = Math.min(100,(xp/nextLevelXP)*100);
    const anim = setInterval(()=>{
        if(width>=target) clearInterval(anim);
        else{
            width++;
            bar.style.width=width+'%';
            bar.innerText=Math.round((width/100)*nextLevelXP)+' / '+nextLevelXP+' XP';
        }
    },20);
}

function updateBadges(){
    const container=document.getElementById('streak-badges');
    container.innerHTML='';
    if(streak>=5){ const s=document.createElement('span'); s.className='badge bg-warning text-dark me-2 mb-2'; s.innerText='⭐ Rising Star'; container.appendChild(s);}
    if(streak>=10){ const s=document.createElement('span'); s.className='badge bg-primary me-2 mb-2'; s.innerText='🏆 Quiz Master'; container.appendChild(s);}
    if(streak>=20){ const s=document.createElement('span'); s.className='badge bg-danger me-2 mb-2'; s.innerText='🔥 Champion'; container.appendChild(s);}
}

function handleOptionClick(optDiv,q){
    clearInterval(timer);
    const value=optDiv.dataset.value;
    const correct=q.correct_option;
    document.querySelectorAll('.option').forEach(o=>o.style.pointerEvents='none');
    if(value==correct){
        optDiv.classList.add('correct');
        correctSound.play();
        xp+=10;
        streak++;
        quizData.correct_count = (quizData.correct_count||0)+1;
        showBootstrapToast("Correct!","success");
    }else{
        optDiv.classList.add('wrong');
        wrongSound.play();
        streak=0;
        lives=lives>0?lives-1:0;
        quizData.wrong_count = (quizData.wrong_count||0)+1;
        document.querySelectorAll('.option').forEach(o=>{if(o.dataset.value==correct) o.classList.add('correct');});
        showBootstrapToast("Wrong!","danger");
    }
    updateStats();
    addXPAnimation();
    updateBadges();

    fetch('update_quiz_stats.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({xp, streak, lives, correct_count:quizData.correct_count, wrong_count:quizData.wrong_count})
    });

    document.getElementById('nextBtn').style.display='inline-block';
}

function nextQuestion(){currentIndex++; loadQuestion();}
document.getElementById('nextBtn').addEventListener('click',nextQuestion);

window.addEventListener('load',()=>{
    updateStats();
    addXPAnimation();
    updateBadges();
    loadQuestion();
});

// Anti-cheat
let tabSwitchCount=0;
window.addEventListener('blur',()=>{
    tabSwitchCount++;
    if(tabSwitchCount===1) showBootstrapToast("Do not switch tabs","warning");
    else {
        streak=0;
        lives=lives>0?lives-1:0;
        updateStats();
        fetch('update_quiz_stats.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({xp, streak, lives, correct_count:quizData.correct_count, wrong_count:quizData.wrong_count})
        });
        showBootstrapToast("Cheating detected!","danger");
    }
});

['contextmenu','copy','cut','paste'].forEach(e=>document.addEventListener(e,ev=>ev.preventDefault()));
<?php endif; ?>
</script>
</body>
</html>
