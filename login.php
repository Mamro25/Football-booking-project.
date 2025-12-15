<?php
session_start();

// Database connection
$servername = "sql201.infinityfree.com";
$username = "if0_40087946"; 
$password = "GlPUQ2J2DqU67"; 
$dbname = "if0_40087946_football_booking";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Redirect target (default: bookings.php)
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : "bookings.php";

// Handle Sign Up
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
  $user = trim($_POST['username']);
  $email = trim($_POST['email']);
  $pass = $_POST['password']; // plain text for now

  $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss", $user, $email, $pass);

  if ($stmt->execute()) {
    header("Location: login.php?registered=1");
    exit();
  } else {
    $error = "❌ Error: " . $stmt->error;
  }
}

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
  $email = trim($_POST['email']);
  $pass = $_POST['password'];

  $sql = "SELECT id, username, password FROM users WHERE email = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if ($pass === $row['password']) {
      $_SESSION['user_id'] = $row['id'];
      $_SESSION['username'] = $row['username'];
      $_SESSION['email'] = $email;

      header("Location: " . $redirect);
      exit();
    } else {
      $error = "❌ Invalid password!";
    }
  } else {
    $error = "❌ No account found with that email!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Sign Up - Football Field Reservation</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
 <nav>
    <a href="index.php" <?php if(basename($_SERVER['PHP_SELF'])=='index.php'){echo 'class="active"';} ?>>Home</a>
    <a href="reservations.php" <?php if(basename($_SERVER['PHP_SELF'])=='reservations.php'){echo 'class="active"';} ?>>Available Reservations</a>
    <a href="bookings.php" <?php if(basename($_SERVER['PHP_SELF'])=='bookings.php'){echo 'class="active"';} ?>>My Bookings</a>
    <a href="team.php" <?php if(basename($_SERVER['PHP_SELF'])=='team.php'){echo 'class="active"';} ?>>Team</a>
    <a href="contact.php" <?php if(basename($_SERVER['PHP_SELF'])=='contact.php'){echo 'class="active"';} ?>>Contact Us</a>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login / Sign Up</a>
    <?php endif; ?>
  </nav>

</header>


  <section class="form-container">
    <?php if (!empty($error)) { echo "<p style='color:red; text-align:center;'>$error</p>"; } ?>

    <!-- Login Form -->
    <div id="loginForm" class="tab-content active">
      <h2>Login</h2>
      <form action="login.php?redirect=<?php echo urlencode($redirect); ?>" method="POST">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" name="login">Login</button>
      </form>
      <p>Don't have an account? <a href="#" id="showSignup">Sign up here</a></p>
    </div>

    <!-- Sign Up Form -->
    <div id="signupForm" class="tab-content" style="display:none;">
      <h2>Sign Up</h2>
      <form action="login.php?redirect=<?php echo urlencode($redirect); ?>" method="POST">
        <input type="text" name="username" placeholder="Enter username" required><br>
        <input type="email" name="email" placeholder="Enter email" required><br>
        <input type="password" name="password" placeholder="Enter password" required><br>
        <button type="submit" name="signup">Sign Up</button>
      </form>
      <p>Already have an account? <a href="#" id="showLogin">Login here</a></p>
    </div>
  </section>

  <footer>
    <p>&copy; 2025 Football Field Reservation</p>
  </footer>

  <script>
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const showSignup = document.getElementById('showSignup');
    const showLogin = document.getElementById('showLogin');

    showSignup.addEventListener('click', (e) => {
      e.preventDefault();
      loginForm.style.display = "none";
      signupForm.style.display = "block";
    });

    showLogin.addEventListener('click', (e) => {
      e.preventDefault();
      signupForm.style.display = "none";
      loginForm.style.display = "block";
    });

    if (window.location.search.includes("registered=1")) {
      signupForm.style.display = "none";
      loginForm.style.display = "block";
      alert("✅ Registration successful! Please log in.");
    }
  </script>
</body>
</html>
