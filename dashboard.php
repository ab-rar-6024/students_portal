<?php
session_start();
include 'db.php';

if (!isset($_SESSION['student'])) {
    header("Location: index.php");
    exit;
}

$student = $_SESSION['student'];
$student_id = $student['id'];

// Fetch subjects per semester
$subjectData = [];
$stmt = $conn->prepare("SELECT semester, subject_name, marks FROM subject_marks WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $semKey = "sem" . $row['semester'];
    $subjectData[$semKey][] = $row;
}

// Handle message send
if (isset($_POST['send_message'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $attachment = '';

    if (!empty($_FILES['attachment']['name'])) {
        $attachment = basename($_FILES['attachment']['name']);
        move_uploaded_file($_FILES['attachment']['tmp_name'], "uploads/$attachment");
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender, student_id, subject, message, attachment) VALUES ('student', ?, ?, ?, ?)");
    $stmt->bind_param("isss", $student_id, $subject, $message, $attachment);
    $stmt->execute();
    echo "<script>alert('Message sent to admin!');</script>";
}

// Delete admin reply if requested
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT attachment FROM messages WHERE id = ? AND student_id = ? AND sender = 'admin'");
    $stmt->bind_param("ii", $deleteId, $student_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($attachment);
        $stmt->fetch();

        if (!empty($attachment) && file_exists("uploads/$attachment")) {
            unlink("uploads/$attachment"); // Delete file
        }

        $delStmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND student_id = ? AND sender = 'admin'");
        $delStmt->bind_param("ii", $deleteId, $student_id);
        $delStmt->execute();
        header("Location: student_home.php");
        exit;
    }
}

// Fetch admin replies
$replyStmt = $conn->prepare("SELECT id, subject, message, attachment, sent_at FROM messages WHERE student_id=? AND sender='admin' ORDER BY sent_at DESC");
$replyStmt->bind_param("i", $student_id);
$replyStmt->execute();
$replyResults = $replyStmt->get_result();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Student Home</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
        }
        .sidebar {
            width: 220px;
            background-color: #003366;
            color: white;
            height: 100vh;
            padding-top: 30px;
            position: fixed;
        }
        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #0055aa;
        }
        .main-content {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
            background: #f4f8ff;
            min-height: 100vh;
        }

        .hidden-section {
            display: none;
        }
        .active-section {
            display: block;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #004080;
            margin-bottom: 10px;
        }

        .info-grid {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .info-box {
            background-color: #eaf3ff;
            border-left: 5px solid #0073e6;
            padding: 15px 20px;
            border-radius: 8px;
            flex: 1;
            min-width: 200px;
        }

        .email-card, .inbox {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }

        .btn-primary {
            background-color: #0073e6;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .message-box {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        canvas {
            background: white;
            border-radius: 10px;
            padding: 10px;
        }

    </style>
</head>
<body>

<div class="sidebar">
    <h3><?= htmlspecialchars($student['name']) ?></h3>
    <a href="#" onclick="showSection('dashboard')">📊 Dashboard</a>
    <a href="#" onclick="showSection('email')">✉️ Send Email</a>
    <a href="#" onclick="showSection('inbox')">📥 Inbox</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <!-- Dashboard Section -->
    <div id="dashboard" class="active-section">
        <div style="text-align:center;">
            <img src="uploads/<?= htmlspecialchars($student['photo']) ?>" class="profile-img">
            <h2><?= htmlspecialchars($student['name']) ?></h2>
            <p><strong>RRN:</strong> <?= htmlspecialchars($student['rrn']) ?></p>
        </div>

        <div class="info-grid">
            <div class="info-box"><h3>Marks</h3><p><?= $student['marks'] ?>/500</p></div>
            <div class="info-box"><h3>Attendance</h3><p><?= $student['attendance'] ?>%</p></div>
            <div class="info-box"><h3>CGPA</h3><p><?= $student['cgpa'] ?></p></div>
            <div class="info-box"><h3>Fees</h3><p><?= $student['fees_paid'] ? "Paid" : "Pending" ?></p></div>
        </div>

        <canvas id="performanceChart" height="100" style="margin-top:30px;"></canvas>
    </div>

    <!-- Email Section -->
    <div id="email" class="hidden-section">
        <div class="email-card">
            <h3>Send Email to Admin</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                <textarea name="message" class="form-control" placeholder="Write your message..." required></textarea>
                <input type="file" name="attachment" accept=".pdf">
                <br><br>
                <button type="submit" name="send_message" class="btn-primary">Send</button>
            </form>
        </div>
    </div>

    <!-- Inbox Section -->
    <div id="inbox" class="hidden-section inbox">
        <h3>Messages from Admin</h3>
        <?php if ($replyResults->num_rows == 0): ?>
            <p>No messages yet.</p>
        <?php else: ?>
            <?php while ($msg = $replyResults->fetch_assoc()): ?>
                <div class="message-box">
    <strong><?= htmlspecialchars($msg['subject']) ?></strong><br>
    <?= nl2br(htmlspecialchars($msg['message'])) ?><br>
    <?php if ($msg['attachment']): ?>
        <a href="uploads/<?= htmlspecialchars($msg['attachment']) ?>" target="_blank">📎 View Attachment</a><br>
    <?php endif; ?>
    <small><?= $msg['sent_at'] ?></small><br>
    <a href="?delete_id=<?= $msg['id'] ?>" onclick="return confirm('Are you sure you want to delete this message?')">🗑️ Delete</a>
</div>

            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function showSection(sectionId) {
    document.querySelectorAll('.main-content > div').forEach(div => {
        div.classList.add('hidden-section');
        div.classList.remove('active-section');
    });
    document.getElementById(sectionId).classList.remove('hidden-section');
    document.getElementById(sectionId).classList.add('active-section');
}

const ctx = document.getElementById('performanceChart').getContext('2d');
const chartData = [<?= $student['marks'] ?>, <?= $student['attendance'] ?>, <?= $student['cgpa'] * 10 ?>];
const chartColors = chartData.map(val => val < 40 ? 'red' : val < 70 ? 'yellow' : 'green');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Marks', 'Attendance', 'CGPA x10'],
        datasets: [{
            label: 'Performance',
            data: chartData,
            backgroundColor: chartColors
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
</script>
</body>
</html>
