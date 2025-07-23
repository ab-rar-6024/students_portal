<!-- email/email_form.php -->
<form method="post" action="email/send_email.php" enctype="multipart/form-data" style="margin:20px;">
  <h3>Send Email to Student</h3>
  <label>To (Email):</label><input type="email" name="to" required><br>
  <label>CC (Optional):</label><input type="email" name="cc"><br>
  <label>Subject:</label><input type="text" name="subject" required><br>
  <label>Body:</label><textarea name="body" rows="5" required></textarea><br>
  <label>Attach PDF:</label><input type="file" name="attachment" accept="application/pdf"><br><br>
  <button type="submit">Send Email</button>
</form>
