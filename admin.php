<?php
session_start();
include 'db.php';
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit; }

// ––– CRUD and Messaging Logic –––

// Delete student
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subject_marks WHERE student_id = $id");
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Add student
if (isset($_POST['add_student'])) {
    $name = $_POST['name'];
    $rrn = $_POST['rrn'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $marks = $_POST['marks'];
    $attendance = $_POST['attendance'];
    $cgpa = $_POST['cgpa'];
    $fees_paid = isset($_POST['fees_paid']) ? 1 : 0;
    $photo = $_FILES['photo']['name'];
    $target = "uploads/" . basename($photo);
    move_uploaded_file($_FILES['photo']['tmp_name'], $target);

    $check = $conn->prepare("SELECT id FROM students WHERE rrn = ?");
    $check->bind_param("s", $rrn);
    $check->execute(); $check->store_result();
    if ($check->num_rows > 0) {
        echo "<script>alert('RRN exists'); window.location='admin.php';</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO students (name, rrn, password, email, marks, attendance, cgpa, fees_paid, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiddis", $name, $rrn, $password, $email, $marks, $attendance, $cgpa, $fees_paid, $photo);
        $stmt->execute();
        echo "<script>alert('Added'); window.location='admin.php';</script>";
    }
    exit;
}

// Update student
if (isset($_POST['update_student'])) {
    $id = $_POST['edit_id'];
    $fees_paid = isset($_POST['edit_fees_paid']) ? 1 : 0;
    $photo = null;

    if (!empty($_FILES['edit_photo']['name'])) {
        $photo = $_FILES['edit_photo']['name'];
        $target = "uploads/" . basename($photo);
        move_uploaded_file($_FILES['edit_photo']['tmp_name'], $target);
        $stmt = $conn->prepare("UPDATE students SET name=?, rrn=?, email=?, marks=?, attendance=?, cgpa=?, fees_paid=?, photo=? WHERE id=?");
        $stmt->bind_param("sssiddisi", $_POST['edit_name'], $_POST['edit_rrn'], $_POST['edit_email'], $_POST['edit_marks'], $_POST['edit_attendance'], $_POST['edit_cgpa'], $fees_paid, $photo, $id);
    } else {
        $stmt = $conn->prepare("UPDATE students SET name=?, rrn=?, email=?, marks=?, attendance=?, cgpa=?, fees_paid=? WHERE id=?");
        $stmt->bind_param("sssiddii", $_POST['edit_name'], $_POST['edit_rrn'], $_POST['edit_email'], $_POST['edit_marks'], $_POST['edit_attendance'], $_POST['edit_cgpa'], $fees_paid, $id);
    }

    $stmt->execute();
    echo "<script>alert('Student updated successfully'); window.location='admin.php';</script>";
    exit;
}

// Admin reply
if (isset($_POST['admin_reply'])) {
    $mid = intval($_POST['msg_id']);
    $st1 = $conn->prepare("SELECT student_id, subject FROM messages WHERE id=?");
    $st1->bind_param("i", $mid); $st1->execute();
    $row = $st1->get_result()->fetch_assoc();
    $re_subject = "Re: " . $row['subject'];
    $st2 = $conn->prepare("INSERT INTO messages (student_id, sender, subject, message) VALUES (?, 'admin', ?, ?)");
    $st2->bind_param("iss", $row['student_id'], $re_subject, $_POST['reply_msg']);
    $st2->execute();
    echo "<script>alert('Replied'); location.href='admin.php';</script>";
    exit;
}

$query = $_GET['query'] ?? '';
$params = [];
$types = '';
$sql = "SELECT * FROM students";

