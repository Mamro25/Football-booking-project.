<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // redirect to login with redirect back
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

$conn = new mysqli("sql201.infinityfree.com", "if0_40087946", "GlPUQ2J2DqU67", "if0_40087946_football_booking");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Team Rooms</title>
<link rel="stylesheet" href="style.css">
<style>
.container { width: 95%; max-width: 1000px; margin: 2rem auto; }
.room-card { background: var(--white); border-radius: 12px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 1rem; }
.small { font-size:0.9rem; color:#666; }
.member-list { list-style:none; padding:0; margin:0; }
.member-list li { padding:8px 10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
.join-btn, .leave-btn, .create-btn, .kick-btn { padding:8px 12px; border-radius:8px; cursor:pointer; border:none; }
.join-btn { background: var(--primary-green); color:white; }
.leave-btn { background:#dc3545; color:white; }
.create-btn { background: var(--accent-gold); color: var(--primary-green); }
.chat-box { border:1px solid #eee; border-radius:8px; padding:10px; max-height:320px; overflow:auto; background:#fafafa; }
.chat-input { display:flex; gap:8px; margin-top:8px; }
.chat-input input { flex:1; padding:10px; border-radius:8px; border:1px solid #ddd; }
.chat-message { padding:6px 8px; margin:6px 0; border-radius:8px; background:#fff; box-shadow: 0 1px 0 rgba(0,0,0,0.03); }
.room-head { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.note { color:#777; font-size:0.9rem; margin-top:8px; }
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
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main class="container">
<?php
if (!$slot_id) {
    // Landing: list user's bookings with Open Team Room links
    $sql = "SELECT b.id AS booking_id, a.id AS slot_id, a.field_id, a.slot_time FROM bookings b JOIN availability a ON b.slot_id = a.id WHERE b.user_id = ? ORDER BY a.slot_time ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    echo "<h2>Your Bookings — Open Team Rooms</h2>";
    if ($res->num_rows === 0) {
        echo "<div class='room-card'><p class='small'>You have no bookings yet. Book a slot first to create a team room.</p></div>";
    } else {
        while ($row = $res->fetch_assoc()) {
            $slot = date('l, F j \\, H:i', strtotime($row['slot_time']));
            $s_id = intval($row['slot_id']);
            echo "<div class='room-card'>";
            echo "<div class='room-head'><strong>$slot</strong><div><a class='create-btn' href='team.php?slot_id=$s_id'>Open Team Room</a></div></div>";
            echo "<p class='small'>Field ID: {$row['field_id']}</p>";
            echo "</div>";
        }
    }
    echo "</main></body></html>";
    $conn->close();
    exit;
}

// If slot_id provided: display room area
// Verify user has booking for that slot (Option B)
// Check if user has booking for this slot (for create permission only)
$chk = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND slot_id = ?");
$chk->bind_param("ii",$user_id,$slot_id);
$chk->execute();
$cr = $chk->get_result();
$hasBooking = ($cr->num_rows > 0);

// Fetch slot info for display
$q = $conn->prepare("SELECT a.slot_time, a.field_id FROM availability a WHERE a.id = ?");
$q->bind_param("i",$slot_id);
$q->execute();
$qres = $q->get_result();
$slotInfo = $qres->fetch_assoc();
$slotTime = date('l, F j \\, H:i', strtotime($slotInfo['slot_time']));
$field_id = intval($slotInfo['field_id']);

?>
<h2>Team Room — <?php echo $slotTime; ?> (Field <?php echo $field_id; ?>)</h2>

<div class="room-card" id="roomArea">
  <div id="roomHeader" class="room-head">
    <div>
      <strong><?php echo $slotTime; ?></strong>
      <div class="small">Field ID: <?php echo $field_id; ?></div>
    </div>
    <div id="roomControls">
      <!-- buttons will be rendered by JS -->
      <span class="note" id="roomNote">Loading room info…</span>
    </div>
  </div>

  <div style="display:flex; gap:20px; margin-top:12px; flex-wrap:wrap;">
    <div style="flex:1; min-width:260px;">
      <h3>Players (<span id="memberCount">0</span>/11)</h3>
      <ul id="members" class="member-list">
        <!-- populated by JS -->
      </ul>
    </div>

    <div style="flex:1; min-width:300px;">
      <h3>Chat</h3>
      <div id="chat" class="chat-box">
        <!-- messages -->
      </div>

      <div class="chat-input">
        <input type="text" id="msgInput" placeholder="Write a message..." autocomplete="off">
        <button id="sendMsgBtn" class="join-btn">Send</button>
      </div>
      <div class="small note">Only members can send messages. Messages refresh every 3s.</div>
    </div>
  </div>
</div>

<script>
const SLOT_ID = <?php echo $slot_id; ?>;
const CURRENT_USER_ID = <?php echo $user_id; ?>;
const CURRENT_USERNAME = "<?php echo addslashes($username); ?>";
const HAS_BOOKING = <?php echo $hasBooking ? 'true' : 'false'; ?>;

let isMember = false;
let isCreator = false;
let refreshTimer = null;

// Fetch room info and members
async function fetchRoom(){
  const resp = await fetch('fetch_team.php?slot_id=' + SLOT_ID);
  const data = await resp.json();
  if (!data.success) return;

  if (!data.exists) {
    // No room yet: allow only users with a booking to create; others can see info
    if (HAS_BOOKING) {
      document.getElementById('roomControls').innerHTML = `<button class="create-btn" onclick="createRoom()">Create Room for this slot</button>`;
      document.getElementById('roomNote').innerText = 'No room exists. Create one to allow others to join.';
    } else {
      document.getElementById('roomControls').innerHTML = `<span class="small">No room exists yet. Only booked users can create it.</span>`;
      document.getElementById('roomNote').innerText = 'Waiting for a booked user to create the room.';
    }
    document.getElementById('members').innerHTML = '<li class="small">No room created yet.</li>';
    document.getElementById('memberCount').innerText = '0';
    isMember = false; isCreator = false;
    clearInterval(refreshTimer);
    return;
  }

  // show members
  const members = data.members;
  isCreator = (data.creator_user_id === CURRENT_USER_ID);
  let memberHTML = '';
  let foundMember = false;
  members.forEach(m => {
    const uid = parseInt(m.id);
    if (uid === CURRENT_USER_ID) foundMember = true;
    let kickBtn = '';
    if (isCreator && uid !== CURRENT_USER_ID) {
      kickBtn = `<button class="kick-btn" onclick="kickMember(${uid})">Kick</button>`;
    }
    memberHTML += `<li><span>${m.username}</span><span>${kickBtn}</span></li>`;
  });

  document.getElementById('members').innerHTML = memberHTML || '<li class="small">No players yet.</li>';
  document.getElementById('memberCount').innerText = members.length;
  isMember = foundMember;

  // Controls
  let controlsHtml = '';
  if (isMember) {
    controlsHtml += `<button class="leave-btn" onclick="leaveRoom()">Leave Room</button>`;
  } else {
    controlsHtml += `<button class="join-btn" onclick="joinRoom()">Join Room</button>`;
  }
  if (isCreator) {
    controlsHtml += ` <span class="small">You are the room creator</span>`;
  }
  document.getElementById('roomControls').innerHTML = controlsHtml;
  document.getElementById('roomNote').innerText = isMember ? 'You have joined this room.' : 'You are not a member. Join to chat.';
}

// create room
async function createRoom(){
  const form = new FormData();
  form.append('action','create_room');
  form.append('slot_id', SLOT_ID);
  const r = await fetch('team_actions.php',{method:'POST',body:form});
  const j = await r.json();
  if (j.success) {
    fetchRoom();
    startRefreshing();
  } else {
    alert('Error: ' + (j.error||'unknown'));
  }
}

// join
async function joinRoom(){
  const form = new FormData();
  form.append('action','join');
  form.append('slot_id', SLOT_ID);
  const r = await fetch('team_actions.php',{method:'POST',body:form});
  const j = await r.json();
  if (j.success) {
    fetchRoom();
    startRefreshing();
  } else {
    alert('Cannot join: ' + (j.error||'unknown'));
  }
}

// leave
async function leaveRoom(){
  if (!confirm('Leave this room?')) return;
  const form = new FormData();
  form.append('action','leave');
  form.append('slot_id', SLOT_ID);
  const r = await fetch('team_actions.php',{method:'POST',body:form});
  const j = await r.json();
  if (j.success) {
    fetchRoom();
    // stop refreshing only if no members or room removed
    setTimeout(fetchRoom,500);
  } else alert('Error: ' + (j.error||'unknown'));
}

// kick
async function kickMember(uid){
  if (!confirm('Kick this user?')) return;
  const form = new FormData();
  form.append('action','kick');
  form.append('slot_id', SLOT_ID);
  form.append('user_id', uid);
  const r = await fetch('team_actions.php',{method:'POST',body:form});
  const j = await r.json();
  if (j.success) fetchRoom();
  else alert('Error: ' + (j.error||'unknown'));
}

// chat polling
async function fetchMessages(){
  const r = await fetch('fetch_messages.php?slot_id=' + SLOT_ID + '&limit=200');
  const j = await r.json();
  if (!j.success) return;
  const chat = document.getElementById('chat');
  chat.innerHTML = '';
  j.messages.forEach(m => {
    const el = document.createElement('div');
    el.className = 'chat-message';
    const who = (m.user_id == CURRENT_USER_ID) ? '<strong>You</strong>' : `<strong>${escapeHtml(m.username)}</strong>`;
    el.innerHTML = `${who} <span class="small" style="float:right">${m.created_at}</span><div style="clear:both"></div><div>${escapeHtml(m.message)}</div>`;
    chat.appendChild(el);
  });
  chat.scrollTop = chat.scrollHeight;
}

// send message
document.getElementById('sendMsgBtn').addEventListener('click', async () => {
  const txt = document.getElementById('msgInput').value.trim();
  if (!txt) return;
  const form = new FormData();
  form.append('action','send_message');
  form.append('slot_id', SLOT_ID);
  form.append('message', txt);
  const r = await fetch('team_actions.php',{method:'POST',body:form});
  const j = await r.json();
  if (j.success) {
    document.getElementById('msgInput').value = '';
    fetchMessages();
  } else {
    // inline toast on error
    const box = document.createElement('div');
    box.className = 'message-box';
    box.innerHTML = `<span class="close-btn" onclick="this.parentElement.remove()">&times;</span>Cannot send message: ${escapeHtml(j.error||'unknown')}`;
    document.body.appendChild(box);
    setTimeout(()=>{ box.remove(); }, 4000);
  }
});

// escape helper
function escapeHtml(str) {
  return String(str).replace(/[&<>"'\/]/g, function (s) {
    return ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
      '/': '&#x2F;'
    })[s];
  });
}

function startRefreshing(){
  if (refreshTimer) return;
  fetchMessages();
  refreshTimer = setInterval(()=>{ fetchRoom(); fetchMessages(); }, 3000);
}

// initial fetch
fetchRoom();
startRefreshing();
</script>

</main>
</body>
</html>
<?php $conn->close(); ?>
