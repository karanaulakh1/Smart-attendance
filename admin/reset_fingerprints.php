<?php
/**
 * admin/reset_fingerprints.php
 * Clears fingerprint_id for ALL members in a chosen group.
 * Use this AFTER wiping the physical sensor (type RESET in Serial Monitor)
 * so the database matches the now-empty sensor.
 *
 * Superadmin only — this is destructive.
 */

session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

if($_SESSION['role'] !== 'superadmin'){
    die("Only Super Admins can reset fingerprints.");
}

$success = $error = "";

/* LOAD GROUPS */
$groups_query = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
$groups = [];
while($g = $groups_query->fetch_assoc()) $groups[] = $g;

/* HANDLE REMOTE SENSOR WIPE TRIGGER */
if(isset($_POST['trigger_sensor_wipe'])){
    // Clear any old requests first
    $conn->query("DELETE FROM reset_requests WHERE status='pending'");
    $conn->query("INSERT INTO reset_requests (status, requested_at) VALUES ('pending', NOW())");
    $success = "Reset command sent. The ESP32 will wipe the sensor within a few seconds (check the device screen).";
}

/* CHECK STATUS OF LAST RESET REQUEST */
$reset_status = $conn->query("SELECT * FROM reset_requests ORDER BY id DESC LIMIT 1");
$last_reset = $reset_status ? $reset_status->fetch_assoc() : null;

/* HANDLE RESET */
if(isset($_POST['confirm_reset']) && isset($_POST['group_table'])){
    $group_table = $_POST['group_table'];

    $valid = false;
    $group_label = '';
    foreach($groups as $g){
        if($g['table_name'] === $group_table){
            $valid = true;
            $group_label = $g['group_name'];
            break;
        }
    }

    if(!$valid){
        $error = "Invalid group selected.";
    } else {
        $result = $conn->query("UPDATE `$group_table` SET fingerprint_id = NULL");
        if($result){
            $affected = $conn->affected_rows;
            $success = "Cleared fingerprint_id for $affected member(s) in $group_label.";

            // Also clear any pending enroll requests for this group
            $conn->query("DELETE FROM enroll_requests WHERE group_table='$group_table'");
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Reset Fingerprints — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#080d18; --surface:#0f1929; --surface2:#162035;
    --border:rgba(255,255,255,0.07); --accent:#3b6ef8; --accent2:#6ee7f7;
    --green:#22c55e; --red:#f43f5e; --amber:#f59e0b;
    --text:#e2e8f0; --muted:#64748b; --sidebar-w:240px;
}
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; overflow-x:hidden; }
.mob-bar{ display:none; align-items:center; justify-content:space-between; padding:14px 18px; background:var(--surface); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:800; }
.mob-bar .brand{ font-size:15px; font-weight:700; }
.hamburger{ background:none; border:none; color:var(--text); font-size:22px; cursor:pointer; }
.overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:900; }
.overlay.on{ display:block; }
.sidebar{ width:var(--sidebar-w); height:100vh; position:fixed; top:0; left:0; background:var(--surface); border-right:1px solid var(--border); padding:24px 16px; z-index:1000; transition:.25s ease; display:flex; flex-direction:column; overflow-y:auto; }
.sidebar .logo{ font-size:20px; font-weight:700; line-height:1.4; padding:0 6px; margin-bottom:32px; }
.nav-section{ font-size:10px; font-weight:600; color:var(--muted); letter-spacing:1.2px; text-transform:uppercase; padding:0 8px; margin:20px 0 8px; }
.sidebar a{ display:flex; align-items:center; gap:10px; color:var(--text); text-decoration:none; padding:11px 12px; border-radius:10px; margin-bottom:3px; font-size:14px; font-weight:500; transition:.15s; }
.sidebar a:hover{ background:var(--surface2); }
.sidebar a.active{ background:var(--accent); color:#fff; font-weight:600; }
.sidebar .spacer{ flex:1; }
.sidebar .logout{ color:#f87171; }
.sidebar .logout:hover{ background:rgba(244,63,94,.12); }
.main{ margin-left:var(--sidebar-w); padding:36px 40px; min-height:100vh; max-width:760px; }
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; margin-bottom:8px; }
.page-sub{ font-size:14px; color:var(--muted); margin-bottom:28px; }
.alert{ display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:14px; margin-bottom:24px; font-size:14px; font-weight:500; }
.alert-ok { background:rgba(34,197,94,.10); border:1px solid rgba(34,197,94,.22); color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.10); border:1px solid rgba(244,63,94,.2);  color:#fb7185; }
.warning-box{ background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.2); border-radius:16px; padding:20px 22px; margin-bottom:28px; }
.warning-box-title{ display:flex; align-items:center; gap:10px; font-size:14px; font-weight:700; color:#fbbf24; margin-bottom:10px; }
.warning-box p{ font-size:13px; color:var(--muted); line-height:1.7; }
.warning-box ol{ font-size:13px; color:var(--muted); line-height:1.8; margin:10px 0 0 18px; }
.card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:28px; }
.group-row{ display:flex; align-items:center; justify-content:space-between; padding:16px 18px; background:var(--surface2); border:1px solid var(--border); border-radius:14px; margin-bottom:12px; }
.group-info{ display:flex; flex-direction:column; gap:3px; }
.group-name{ font-weight:700; font-size:15px; }
.group-count{ font-size:12px; color:var(--muted); font-family:'DM Mono',monospace; }
.reset-btn{ padding:10px 20px; border:none; border-radius:10px; background:linear-gradient(135deg,#be123c,var(--red)); color:#fff; font-family:'DM Sans',sans-serif; font-size:13px; font-weight:700; cursor:pointer; transition:.2s; }
.reset-btn:hover{ transform:translateY(-1px); }
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }
    .page-title{ font-size:22px; }
    .group-row{ flex-direction:column; align-items:flex-start; gap:12px; }
    .reset-btn{ width:100%; }
}
</style>
</head>
<body>

