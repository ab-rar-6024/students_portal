<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send Email to Students</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f0f4f8;
      font-family: 'Segoe UI', sans-serif;
    }

    .email-card {
      max-width: 700px;
      margin: 50px auto;
      padding: 30px;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      animation: fadeIn 0.5s ease-in-out;
    }

    .form-label {
      font-weight: 600;
    }

    .form-control, .form-select {
      border-radius: 8px;
    }

    .btn-send {
      background-color: #3498db;
      color: white;
      font-weight: bold;
      border-radius: 8px;
    }

    .btn-send:hover {
      background-color: #2980b9;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<div class="email-card">
  <h3 class="text-center mb-4">📧 Send Email to Students</h3>

  <form action="send_email.php" method="post" enctype="multipart/form-data">
    
    <div class="mb-3">
      <label class="form-label">To:</label>
      <select name="to_email" class="form-select" required>
        <option value="">-- Select Student --</option>
        <?php
        include '../db.php';
        $students = $conn->query("SELECT name, email FROM students");
        while ($row = $students->fetch_assoc()):
        ?>
        <option value="<?= $row['email'] ?>"><?= $row['name'] ?> (<?= $row['email'] ?>)</option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">CC:</label>
      <input type="email" name="cc" class="form-control" placeholder="Enter CC email (optional)">
    </div>

    <div class="mb-3">
      <label class="form-label">Subject:</label>
      <input type="text" name="subject" class="form-control" required placeholder="Enter subject">
    </div>

    <div class="mb-3">
      <label class="form-label">Body:</label>
      <textarea name="body" class="form-control" rows="6" required placeholder="Enter your message"></textarea>
    </div>

    <div class="mb-4">
      <label class="form-label">Attach PDF:</label>
      <input type="file" name="attachment" class="form-control" accept="application/pdf">
    </div>

    <div class="text-center">
      <button type="submit" class="btn btn-send px-4 py-2">📨 Send</button>
    </div>

  </form>
</div>

</body>
</html>
