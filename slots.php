<?php
session_start();
$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;

// Fetch slots for next 7 days
$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+6 days'));

$sql = "SELECT * FROM availability 
        WHERE field_id=? 
        AND DATE(slot_time) BETWEEN ? AND ? 
        ORDER BY slot_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $field_id, $today, $sevenDaysLater);
$stmt->execute();
$result = $stmt->get_result();

// Organize slots by date
$slotsByDate = [];
while($row = $result->fetch_assoc()){
    $date = date('Y-m-d', strtotime($row['slot_time']));
    if(!isset($slotsByDate[$date])) $slotsByDate[$date] = [];
    $slotsByDate[$date][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Slots</title>
<link rel="stylesheet" href="style.css">
<style>
.day-box { border: 1px solid #ddd; border-radius: 10px; padding: 15px; margin: 20px auto; width: 90%; background: #f9f9f9; box-shadow: 0 3px 8px rgba(0,0,0,0.1); }
.slot { display: flex; justify-content: space-between; margin: 10px 0; }
.book-btn { padding: 6px 12px; background: #28a745; color: #fff; border-radius: 6px; text-decoration: none; cursor: pointer; }
.book-btn:hover { background: #218838; }

/* Modal */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.5); opacity:0; transition: opacity 0.3s ease; }
.modal.show { display:block; opacity:1; }
.modal-content { background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.2); }
.modal-content h2 { margin-bottom:10px; color:#28a745; }
.modal-actions { margin-top:20px; }
.confirm-btn { padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px; }
.confirm-btn:hover { background: #218838; }
.cancel-btn { padding: 8px 15px; background: #ccc; color: black; border: none; border-radius: 5px; cursor: pointer; }
.cancel-btn:hover { background: #aaa; }
.close-btn { position:absolute; top:5px; right:10px; font-weight:bold; cursor:pointer; }
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
    <?php if(isset($_SESSION['user_id'])): ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login / Sign Up</a>
    <?php endif; ?>
  </nav>
</header>

<main>
<?php
if(isset($_GET['success'])) echo "<div style='text-align:center;color:green;margin:10px 0;'>✅ Booking Confirmed!</div>";
if(isset($_GET['error'])) echo "<div style='text-align:center;color:red;margin:10px 0;'>⚠️ Error: ".$_GET['error']."</div>";

if(count($slotsByDate) === 0) {
    echo "<p style='text-align:center;color:red;'>No slots found for this field.</p>";
} else {
    foreach($slotsByDate as $date => $slots){
        echo "<div class='day-box'><h3>".date('l, F j', strtotime($date))."</h3>";
        foreach($slots as $slot){
            $status = $slot['is_booked'] ? "Booked" : "Available";
            echo "<div class='slot'>";
            echo "<span>".date('H:i', strtotime($slot['slot_time']))." - $status</span>";
            if(!$slot['is_booked']){
                if(isset($_SESSION['user_id'])){
                    echo "<a href='#' class='book-btn' onclick='openModal({$slot['id']})'>Book</a>";
                } else {
                    echo "<a href='login.php?redirect=slots.php?field_id=$field_id' class='book-btn'>Login to Book</a>";
                }
            } else {
                // if slot is booked, allow any logged-in user to view/join the team room
                if (isset($_SESSION['user_id'])) {
                    echo "<a href='team.php?slot_id={$slot['id']}' class='book-btn' style='background:var(--accent-gold);color:var(--primary-green);'>Open Team</a>";
                } else {
                    echo "<span class='small'>Booked</span>";
                }
            }
            echo "</div>";
        }
        echo "</div>";
    }
}
?>
</main>

<!-- Booking Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>
        <h2>Confirm Booking</h2>
        <p>Are you sure you want to book this slot?</p>
        <div class="modal-actions">
            <button id="confirmBtn" class="confirm-btn">Confirm</button>
            <button id="cancelBtn" class="cancel-btn">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let selectedSlotId = null;
    const modal = document.getElementById("confirmModal");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const confirmBtn = document.getElementById("confirmBtn");
    const fieldId = <?php echo $field_id; ?>;

    // Open modal when book button is clicked
    window.openModal = function(slotId) {
        selectedSlotId = slotId;
        modal.classList.add("show");
    }

    // Close modal
    function closeModal() {
        selectedSlotId = null;
        modal.classList.remove("show");
    }

    closeModalBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    confirmBtn.addEventListener("click", () => {
        if(selectedSlotId){
            window.location.href = `book.php?slot_id=${selectedSlotId}&field_id=${fieldId}`;
        }
    });

    window.addEventListener("click", (e) => {
        if(e.target === modal) closeModal();
    });
});

// Toast messages for success/error
(function(){
    const params = new URLSearchParams(window.location.search);
    const ok = params.get('success');
    const err = params.get('error');
    if (ok || err) {
        const box = document.createElement('div');
        box.className = 'message-box';
        box.innerHTML = `<span class="close-btn" onclick="this.parentElement.remove()">&times;</span>${ok ? '✅ Booking Confirmed!' : '⚠️ Error: ' + err}`;
        document.body.appendChild(box);
        setTimeout(()=>{ box.remove(); }, 5000);
    }
})();
</script>
</body>
</html>
<?php $conn->close(); ?>
