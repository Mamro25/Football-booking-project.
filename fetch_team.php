<?php
// fetch_team.php
session_start();
header('Content-Type: application/json');

$slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;
if (!$slot_id) { echo json_encode(['success'=>false]); exit; }

$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) { echo json_encode(['success'=>false]); exit; }

// find room
$q = $conn->prepare("SELECT r.id, r.creator_user_id, a.slot_time, a.field_id FROM rooms r JOIN availability a ON r.slot_id = a.id WHERE r.slot_id = ?");
$q->bind_param("i",$slot_id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows === 0) {
    // no room yet
    echo json_encode(['success'=>true,'exists'=>false]);
    exit;
}
$room = $res->fetch_assoc();
$room_id = intval($room['id']);

// members
$members = [];
$m = $conn->prepare("SELECT u.id, u.username, rm.joined_at FROM room_members rm JOIN users u ON rm.user_id = u.id WHERE rm.room_id = ? ORDER BY rm.joined_at ASC");
$m->bind_param("i",$room_id);
$m->execute();
$mr = $m->get_result();
while($row = $mr->fetch_assoc()){
    $members[] = $row;
}

echo json_encode([
    'success'=>true,
    'exists'=>true,
    'room_id'=>$room_id,
    'creator_user_id'=>intval($room['creator_user_id']),
    'slot_time'=>$room['slot_time'],
    'field_id'=>$room['field_id'],
    'members'=>$members,
]);
