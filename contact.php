<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us</title>
<link rel="stylesheet" href="style.css">
<style>
.contact-wrapper { max-width: 900px; margin: 2rem auto; padding: 1rem; text-align:center; }
.info-card { background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:16px; margin-bottom:16px; }
.names-box { margin-top: 30px; font-size: 0.9rem; color:#555; text-align:center; line-height:1.6; }
.grid { display:grid; grid-template-columns: 1fr; gap:16px; justify-items:center; }
@media (min-width: 769px){ .grid { grid-template-columns: 1fr; } }
.form-container { width: 100%; max-width: 520px; margin: 0 auto; text-align:left; }
.submit-btn { background: var(--primary-green); color:#fff; border:none; padding:10px 16px; border-radius:12px; cursor:pointer; }
.submit-btn:hover { background: var(--dark-green); }
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

<main class="contact-wrapper">
    <div class="info-card">
        <h2>Contact Us</h2>
        <p class="small">Have questions about fields, bookings, or team rooms? Send us a message.</p>
    </div>

    <div class="grid">
        <form class="form-container" method="post" action="mailto:support@example.com" enctype="text/plain">
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" required></textarea>

            <button class="submit-btn" type="submit">Send Message</button>
        </form>
    </div>

    <!-- ⭐ Names placed below the entire contact form -->
    <div class="names-box">
        <strong>Made by:</strong><br>
        Mohamed Amro<br>
        Yousef Wael<br>
        Kareem Elmaghraby
    </div>

</main>
</body>
</html>
