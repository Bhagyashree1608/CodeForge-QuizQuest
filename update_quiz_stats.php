<?php
session_start();
$data = json_decode(file_get_contents('php://input'), true);

if($data){
    $_SESSION['quiz']['xp'] = $data['xp'];
    $_SESSION['quiz']['streak'] = $data['streak'];
    $_SESSION['quiz']['lives'] = $data['lives'];
    $_SESSION['quiz']['correct_count'] = $data['correct_count'];
    $_SESSION['quiz']['wrong_count'] = $data['wrong_count'];
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'error']);
}
