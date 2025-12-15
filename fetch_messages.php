<?php
// fetch_messages.php
session_start();
header('Content-Type: application/json');

$slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
if (!$slot_id) { echo json_encode(['success'=>false]); exit; }

$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) { echo json_encode(['success'=>false]); exit; }

// get room id
$q = $conn->prepare("SELECT id FROM rooms WHERE slot_id = ?");
$q->bind_param("i",$slot_id);
$q->execute();
$res = $q->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success'=>true,'messages'=>[]]);
    exit;
}
$row = $res->fetch_assoc();
$room_id = $row['id'];

$stmt = $conn->prepare("SELECT m.id, m.user_id, u.username, m.message, m.created_at FROM room_messages m JOIN users u ON m.user_id = u.id WHERE m.room_id = ? ORDER BY m.created_at ASC LIMIT ?");
$stmt->bind_param("ii",$room_id,$limit);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while($r = $result->fetch_assoc()){
    $messages[] = $r;
}

echo json_encode(['success'=>true,'messages'=>$messages]);
