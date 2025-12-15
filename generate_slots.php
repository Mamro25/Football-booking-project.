<?php
$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$fields = [1, 2, 3]; // IDs of your fields
$slots = ["17:00:00", "18:00:00"]; // two daily slots
$daysAhead = 30; // generate 30 days ahead

$today = new DateTime('today');

foreach ($fields as $field) {
    for ($i = 0; $i < $daysAhead; $i++) {
        $date = clone $today;
        $date->modify("+$i days");
        $day = $date->format('Y-m-d');

        foreach ($slots as $slotTime) {
            $fullSlot = "$day $slotTime";

            // Check if slot already exists
            $stmt = $conn->prepare("SELECT id FROM availability WHERE field_id=? AND slot_time=?");
            $stmt->bind_param("is", $field, $fullSlot);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Insert slot
                $insert = $conn->prepare("INSERT INTO availability (field_id, slot_time, is_booked) VALUES (?, ?, 0)");
                $insert->bind_param("is", $field, $fullSlot);
                $insert->execute();
            }
        }
    }
}

$conn->close();
echo "✅ Two daily slots generated successfully.";
?>