if (!empty($query)) {
    $sql .= " WHERE name LIKE ? OR rrn LIKE ?";
    $params[] = "%$query%"; $types .= "s";
    $params[] = "%$query%"; $types .= "s";

    if (is_numeric($query)) {
        $sql .= " OR marks >= ? OR attendance >= ?";
        $params[] = (int)$query; $types .= "i";
        $params[] = (int)$query; $types .= "i";
    }
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

$studentOptions = $conn->query("SELECT id,name FROM students ORDER BY name");
$messages = $conn->query("
    SELECT m.id, s.name, m.subject, m.message, m.attachment, m.sent_at
    FROM messages m 
    JOIN students s ON m.student_id = s.id
    WHERE m.sender='student'
    ORDER BY m.sent_at DESC
");
$avg = $conn->query("
    SELECT AVG(marks) AS marks, AVG(attendance) AS attendance, AVG(cgpa) AS cgpa 
    FROM students
")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      /* Sidebar and content styles */
      body { margin:0; font-family:'Segoe UI'; }
      .sidebar { position:fixed; width:220px; background:#2c3e50; height:100vh; color:#fff; padding:20px; }
      .sidebar a { display:block; color:#fff; padding:10px; text-decoration:none; margin-bottom:5px; }
      .sidebar a.active, .sidebar a:hover { background:#34495e; }
      .main { margin-left:240px; padding:20px; }
      .section { display:none; }
      .section.active { display:block; }
      .card { padding:20px; background:#fff; margin-bottom:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
      table { width:100%; border-collapse:collapse; }
      th, td { border:1px solid #ddd; padding:8px; }
      th { background:#2980b9; color:#fff; }
      .btn-red { background:#e74c3c; color:#fff; }
    </style>
</head>
<body>
  <div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#" class="nav-link active" onclick="showSection('dashboard',event)">🏠 Dashboard</a>
    <a href="#" class="nav-link" onclick="showSection('add_student',event)">➕ Add Student</a>
    <a href="#" class="nav-link" onclick="showSection('update_student',event)">✏️ Update Student</a>
    <a href="#" class="nav-link" onclick="showSection('inbox',event)">📥 Inbox</a>
    <a href="#" class="nav-link" onclick="showSection('performance',event)">📊 Performance</a>
    <a href="logout.php">🚪 Logout</a>
  </div>

  <div class="main">
    <!-- Dashboard + Search -->
    <div id="dashboard" class="section active">
      <div class="card">
        <h3>All Students</h3>
        <form method="GET" class="mb-3">
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <input type="text" name="query" placeholder="Search Name / RRN / Marks / Attendance" class="form-control" style="max-width:350px"
                   value="<?= htmlspecialchars($_GET['query'] ?? '') ?>">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="admin.php" class="btn btn-secondary">Reset</a>
          </div>
        </form>

        <table>
          <thead><tr><th>ID</th><th>Name</th><th>RRN</th><th>Email</th><th>Marks</th><th>Actions</th></tr></thead>
          <tbody>
          <?php while ($r = $students->fetch_assoc()): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['rrn']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['marks']) ?></td>
              <td>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="selected_student_id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">View</button>
                </form>
                <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-red" onclick="return confirm('Delete?')">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Student -->
    <div id="add_student" class="section">
      <div class="card">
        <h3>Add Student</h3>
        <form method="post" enctype="multipart/form-data">
          <input name="name" class="form-control mb-2" placeholder="Name" required>
          <input name="rrn" class="form-control mb-2" placeholder="RRN" required>
          <input name="password" class="form-control mb-2" placeholder="Password" required>
          <input name="email" class="form-control mb-2" placeholder="Email" required>
          <input name="marks" type="number" class="form-control mb-2" placeholder="Marks" required>
          <input name="attendance" type="number" class="form-control mb-2" placeholder="Attendance (%)" required>
          <input name="cgpa" step="0.1" type="number" class="form-control mb-2" placeholder="CGPA" required>
          <div class="form-check mb-2"><input type="checkbox" name="fees_paid" class="form-check-input"><label class="form-check-label">Fees Paid</label></div>
          <input type="file" name="photo" class="form-control mb-2" required>
          <button name="add_student" class="btn btn-success">Add Student</button>
        </form>
      </div>
    </div>

    <!-- Update Student -->
    <div id="update_student" class="section">
      <div class="card">
        <h3>Update Student</h3>
        <form method="post">
          <select name="selected_student_id" class="form-select mb-3" onchange="this.form.submit()">
            <option value="">-- Select a Student --</option>
            <?php while ($stu = $studentOptions->fetch_assoc()): ?>
              <option value="<?= $stu['id'] ?>" <?= (isset($_POST['selected_student_id']) && $_POST['selected_student_id'] == $stu['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($stu['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </form>
        <?php
        if (isset($_POST['selected_student_id']) && $_POST['selected_student_id'] != ""):
          $sid = intval($_POST['selected_student_id']);
          $sstmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
          $sstmt->bind_param("i", $sid);
          $sstmt->execute();
          $student = $sstmt->get_result()->fetch_assoc();
        ?>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" value="<?= $student['id'] ?>">
            <input name="edit_name" class="form-control mb-2" value="<?= htmlspecialchars($student['name']) ?>" required>
            <input name="edit_rrn" class="form-control mb-2" value="<?= htmlspecialchars($student['rrn']) ?>" required>
            <input name="edit_email" class="form-control mb-2" value="<?= htmlspecialchars($student['email']) ?>" required>
            <input name="edit_marks" type="number" class="form-control mb-2" value="<?= $student['marks'] ?>" required>
            <input name="edit_attendance" type="number" class="form-control mb-2" value="<?= $student['attendance'] ?>" required>
            <input name="edit_cgpa" type="number" step="0.1" class="form-control mb-2" value="<?= $student['cgpa'] ?>" required>
            <div class="form-check mb-2">
              <input type="checkbox" name="edit_fees_paid" <?= $student['fees_paid'] ? 'checked' : '' ?> class="form-check-input">
              <label class="form-check-label">Fees Paid</label>
            </div>
            <input type="file" name="edit_photo" class="form-control mb-2">
            <button name="update_student" class="btn btn-success">Update</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Inbox Section -->
    <div id="inbox" class="section">
      <div class="card">
        <h3>Student Messages</h3>
        <?php while ($msg = $messages->fetch_assoc()): ?>
          <div class="card mb-2 p-2">
            <strong>From:</strong> <?= htmlspecialchars($msg['name']) ?><br>
            <strong>Subject:</strong> <?= htmlspecialchars($msg['subject']) ?><br>
            <?= nl2br(htmlspecialchars($msg['message'])) ?><br>
            <?= $msg['attachment'] ? "<a href='uploads/".htmlspecialchars($msg['attachment'])."' target='_blank'>📎 Attachment</a><br>" : "" ?>
            <small><?= $msg['sent_at'] ?></small>
            <form method="post" class="mt-2">
              <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
              <textarea name="reply_msg" class="form-control mb-2" placeholder="Type your reply here..." required></textarea>
              <button name="admin_reply" class="btn btn-primary">Reply</button>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

    <!-- Performance Section -->
    <div id="performance" class="section">
      <div class="card">
        <h3>Average Student Performance</h3>
        <canvas id="performanceChart"></canvas>
      </div>
    </div>

    <?php if ($students->num_rows === 0): ?>
      <div class="alert alert-warning mt-3">No students found.</div>
    <?php endif; ?>

    <?php
    if (isset($_POST['selected_student_id'])) {
        $sid = intval($_POST['selected_student_id']);
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        if ($student):
    ?>
        <div class="card">
            <h3>👤 Student Details</h3>
            <div style="display:flex; gap:20px;">
                <img src="uploads/<?= htmlspecialchars($student['photo']) ?>" width="120" height="120" style="border-radius:8px;">
                <div>
                    <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                    <p><strong>RRN:</strong> <?= htmlspecialchars($student['rrn']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                    <p><strong>Marks:</strong> <?= $student['marks'] ?></p>
                    <p><strong>Attendance:</strong> <?= $student['attendance'] ?>%</p>
                    <p><strong>CGPA:</strong> <?= $student['cgpa'] ?></p>
                </div>
            </div>
            <canvas id="studentChart" height="120"></canvas>
        </div>

        <script>
        const ctxStudent = document.getElementById('studentChart').getContext('2d');
        const studentChart = new Chart(ctxStudent, {
            type: 'bar',
            data: {
                labels: ['Marks', 'Attendance', 'CGPA ×10'],
                datasets: [{
                    label: 'Student Performance',
                    data: [
                        <?= $student['marks'] ?>,
                        <?= $student['attendance'] ?>,
                        <?= $student['cgpa'] * 10 ?>
                    ],
                    backgroundColor: [
                        <?= $student['marks'] ?> < 60 ? 'red' : <?= $student['marks'] ?> < 75 ? 'yellow' : 'green',
                        <?= $student['attendance'] ?> < 60 ? 'red' : <?= $student['attendance'] ?> < 75 ? 'yellow' : 'green',
                        <?= $student['cgpa']*10 ?> < 60 ? 'red' : <?= $student['cgpa']*10 ?> < 75 ? 'yellow' : 'green'
                    ]
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
        </script>
    <?php endif; } ?>
  </div>

  <script>
    function showSection(id, e){
      document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
      document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
      document.getElementById