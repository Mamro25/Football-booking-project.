<?php
session_start();
$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) die("DB connection failed");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Missing credentials';
    } else {
        // Simple admin auth: check users table for a role=admin
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $u = $res->fetch_assoc();
          $isAdmin = strtolower($u['role'] ?? '') === 'admin';
          if ($isAdmin && $password === $u['password']) {
            // Ensure admin session is exclusive: clear any user session
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            $_SESSION['admin_id'] = intval($u['id']);
            $_SESSION['admin_username'] = $username;
                header('Location: admin.php');
                exit();
            }
        }
        $error = 'Invalid admin credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link rel="stylesheet" href="style.css">
<style>
  .admin-hero { text-align:center; padding:2rem 1rem; }
</style>
</head>
<body>
<header>
  <nav>
    <a href="index.php">Home</a>
    <a href="reservations.php">Available Reservations</a>
    <a href="bookings.php">My Bookings</a>
    <a href="team.php">Team</a>
    <a href="contact.php">Contact Us</a>
    <a href="admin_login.php" class="active">Admin</a>
  </nav>
</header>
<main>
  <div class="admin-hero">
    <h2>Admin Control Panel Login</h2>
    <p class="small">Authorized personnel only.</p>
  </div>
  <form class="form-container" method="post">
    <?php if($error){ echo '<div style="color:#dc3545;text-align:center;">'.$error.'</div>'; } ?>
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Login</button>
  </form>
</main>
</body>
</html>
