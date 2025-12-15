<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Available Reservations</title>
<link rel="stylesheet" href="style.css">
<style>
/* Reservations Grid */
.reservations {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    padding: 2rem;
}

.card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    width: 280px;
    padding: 15px;
    text-align: center;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.card h3 {
    font-size: 1.1rem;
    margin-bottom: 12px;
}

.card button {
    background: var(--primary-green);
    color: var(--white);
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

.card button:hover {
    background: var(--dark-green);
    transform: translateY(-2px);
}

/* Responsive */
@media screen and (max-width: 768px) {
    .card {
        width: 90%;
    }
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
<section class="reservations">
    <div class="card">
        <img src="images/field1.png" alt="Field 1">
        <h3>Oláh Gábor utcai Sportcsarnok</h3>
        <button type="button" onclick="checkLogin(1)">Book Now</button>
    </div>
    <div class="card">
        <img src="images/field2.png" alt="Field 2">
        <h3>DEAC University Sports Center</h3>
        <button type="button" onclick="checkLogin(2)">Book Now</button>
    </div>
    <div class="card">
        <img src="images/field3.png" alt="Field 3">
        <h3>Debreceni Sportcentrum Közhasznú Nonprofit Kft.</h3>
        <button type="button" onclick="checkLogin(3)">Book Now</button>
    </div>
</section>
</main>

<script>
function checkLogin(fieldId) {
    <?php if(isset($_SESSION['user_id'])): ?>
        window.location.href = 'slots.php?field_id=' + fieldId;
    <?php else: ?>
        window.location.href = 'login.php?redirect=slots.php?field_id=' + fieldId;
    <?php endif; ?>
}
</script>
</body>
</html>
