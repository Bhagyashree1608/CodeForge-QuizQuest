<?php
// subject_select.php – lets user pick a subject to quiz on
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QuizQuest – Select Subject</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(135deg,#6a11cb,#2575fc);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: Arial, sans-serif;
    }
    .container {
      background:#fff;
      padding:30px;
      border-radius:15px;
      box-shadow:0 10px 25px rgba(0,0,0,0.2);
      max-width:700px;
      width:100%;
    }
    .card {
      border:none;
      transition:transform 0.3s,box-shadow 0.3s;
    }
    .card:hover {
      transform:scale(1.05);
      box-shadow:0 8px 20px rgba(0,0,0,0.2);
    }
    .select-sub {
      background:#2575fc;
      color:#fff;
      border:none;
      padding:8px 12px;
      border-radius:8px;
      transition:background 0.2s;
    }
    .select-sub:hover {
      background:#6a11cb;
    }
    #openQuiz {
      background:#6a11cb;
      border:none;
      padding:10px 20px;
      border-radius:10px;
      color:#fff;
      transition:background 0.2s;
    }
    #openQuiz:hover {
      background:#2575fc;
    }
    h3 {
      text-align:center;
      margin-bottom:25px;
      font-weight:bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <h3>🎯 Choose Your Subject</h3>
    <div id="subjects" class="row gy-3"></div>
    <hr>
    <div class="mt-3 text-center">
      <button id="openQuiz" class="btn" disabled>🚀 Start Quiz</button>
    </div>
  </div>

<script>
async function fetchPacks(){
  try {
    const res = await fetch('api/get_packs.php');
    if(!res.ok) throw new Error('Failed to load subjects');
    const data = await res.json();
    renderSubjects(data);
  } catch (err) {
    alert(err);
  }
}

function renderSubjects(data){
  const container = document.getElementById('subjects');
  container.innerHTML = '';
  data.subjects.forEach(s => {
    const col = document.createElement('div');
    col.className = 'col-md-4';
    col.innerHTML = `
      <div class="card p-3 text-center">
        <h5 class="fw-bold">${s.name}</h5>
        <div class="small text-muted">${s.pack_count>0 ? s.pack_count + ' packs': 'No packs yet'}</div>
        <div class="mt-2">
          <button class="select-sub" data-sub="${s.id}">Select</button>
        </div>
      </div>`;
    container.appendChild(col);
  });

  document.querySelectorAll('.select-sub').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      const sub = e.currentTarget.dataset.sub;
      localStorage.setItem('qq_active_subject', sub);
      document.getElementById('openQuiz').disabled = false;
      document.getElementById('openQuiz').innerText = '🚀 Start Quiz: ' + sub;
    });
  });
}

document.getElementById('openQuiz').addEventListener('click', ()=>{
  window.location.href = 'quiz.php';
});

fetchPacks();
</script>
</body>
</html>