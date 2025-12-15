<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit(); }
$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) die("DB connection failed");

// Basic admin actions: view bookings, cancel bookings, manage rooms
$info = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_booking_id'])) {
        $bid = intval($_POST['cancel_booking_id']);
        // Fetch booking
        $b = $conn->prepare("SELECT slot_id FROM bookings WHERE id = ?");
        $b->bind_param("i", $bid);
        $b->execute();
        $br = $b->get_result();
        if ($br->num_rows) {
            $row = $br->fetch_assoc();
            $slot_id = intval($row['slot_id']);
            // Delete booking + free slot
            $conn->prepare("DELETE FROM bookings WHERE id = ?")->bind_param("i", $bid);
            $conn->query("DELETE FROM bookings WHERE id = ".$bid);
            $up = $conn->prepare("UPDATE availability SET is_booked = 0 WHERE id = ?");
            $up->bind_param("i", $slot_id);
            $up->execute();
            $info = 'Booking cancelled';
        }
    }
    if (isset($_POST['delete_room_id'])) {
        $rid = intval($_POST['delete_room_id']);
        $conn->query("DELETE FROM room_messages WHERE room_id = ".$rid);
        $conn->query("DELETE FROM room_members WHERE room_id = ".$rid);
        $conn->query("DELETE FROM rooms WHERE id = ".$rid);
        $info = 'Room deleted';
    }
    if (isset($_POST['delete_slot_id'])) {
      $sid = intval($_POST['delete_slot_id']);
      $conn->query("DELETE m FROM room_messages m JOIN rooms r ON m.room_id=r.id WHERE r.slot_id = ".$sid);
      $conn->query("DELETE rm FROM room_members rm JOIN rooms r ON rm.room_id=r.id WHERE r.slot_id = ".$sid);
      $conn->query("DELETE FROM rooms WHERE slot_id = ".$sid);
      $conn->query("DELETE FROM bookings WHERE slot_id = ".$sid);
      $conn->query("DELETE FROM availability WHERE id = ".$sid);
      $info = 'Slot removed';
    }
    if (isset($_POST['add_slot'])) {
      $field = intval($_POST['field_id'] ?? 0);
      $timeInput = trim($_POST['slot_time'] ?? '');
      $time = str_replace('T', ' ', $timeInput);
      if ($field && $time) {
        $stmt = $conn->prepare("INSERT INTO availability (field_id, slot_time, is_booked) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $field, $time);
        $stmt->execute();
        $info = 'New slot added';
      } else {
        $info = 'Invalid slot data';
      }
    }
    if (isset($_POST['kick_room_id']) && isset($_POST['kick_user_id'])) {
      $rid = intval($_POST['kick_room_id']);
      $uid = intval($_POST['kick_user_id']);
      $del = $conn->prepare("DELETE FROM room_members WHERE room_id = ? AND user_id = ?");
      $del->bind_param("ii", $rid, $uid);
      $del->execute();
      $info = 'User kicked from room';
    }
}

