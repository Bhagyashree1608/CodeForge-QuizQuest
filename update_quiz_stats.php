<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if(isset($data['xp'], $data['streak'], $data['lives'])){
    $xp = (int)$data['xp'];
    $streak = (int)$data['streak'];
    $lives = (int)$data['lives'];

    // Update user stats in DB
    $stmt = $conn->prepare("UPDATE users SET xp=?, streak=?, lives=? WHERE id=?");
    $stmt->bind_param("iiii", $xp, $streak, $lives, $_SESSION['user_id']);
    if($stmt->execute()){
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid data']);
}
?>
