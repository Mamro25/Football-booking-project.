<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


$user_id = $_SESSION['user_id'];

// --- Handle cancellation if requested ---
if (isset($_POST['cancel_booking_id'])) {
    $booking_id = intval($_POST['cancel_booking_id']);

    // Make sure the booking belongs to this user
    $check = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $booking_id, $user_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $slot_id = $row['slot_id'];

        // Delete the booking
        $delete = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $booking_id, $user_id);
        $delete->execute();

        // Mark the slot as available again
        $update = $conn->prepare("UPDATE availability SET is_booked = 0 WHERE id = ?");
        $update->bind_param("i", $slot_id);
        $update->execute();

        $message = "Booking cancelled successfully!";
    } else {
        $message = "Error: Booking not found or unauthorized.";
    }
}

// --- Fetch all user bookings ---
$sql = "SELECT b.id, a.id AS slot_id, a.field_id, a.slot_time, b.booked_at, u.username
        FROM bookings b
        JOIN availability a ON b.slot_id = a.id
        JOIN users u ON b.user_id = u.id
        WHERE b.user_id = ?
        ORDER BY a.slot_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Bookings</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { width: 90%; margin: 30px auto; border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: center; }
    th { background: var(--primary-green); color: white; }
    tr:nth-child(even) td { background: #f7f7f7; }
    .cancel-btn { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; transition: background 0.3s ease; }
    .cancel-btn:hover { background: #b02a37; }
    .message { text-align: center; color: #28a745; font-weight: bold; margin: 15px; }
    .team-btn { background: var(--accent-gold); color: var(--primary-green); border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
    .team-btn:hover { background:#c49c2f; }
  </style>
</head>
<body>
<header>
  <h1>My Bookings</h1>
  <nav>
    <a href="index.php">Home</a>
    <a href="reservations.php">Available Reservations</a>
    <a href="bookings.php" class="active">My Bookings</a>
    <a href="team.php">Team</a>
    <a href="contact.php">Contact Us</a>
    <?php if(isset($_SESSION['admin_id'])): ?>
      <a href="admin.php" <?php if(basename($_SERVER['PHP_SELF'])=='admin.php'){echo 'class="active"';} ?>>Admin</a>
    <?php else: ?>
      <a href="admin_login.php" <?php if(basename($_SERVER['PHP_SELF'])=='admin_login.php'){echo 'class="active"';} ?>>Admin</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
  <?php if (isset($message)) echo "<div class='message'>$message</div>"; ?>

  <table>
    <tr>
      <th>Field ID</th>
      <th>Slot Time</th>
      <th>Booked At</th>
      <th>Username</th>
      <th>Actions</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $slot_display = date('l, F j \\, H:i', strtotime($row['slot_time']));
            echo "<tr>
                    <td>{$row['field_id']}</td>
                    <td>{$slot_display}</td>
                    <td>{$row['booked_at']}</td>
                    <td>{$row['username']}</td>
                    <td>
                        <form method='POST' style='display:inline-block; margin-right:6px;' onsubmit='return confirm(\"Cancel this booking?\");'>
                            <input type='hidden' name='cancel_booking_id' value='{$row['id']}'>
                            <button type='submit' class='cancel-btn'>Cancel</button>
                        </form>
                        <a href='team.php?slot_id={$row['slot_id']}' class='team-btn'>Open Team Room</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No bookings yet.</td></tr>";
    }
    ?>
  </table>
</main>
</body>
</html>
<?php $conn->close(); ?>