$bookings = $conn->query("SELECT b.id, u.username, a.field_id, a.slot_time FROM bookings b JOIN users u ON b.user_id=u.id JOIN availability a ON b.slot_id=a.id ORDER BY a.slot_time DESC");
$rooms = $conn->query("SELECT r.id, r.slot_id, r.creator_user_id, a.slot_time, a.field_id FROM rooms r JOIN availability a ON r.slot_id=a.id ORDER BY a.slot_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel</title>
<link rel="stylesheet" href="style.css">
<style>
.admin-section { width:95%; max-width:1100px; margin:2rem auto; }
.admin-card { background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:16px; margin-bottom:16px; }
.admin-card h3 { margin-bottom:10px; }
.action-btn { padding:8px 12px; border:none; border-radius:8px; cursor:pointer; }
.action-btn.cancel { background:#dc3545; color:#fff; }
.action-btn.delete { background:#dc3545; color:#fff; }
.action-btn { transition: filter 0.2s ease; }
.action-btn:hover { filter: brightness(0.9); }
.note { color:#777; font-size:0.9rem; }
</style>
</head>
<body>
<header>
  <nav style="display:flex; justify-content:center; gap:2rem; flex-wrap:wrap;">
    <a href="index.php">Home</a>
    <a href="reservations.php">Available Reservations</a>
    <a href="bookings.php">My Bookings</a>
    <a href="team.php">Team</a>
    <a href="contact.php">Contact Us</a>
    <a href="admin.php" class="active">Admin</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>
<main class="admin-section">
  <?php if($info){ echo '<div style="text-align:center;color:green;">'.$info.'</div>'; } ?>

  <div class="admin-card">
    <h3>All Bookings</h3>
    <div class="note">Cancel any booking and free the slot.</div>
    <table>
      <tr><th>ID</th><th>User</th><th>Field</th><th>Slot Time</th><th>Action</th></tr>
      <?php while($b = $bookings->fetch_assoc()): ?>
        <tr>
          <td><?php echo $b['id']; ?></td>
          <td><?php echo htmlspecialchars($b['username']); ?></td>
          <td><?php echo $b['field_id']; ?></td>
          <td><?php echo date('Y-m-d H:i', strtotime($b['slot_time'])); ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Cancel booking #<?php echo $b['id']; ?>?');">
              <input type="hidden" name="cancel_booking_id" value="<?php echo $b['id']; ?>">
              <button class="action-btn cancel" type="submit">Cancel</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <div class="admin-card">
    <h3>All Rooms</h3>
    <div class="note">Delete rooms, kick members, and view messages.</div>
    <table>
      <tr><th>Room ID</th><th>Slot</th><th>Field</th><th>Creator</th><th>Members</th><th>Messages</th><th>Action</th></tr>
      <?php while($r = $rooms->fetch_assoc()): ?>
        <tr>
          <td><?php echo $r['id']; ?></td>
          <td><?php echo date('Y-m-d H:i', strtotime($r['slot_time'])); ?></td>
          <td><?php echo $r['field_id']; ?></td>
          <td><?php echo $r['creator_user_id']; ?></td>
          <td>
            <?php
              $mid = $conn->prepare("SELECT rm.user_id, u.username FROM room_members rm JOIN users u ON rm.user_id=u.id WHERE rm.room_id = ? ORDER BY rm.joined_at ASC");
              $mid->bind_param("i", $r['id']);
              $mid->execute();
              $membersRes = $mid->get_result();
            ?>
            <ul style="list-style:none; padding:0; margin:0;">
              <?php while($m = $membersRes->fetch_assoc()): ?>
                <li style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                  <span><?php echo htmlspecialchars($m['username']); ?> (<?php echo $m['user_id']; ?>)</span>
                  <form method="post" style="margin:0;" onsubmit="return confirm('Kick <?php echo htmlspecialchars($m['username']); ?> from room #<?php echo $r['id']; ?>?');">
                    <input type="hidden" name="kick_room_id" value="<?php echo $r['id']; ?>">
                    <input type="hidden" name="kick_user_id" value="<?php echo $m['user_id']; ?>">
                    <button class="action-btn delete" type="submit">Kick</button>
                  </form>
                </li>
              <?php endwhile; ?>
            </ul>
          </td>
          <td>
            <?php
              $msg = $conn->prepare("SELECT m.created_at, u.username, m.message FROM room_messages m JOIN users u ON m.user_id=u.id WHERE m.room_id = ? ORDER BY m.created_at DESC LIMIT 10");
              $msg->bind_param("i", $r['id']);
              $msg->execute();
              $messagesRes = $msg->get_result();
            ?>
            <div style="max-height:160px; overflow:auto; border:1px solid #eee; border-radius:8px; padding:8px; background:#fafafa;">
              <?php if($messagesRes->num_rows === 0): ?>
                <div class="small">No messages</div>
              <?php else: while($mm = $messagesRes->fetch_assoc()): ?>
                <div class="small"><strong><?php echo htmlspecialchars($mm['username']); ?>:</strong> <?php echo htmlspecialchars($mm['message']); ?> <span style="float:right; opacity:0.7;"><?php echo $mm['created_at']; ?></span></div>
              <?php endwhile; endif; ?>
            </div>
          </td>
          <td>
            <form method="post" onsubmit="return confirm('Delete room #<?php echo $r['id']; ?>?');">
              <input type="hidden" name="delete_room_id" value="<?php echo $r['id']; ?>">
              <button class="action-btn delete" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <div class="admin-card">
    <h3>Manage Slots</h3>
    <div class="note">Add new availability or remove existing slots.</div>
    <form method="post" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
      <div>
        <label>Field</label>
        <select name="field_id" required>
          <option value="1">Oláh Gábor utcai Sportcsarnok</option>
          <option value="2">DEAC University Sports Center</option>
          <option value="3">Debreceni Sportcentrum Közhasznú Nonprofit Kft.</option>
        </select>
      </div>
      <div>
        <label>Slot Time</label>
        <input type="datetime-local" name="slot_time" required>
      </div>
      <div>
        <button class="action-btn" type="submit" name="add_slot" value="1">Add Slot</button>
      </div>
    </form>

    <?php $slots = $conn->query("SELECT id, field_id, slot_time, is_booked FROM availability ORDER BY slot_time DESC LIMIT 100"); ?>
    <table>
      <tr><th>ID</th><th>Field</th><th>Time</th><th>Status</th><th>Action</th></tr>
      <?php while($s = $slots->fetch_assoc()): ?>
        <tr>
          <td><?php echo $s['id']; ?></td>
          <td><?php echo $s['field_id']; ?></td>
          <td><?php echo $s['slot_time']; ?></td>
          <td><?php echo $s['is_booked'] ? 'Booked' : 'Available'; ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Remove slot #<?php echo $s['id']; ?>? This will also clear related bookings/rooms.');">
              <input type="hidden" name="delete_slot_id" value="<?php echo $s['id']; ?>">
              <button class="action-btn delete" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</main>
</body>
</html>
