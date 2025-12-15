<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Use local database like the rest of the app
$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['slot_id'], $_GET['field_id'])) {
    die("❌ Invalid request!");
}

$slot_id = intval($_GET['slot_id']);
$field_id = intval($_GET['field_id']);
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // 0️⃣ Enforce single active booking per user (only one future booking allowed)
    $now = date('Y-m-d H:i:s');
    $chk = $conn->prepare("SELECT b.id FROM bookings b JOIN availability a ON b.slot_id = a.id WHERE b.user_id = ? AND a.slot_time >= ? LIMIT 1 FOR UPDATE");
    $chk->bind_param("is", $user_id, $now);
    $chk->execute();
    $hasActive = $chk->get_result()->num_rows > 0;
    if ($hasActive) {
        throw new Exception("one_active_booking");
    }

    // 1️⃣ Check if slot exists and is available
    $stmt = $conn->prepare("SELECT is_booked FROM availability WHERE id = ? AND field_id = ? FOR UPDATE");
    $stmt->bind_param("ii", $slot_id, $field_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("not_found");
    }

    $slot = $result->fetch_assoc();
    if ($slot['is_booked']) {
        throw new Exception("already_booked");
    }

    // 2️⃣ Update slot to booked (no user_id column)
    $update = $conn->prepare("UPDATE availability SET is_booked = 1 WHERE id = ?");
    $update->bind_param("i", $slot_id);
    $update->execute();

    if ($update->affected_rows === 0) {
        throw new Exception("booking_failed");
    }

    // 3️⃣ Insert into bookings table
    $insert = $conn->prepare("INSERT INTO bookings (user_id, slot_id, booked_at) VALUES (?, ?, NOW())");
    $insert->bind_param("ii", $user_id, $slot_id);
    $insert->execute();

    if ($insert->affected_rows === 0) {
        throw new Exception("booking_failed");
    }

    // Commit transaction
    $conn->commit();

    // Redirect to slots page with success
    header("Location: slots.php?field_id=$field_id&success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback(); // undo any changes
    $error_code = $e->getMessage();
    header("Location: slots.php?field_id=$field_id&error=$error_code");
    exit();
}

$conn->close();
?>
