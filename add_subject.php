<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $semesters = $_POST['semester'];
    $subjects = $_POST['subject_name'];
    $marks = $_POST['marks'];

    $stmt = $conn->prepare("INSERT INTO subject_marks (student_id, semester, subject_name, marks) VALUES (?, ?, ?, ?)");

    for ($i = 0; $i < count($subjects); $i++) {
        $sem = $semesters[$i];
        $sub = $subjects[$i];
        $mks = $marks[$i];

        $stmt->bind_param("iisi", $student_id, $sem, $sub, $mks);
        $stmt->execute();
    }

    echo "<script>alert('Subjects added successfully'); window.location='admin.php';</script>";
}
?>
