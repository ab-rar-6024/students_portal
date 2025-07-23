<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $rrn = $_POST['rrn'];
    $password = $_POST['password'];
    $marks = $_POST['marks'];
    $attendance = $_POST['attendance'];
    $cgpa = $_POST['cgpa'];
    $fees_paid = isset($_POST['fees_paid']) ? 1 : 0;

    // ✅ Check for duplicate RRN
    $check = $conn->prepare("SELECT id FROM students WHERE rrn = ?");
    $check->bind_param("s", $rrn);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "<span style='color:red;'>RRN already exists. Please use a unique RRN.</span>";
    } else {
        // ✅ Upload photo
        $photo = $_FILES['photo']['name'];
        $target = "uploads/" . basename($photo);

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true); // Ensure uploads directory exists
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $stmt = $conn->prepare("INSERT INTO students (name, rrn, password, marks, attendance, cgpa, fees_paid, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiddis", $name, $rrn, $password, $marks, $attendance, $cgpa, $fees_paid, $photo);

            if ($stmt->execute()) {
                $message = "<span style='color:green;'>Student added successfully!</span>";
            } else {
                $message = "<span style='color:red;'>Error adding student to database.</span>";
            }
        } else {
            $message = "<span style='color:red;'>Error uploading photo.</span>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student - Crescent Portal</title>
    <style>
        body {
            font-family: Arial;
            background-color: #eef5ff;
            padding: 30px;
        }
        .form-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            margin: auto;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            font-size: 15px;
        }
        label {
            font-weight: bold;
        }
        button {
            padding: 12px;
            background: #0077cc;
            color: white;
            font-weight: bold;
            border: none;
            width: 100%;
            margin-top: 10px;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="form-box">
    <form method="post" enctype="multipart/form-data">
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>RRN:</label>
        <input type="text" name="rrn" required>
		
		<label>Email:</label>
		<input type="email" name="email" required><br>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Marks:</label>
        <input type="number" name="marks" required>

        <label>Attendance (%):</label>
        <input type="number" name="attendance" required>

        <label>CGPA:</label>
        <input type="number" step="0.1" name="cgpa" required>

        <label>Fees Paid:</label>
        <input type="checkbox" name="fees_paid">

        <label>Photo:</label>
        <input type="file" name="photo" accept="image/*" required>

			
    </form>

    <?php if ($message) echo "<p class='message'>$message</p>"; ?>
</div>

</body>
</html>
