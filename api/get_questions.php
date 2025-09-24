<?php
header('Content-Type: application/json');
require 'db.php';

if(!isset($_GET['subject'])) die(json_encode(["error"=>"subject missing"]));

$subject_code = $_GET['subject'];

// Get subject ID
$stmt = $conn->prepare("SELECT id FROM subjects WHERE code=?");
$stmt->bind_param("s", $subject_code);
$stmt->execute();
$stmt->bind_result($subject_id);
if(!$stmt->fetch()) {
    die(json_encode(["error"=>"subject not found"]));
}
$stmt->close();

// Get latest uploaded pack for subject
$stmt = $conn->prepare("SELECT json_file FROM question_packs WHERE subject_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($json_file);
if(!$stmt->fetch()) {
    die(json_encode(["error"=>"no pack found"]));
}
$stmt->close();

// Read JSON file and return
$data = file_get_contents($json_file);
echo $data;
?>
