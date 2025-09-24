<?php
require 'db.php';

if(!isset($_POST['subject'])) die("Subject missing");
if(!isset($_FILES['packfile'])) die("File missing");

$subject_code = $_POST['subject'];

// Find subject ID
$stmt = $conn->prepare("SELECT id FROM subjects WHERE code=?");
$stmt->bind_param("s", $subject_code);
$stmt->execute();
$stmt->bind_result($subject_id);
if(!$stmt->fetch()) { die("Subject not found"); }
$stmt->close();

// Upload JSON file
$filename = basename($_FILES['packfile']['name']);
$target = "../data/".time()."_".$filename;

if(move_uploaded_file($_FILES['packfile']['tmp_name'], $target)){
    $title = pathinfo($filename, PATHINFO_FILENAME);
    $stmt = $conn->prepare("INSERT INTO question_packs(subject_id, title, json_file) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $subject_id, $title, $target);
    $stmt->execute();
    $stmt->close();
    echo "✅ Pack uploaded successfully!";
} else {
    echo "❌ Upload failed.";
}
?>