<div class="mob-bar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="brand">📘 Smart Attendance</div>
    <div></div>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="logo">📘 Smart<br>Attendance</div>
    <div class="nav-section">Menu</div>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="add_member.php">➕ Add Member</a>
    <a href="manage_members.php">👥 Manage Members</a>
    <a href="attendance.php">🗓️ Attendance</a>
    <a href="admin_management.php">👮 Admin Management</a>
    <a href="reset_fingerprints.php" class="active">🗑️ Reset Fingerprints</a>
    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<div class="main">

    <div class="page-title">Reset Fingerprints</div>
    <div class="page-sub">Clear stored fingerprint IDs in the database to match a freshly wiped sensor.</div>

    <?php if($success){ echo '<div class="alert alert-ok">&#10003; '.htmlspecialchars($success).'</div>'; } ?>
    <?php if($error){   echo '<div class="alert alert-err">&#9888; '.htmlspecialchars($error).'</div>'; } ?>

    <!-- REMOTE SENSOR WIPE -->
    <div class="card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
            <div>
                <div style="font-weight:700;font-size:15px;margin-bottom:4px;">Wipe Sensor Remotely</div>
                <div style="font-size:12px;color:var(--muted);">
                    Sends a command to the ESP32 — no laptop or Serial Monitor needed.
                    <?php if($last_reset): ?>
                        <br>Last request: <?php echo date("d M Y, h:i A", strtotime($last_reset['requested_at'])); ?>
                        — Status:
                        <span style="color:<?php echo $last_reset['status']==='completed' ? '#4ade80' : '#fbbf24'; ?>;font-weight:700;">
                            <?php echo $last_reset['status']==='completed' ? 'Completed ✓' : 'Pending — waiting for device'; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <form method="POST" onsubmit="return confirm('This will wipe ALL fingerprints from the physical sensor as soon as it next connects. This cannot be undone. Continue?');">
                <button type="submit" name="trigger_sensor_wipe" class="reset-btn">Wipe Sensor Now</button>
            </form>
        </div>
    </div>

    <div class="warning-box">
        <div class="warning-box-title">⚠️ Two separate steps for a full reset</div>
        <p>Wiping the sensor (above) only clears the physical fingerprint memory. The database step below is separate and must be done for each group.</p>
        <ol>
            <li>Click <strong style="color:#fbbf24;">Wipe Sensor Now</strong> above — works remotely, device picks it up automatically</li>
            <li>Wait for the device screen to show "SENSOR WIPED"</li>
            <li>Then click Reset below for the matching group to clear database records</li>
            <li>Re-enroll members using the Enroll button in Manage Members</li>
        </ol>
    </div>

    <div class="card">
        <?php foreach($groups as $g):
            $cnt = $conn->query("SELECT COUNT(*) as c FROM `".$g['table_name']."` WHERE fingerprint_id IS NOT NULL AND fingerprint_id != ''")->fetch_assoc()['c'];
        ?>
        <div class="group-row">
            <div class="group-info">
                <span class="group-name"><?php echo htmlspecialchars($g['group_name']); ?></span>
                <span class="group-count"><?php echo $cnt; ?> enrolled fingerprint(s)</span>
            </div>
            <form method="POST" onsubmit="return confirm('This will clear fingerprint_id for ALL members in <?php echo htmlspecialchars($g['group_name']); ?>. This cannot be undone. Continue?');">
                <input type="hidden" name="group_table" value="<?php echo htmlspecialchars($g['table_name']); ?>">
                <button type="submit" name="confirm_reset" class="reset-btn">Reset This Group</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('on'); document.getElementById('overlay').classList.toggle('on'); }
function confirmLogout(){ if(confirm("Logout?")){ window.location="logout.php"; } }
</script>
</body>
</html>