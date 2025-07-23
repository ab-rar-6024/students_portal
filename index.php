<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rrn = $_POST['rrn'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if ($role == 'student') {
        $stmt = $conn->prepare("SELECT * FROM students WHERE rrn = ? AND password = ?");
        $stmt->bind_param("ss", $rrn, $password);
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $rrn, $password);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION[$role] = $user;

        if ($role == 'student') {
            header("Location: dashboard.php");
        } else {
            header("Location: admin.php");
        }
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Crescent Portal Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #c3e4ff, #f5faff);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
            width: 350px;
        }

        .logo {
            width: 100px;
            margin-bottom: 20px;
        }

        .login-input, .login-select {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            font-size: 16px;
            border-radius: 10px;
            background: linear-gradient(#f0f0f0, #ffffff);
            box-shadow: inset 1px 1px 2px #ccc, inset -1px -1px 2px #fff;
        }

        .login-btn {
            background-color: #0077cc;
            color: white;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
        }

        .error-msg {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- ✅ Update image path here -->
    <img src="images/logo.png" class="logo" alt="Crescent logo">

    <h2>Crescent Login</h2>
    <form method="post">
        <select class="login-select" name="role" required>
            <option value="student">Student Login</option>
            <option value="admin">Admin Login</option>
        </select>
        <input class="login-input" type="text" name="rrn" placeholder="RRN / Username" required><br>
        <input class="login-input" type="password" name="password" placeholder="Password" required><br>
        <button class="login-btn" type="submit">Login</button>
        <?php if (!empty($error)) echo "<p class='error-msg'>$error</p>"; ?>
    </form>
</div>
</body>
</html>
