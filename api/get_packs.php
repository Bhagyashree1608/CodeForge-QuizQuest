<?php
header('Content-Type: application/json');
require 'db.php';

$sql = "SELECT s.code AS id, s.name, COUNT(q.id) AS pack_count
        FROM subjects s
        LEFT JOIN question_packs q ON q.subject_id = s.id
        GROUP BY s.id";

$res = $conn->query($sql);
$subjects = [];

while ($row = $res->fetch_assoc()) {
    $subjects[] = [
        "id" => $row['id'],
        "name" => $row['name'],
        "pack_count" => $row['pack_count']
    ];
}

echo json_encode(["subjects" => $subjects]);
?>
