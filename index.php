<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Football Field Reservations</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .names-box {
      margin-top: 20px;
      text-align: center;
      font-size: 0.9rem;
      color: #555;
      line-height: 1.6;
      padding-bottom: 15px;
    }
  </style>
</head>
<body>
<header>
  <nav>
    <a href="index.php" <?php if(basename($_SERVER['PHP_SELF'])=='index.php'){echo 'class="active"';} ?>>Home</a>
    <a href="reservations.php" <?php if(basename($_SERVER['PHP_SELF'])=='reservations.php'){echo 'class="active"';} ?>>Available Reservations</a>
    <a href="bookings.php" <?php if(basename($_SERVER['PHP_SELF'])=='bookings.php'){echo 'class="active"';} ?>>My Bookings</a>
    <a href="team.php" <?php if(basename($_SERVER['PHP_SELF'])=='team.php'){echo 'class="active"';} ?>>Team</a>
    <a href="contact.php" <?php if(basename($_SERVER['PHP_SELF'])=='contact.php'){echo 'class="active"';} ?>>Contact Us</a>
    <?php if(isset($_SESSION['admin_id'])): ?>
      <a href="admin.php" <?php if(basename($_SERVER['PHP_SELF'])=='admin.php'){echo 'class="active"';} ?>>Admin</a>
    <?php else: ?>
      <a href="admin_login.php" <?php if(basename($_SERVER['PHP_SELF'])=='admin_login.php'){echo 'class="active"';} ?>>Admin</a>
    <?php endif; ?>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login / Sign Up</a>
    <?php endif; ?>
  </nav>
</header>

<main>
  <section class="hero">
    <h2>Book Your Football Field Online In Debrecen</h2>
    <p>Find available slots, make reservations, and enjoy the game hassle-free.</p>
    <a href="reservations.php" class="btn">Reserve Now</a>
  </section>
</main>

<footer>
  <p>&copy; 2025 Football Community. All rights reserved.</p>
</footer>

<!-- ⭐ Names added below the footer, matching site style -->
<div class="names-box">
    <strong>Made by:</strong><br>
    Mohamed Amro<br>
    Yousef Wael<br>
    Kareem Elmaghraby
</div>

</body>
</html>
