<?php
// import_all_questions.php
include 'api/db.php'; // your database connection

// Array of JSON files and their subjects
$jsonFiles = [
    'Coding' => __DIR__ . '/data/coding_sample.json',
    'Vocabulary' => __DIR__ . '/data/vocab_sample.json',
    'Finance' => __DIR__ . '/data/finance_sample.json',
    'General Aptitude' => __DIR__ . '/data/general_aptitude.json',
    'Science' => __DIR__ . '/data/reasioning.json'
];

$insertedTotal = 0;

// Prepare insert statement
$stmt = $conn->prepare("INSERT INTO questions (subject, question_text, option1, option2, option3, option4, correct_option, difficulty, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

foreach($jsonFiles as $subject => $filePath){
    if(!file_exists($filePath)){
        echo "JSON file not found for $subject at $filePath\n";
        continue;
    }

    $jsonData = file_get_contents($filePath);
    $questions = json_decode($jsonData, true);

    if(!$questions){
        echo "Invalid JSON format in $filePath\n";
        continue;
    }

    $inserted = 0;

    foreach($questions as $q){
        $question_text = $q['question'] ?? '';
        $options = $q['options'] ?? [];
        $answer = $q['answer'] ?? '';

        if(count($options) < 2 || empty($answer)) continue;

        $opt1 = $options[0] ?? '';
        $opt2 = $options[1] ?? '';
        $opt3 = $options[2] ?? '';
        $opt4 = $options[3] ?? '';

        $correct_option = array_search($answer, [$opt1, $opt2, $opt3, $opt4]) + 1;
        $difficulty = $q['difficulty'] ?? 1;

        $stmt->bind_param("ssssssii", $subject, $question_text, $opt1, $opt2, $opt3, $opt4, $correct_option, $difficulty);

        if($stmt->execute()){
            $inserted++;
        }
    }

    echo "Imported $inserted questions for $subject.\n";
    $insertedTotal += $inserted;
}

$stmt->close();
echo "Total imported questions: $insertedTotal\n";
?>
