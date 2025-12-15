<?php
// team_actions.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'not_logged_in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'error'=>'db_connection']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

function json_err($msg){ echo json_encode(['success'=>false,'error'=>$msg]); exit; }

// Create room for a slot (only allowed if user has a booking for that slot)
if ($action === 'create_room') {
    $slot_id = intval($_POST['slot_id'] ?? 0);
    if (!$slot_id) json_err('invalid_slot');

    // Check user has a booking for that slot
    $chk = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND slot_id = ?");
    $chk->bind_param("ii",$user_id,$slot_id);
    $chk->execute();
    $r = $chk->get_result();
    if ($r->num_rows === 0) json_err('no_booking');

    // If room exists, return it
    $chk2 = $conn->prepare("SELECT id FROM rooms WHERE slot_id = ?");
    $chk2->bind_param("i",$slot_id);
    $chk2->execute();
    $res2 = $chk2->get_result();
    if ($res2->num_rows > 0) {
        $row = $res2->fetch_assoc();
        echo json_encode(['success'=>true,'room_id'=>$row['id'],'created'=>false]);
        exit;
    }

    // Create room
    $ins = $conn->prepare("INSERT INTO rooms (slot_id, creator_user_id) VALUES (?, ?)");
    $ins->bind_param("ii",$slot_id,$user_id);
    if (!$ins->execute()) json_err('create_failed');

    $room_id = $conn->insert_id;

    // Add creator as member
    $insm = $conn->prepare("INSERT INTO room_members (room_id, user_id) VALUES (?, ?)");
    $insm->bind_param("ii",$room_id,$user_id);
    $insm->execute();

    echo json_encode(['success'=>true,'room_id'=>$room_id,'created'=>true]);
    exit;
}

// Join a room (room exists linked to slot)
if ($action === 'join') {
    $slot_id = intval($_POST['slot_id'] ?? 0);
    if (!$slot_id) json_err('invalid_slot');

    // find room
    $q = $conn->prepare("SELECT id FROM rooms WHERE slot_id = ?");
    $q->bind_param("i",$slot_id);
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows === 0) json_err('room_not_found');
    $room = $res->fetch_assoc();
    $room_id = intval($room['id']);

    // Check if already a member
    $chk = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ?");
    $chk->bind_param("ii",$room_id,$user_id);
    $chk->execute();
    $r = $chk->get_result();
    if ($r->num_rows > 0) json_err('already_member');

    // Check member count limit (11)
    $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM room_members WHERE room_id = ?");
    $cnt->bind_param("i",$room_id);
    $cnt->execute();
    $cres = $cnt->get_result();
    $c = $cres->fetch_assoc()['c'];
    if ($c >= 11) json_err('room_full');

    // Add
    $ins = $conn->prepare("INSERT INTO room_members (room_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii",$room_id,$user_id);
    if (!$ins->execute()) json_err('join_failed');

    echo json_encode(['success'=>true]);
    exit;
}

// Leave a room
if ($action === 'leave') {
    $slot_id = intval($_POST['slot_id'] ?? 0);
    if (!$slot_id) json_err('invalid_slot');

    $q = $conn->prepare("SELECT id, creator_user_id FROM rooms WHERE slot_id = ?");
    $q->bind_param("i",$slot_id);
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows === 0) json_err('room_not_found');
    $room = $res->fetch_assoc();
    $room_id = $room['id'];

    // Delete member
    $del = $conn->prepare("DELETE FROM room_members WHERE room_id = ? AND user_id = ?");
    $del->bind_param("ii",$room_id,$user_id);
    $del->execute();

    // If no members left, delete room and messages
    $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM room_members WHERE room_id = ?");
    $cnt->bind_param("i",$room_id);
    $cnt->execute();
    $cres = $cnt->get_result();
    $c = $cres->fetch_assoc()['c'];
    if ($c == 0) {
        $conn->prepare("DELETE FROM room_messages WHERE room_id = ?")->bind_param("i",$room_id);
        $conn->prepare("DELETE FROM rooms WHERE id = ?")->bind_param("i",$room_id);
        // execute both
        $conn->query("DELETE FROM room_messages WHERE room_id = ".$room_id);
        $conn->query("DELETE FROM rooms WHERE id = ".$room_id);
    }

    echo json_encode(['success'=>true]);
    exit;
}

// Kick member (only creator can)
if ($action === 'kick') {
    $slot_id = intval($_POST['slot_id'] ?? 0);
    $kick_user = intval($_POST['user_id'] ?? 0);
    if (!$slot_id || !$kick_user) json_err('invalid');

    $q = $conn->prepare("SELECT id, creator_user_id FROM rooms WHERE slot_id = ?");
    $q->bind_param("i",$slot_id);
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows === 0) json_err('room_not_found');
    $room = $res->fetch_assoc();
    $room_id = $room['id'];
    if (intval($room['creator_user_id']) !== $user_id) json_err('not_creator');

    // Remove that member
    $del = $conn->prepare("DELETE FROM room_members WHERE room_id = ? AND user_id = ?");
    $del->bind_param("ii",$room_id,$kick_user);
    $del->execute();

    echo json_encode(['success'=>true]);
    exit;
}

// Send message
if ($action === 'send_message') {
    $slot_id = intval($_POST['slot_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if (!$slot_id || $message === '') json_err('invalid');

    // find room id
    $q = $conn->prepare("SELECT id FROM rooms WHERE slot_id = ?");
    $q->bind_param("i",$slot_id);
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows === 0) json_err('room_not_found');
    $room = $res->fetch_assoc();
    $room_id = $room['id'];

    // Confirm user is a member (optional)
    $chk = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ?");
    $chk->bind_param("ii",$room_id,$user_id);
    $chk->execute();
    $r = $chk->get_result();
    if ($r->num_rows === 0) json_err('not_member');

    $ins = $conn->prepare("INSERT INTO room_messages (room_id, user_id, message) VALUES (?, ?, ?)");
    $ins->bind_param("iis",$room_id,$user_id,$message);
    if (!$ins->execute()) json_err('send_failed');

    echo json_encode(['success'=>true]);
    exit;
}

json_err('unknown_action');
