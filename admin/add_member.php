<?php
session_start();
include '../database.php';
if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit(); }
$admin_role = $_SESSION['role'];

$success = $error = "";

/* FETCH EXISTING GROUPS */
$groups_query = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
$groups = [];
while($g = $groups_query->fetch_assoc()) $groups[] = $g;

/* CREATE NEW GROUP */
if(isset($_POST['create_group'])){
    if($admin_role !== 'superadmin'){
        $error = "Only Super Admins can create new groups.";
    } else {
        $gname  = trim($_POST['group_name']);
        $tname  = strtolower(preg_replace('/[^a-z0-9]/i','_', $gname));
        $attbl  = $tname . '_attendance';

        // check duplicate
        $dup = $conn->query("SELECT id FROM groups_registry WHERE table_name='$tname'");
        if($dup->num_rows > 0){
            $error = "A group with that name already exists.";
        } else {
            // create member table
            $conn->query("CREATE TABLE IF NOT EXISTS `$tname` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(50) UNIQUE,
                name VARCHAR(100),
                email VARCHAR(100),
                phone VARCHAR(20),
                department VARCHAR(100),
                course VARCHAR(100),
                year VARCHAR(50),
                fingerprint_id VARCHAR(50)
            )");
            // create attendance table
            $conn->query("CREATE TABLE IF NOT EXISTS `$attbl` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(50),
                course VARCHAR(100),
                status VARCHAR(20),
                date DATE,
                time VARCHAR(20)
            )");
            // register group
            $conn->query("INSERT INTO groups_registry (group_name, table_name, attendance_table)
                VALUES ('$gname','$tname','$attbl')");
            $success = "Group \"$gname\" created successfully.";
            // refresh groups list
            $groups_query2 = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
            $groups = [];
            while($g = $groups_query2->fetch_assoc()) $groups[] = $g;
        }
    }
}

/* ADD MEMBER */
if(isset($_POST['add_member'])){
    $group_table = $_POST['group_table'];
    $student_id     = $_POST['student_id'];
    $name           = $_POST['name'];
    $email          = $_POST['email'];
    $phone          = $_POST['phone'];
    $department     = $_POST['department'];
    $course         = $_POST['course'];
    $year           = $_POST['year'];
    $fingerprint_id = $_POST['fingerprint_id'];

    // check duplicate
    $dup = $conn->query("SELECT id FROM `$group_table` WHERE student_id='$student_id'");
    if($dup->num_rows > 0){
        $error = "ID \"$student_id\" already exists in this group.";
    } else {
        $ins = $conn->query("INSERT INTO `$group_table`
            (student_id,name,email,phone,department,course,year,fingerprint_id)
            VALUES ('$student_id','$name','$email','$phone','$department','$course','$year','$fingerprint_id')");
        if($ins) $success = "Member added successfully to " . htmlspecialchars($group_table) . ".";
        else     $error   = "Database error: " . $conn->error;
    }
}

$total_members = 0;
foreach($groups as $g){
    $cnt = $conn->query("SELECT COUNT(*) as c FROM `".$g['table_name']."`");
    if($cnt) $total_members += $cnt->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Add Member — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{ --bg:#080d18; --surface:#0f1929; --surface2:#162035; --border:rgba(255,255,255,0.07); --accent:#3b6ef8; --accent2:#6ee7f7; --green:#22c55e; --red:#f43f5e; --amber:#f59e0b; --purple:#a78bfa; --text:#e2e8f0; --muted:#64748b; --sidebar-w:240px; }
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; overflow-x:hidden; }
.mob-bar{ display:none; align-items:center; justify-content:space-between; padding:14px 18px; background:var(--surface); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:800; }
.mob-bar .brand{ font-size:15px; font-weight:700; }
.hamburger{ background:none; border:none; color:var(--text); font-size:22px; cursor:pointer; padding:4px 6px; border-radius:8px; }
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
.main{ margin-left:var(--sidebar-w); padding:36px 40px; min-height:100vh; }
.top-bar{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; margin-bottom:32px; }
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; }
.count-pill{ background:var(--surface2); border:1px solid var(--border); padding:9px 18px; border-radius:50px; font-size:13px; font-weight:500; color:var(--muted); }
.count-pill span{ color:var(--text); font-weight:700; }
.alert{ display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:14px; margin-bottom:24px; font-size:14px; font-weight:500; animation:slideIn .3s ease; max-width:780px; }
@keyframes slideIn{ from{ opacity:0; transform:translateY(-8px); } to{ opacity:1; transform:translateY(0); } }
.alert-ok { background:rgba(34,197,94,.10); border:1px solid rgba(34,197,94,.22); color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.10); border:1px solid rgba(244,63,94,.2);  color:#fb7185; }
.layout{ display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; max-width:1100px; }
.card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:28px; margin-bottom:24px; }
.card-head{ padding-bottom:18px; border-bottom:1px solid var(--border); margin-bottom:24px; }
.card-head h2{ font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
.form-grid{ display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.input-wrap{ display:flex; flex-direction:column; gap:6px; }
.input-wrap label{ font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; }
.f-input, .f-select{ padding:12px 14px; background:var(--surface2); border:1px solid var(--border); border-radius:12px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; outline:none; transition:.15s; width:100%; }
.f-input::placeholder{ color:var(--muted); opacity:.7; }
.f-input:focus, .f-select:focus{ border-color:var(--accent); background:#1a2640; }
.f-select{ appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; background-color:var(--surface2); padding-right:36px; cursor:pointer; }
.form-grid .full{ grid-column:1/-1; }
.submit-btn{ width:100%; margin-top:6px; padding:14px; border:none; border-radius:12px; background:linear-gradient(135deg,var(--accent),#5b8af9); color:#fff; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:.2s; box-shadow:0 4px 18px rgba(59,110,248,.25); }
.submit-btn:hover{ transform:translateY(-2px); }
.req{ display:inline-block; width:6px; height:6px; background:var(--red); border-radius:50%; margin-left:4px; vertical-align:middle; margin-bottom:2px; }
.divider{ border:none; border-top:1px solid var(--border); margin:4px 0; }
.side-panel{ display:flex; flex-direction:column; gap:16px; }
.info-card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:22px; }
.info-card-title{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--muted); margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border); }
.group-list{ display:flex; flex-direction:column; gap:8px; }
.group-item{ display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:var(--surface2); border:1px solid var(--border); border-radius:10px; font-size:13px; }
.group-item-name{ font-weight:600; }
.group-item-count{ font-family:'DM Mono',monospace; font-size:11px; color:var(--muted); }
/* create group section */
.create-group-toggle{ width:100%; padding:11px; border:1px dashed var(--border); border-radius:12px; background:transparent; color:var(--muted); font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600; cursor:pointer; transition:.2s; margin-top:4px; }
.create-group-toggle:hover{ color:var(--text); border-color:rgba(255,255,255,.15); background:var(--surface2); }
.create-group-box{ display:none; margin-top:12px; }
.create-group-box.open{ display:block; }
.create-btn{ width:100%; padding:11px; border:none; border-radius:10px; background:linear-gradient(135deg,var(--purple),#c4b5fd); color:#fff; font-family:'DM Sans',sans-serif; font-size:13px; font-weight:700; cursor:pointer; transition:.2s; margin-top:8px; }
.create-btn:hover{ transform:translateY(-1px); }
.superadmin-note{ font-size:11px; color:var(--muted); margin-top:8px; line-height:1.5; }
@media(max-width:900px){ .layout{ grid-template-columns:1fr; } .side-panel{ order:-1; } }
@media(max-width:768px){ .mob-bar{ display:flex; } .sidebar{ left:-280px; } .sidebar.on{ left:0; } .main{ margin-left:0; padding:16px; } .page-title{ font-size:22px; } .form-grid{ grid-template-columns:1fr; } .form-grid .full{ grid-column:1; } }
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
    <a href="add_member.php" class="active">➕ Add Member</a>
    <a href="manage_memberss.php">👥 Manage Members</a>
    <a href="attendance.php">🗓️ Attendance</a>
    <?php if($admin_role=='superadmin'){ ?><a href="admin_management.php">👮 Admin Management</a><?php } ?>
    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div class="top-bar">
        <div class="page-title">Add Member</div>
        <div class="count-pill"><span><?php echo $total_members; ?></span> members total</div>
    </div>

    <?php if($success){ echo '<div class="alert alert-ok">&#10003; '.htmlspecialchars($success).'</div>'; } ?>
    <?php if($error){   echo '<div class="alert alert-err">&#9888; '.htmlspecialchars($error).'</div>'; } ?>

    <div class="layout">
        <!-- FORM -->
        <div class="card">
            <div class="card-head"><h2>Member Details</h2></div>
            <form method="POST">
                <div class="form-grid">
                    <div class="input-wrap full">
                        <label>Group <span class="req"></span></label>
                        <select class="f-select" name="group_table" required>
                            <?php foreach($groups as $g){ echo '<option value="'.htmlspecialchars($g['table_name']).'">'.htmlspecialchars($g['group_name']).'</option>'; } ?>
                        </select>
                    </div>
                    <div class="input-wrap">
                        <label>Member ID <span class="req"></span></label>
                        <input class="f-input" type="text" name="student_id" placeholder="e.g. STU2024001" required>
                    </div>
                    <div class="input-wrap">
                        <label>Full Name <span class="req"></span></label>
                        <input class="f-input" type="text" name="name" placeholder="e.g. Rahul Sharma" required>
                    </div>
                    <div class="input-wrap">
                        <label>Email</label>
                        <input class="f-input" type="email" name="email" placeholder="member@college.edu">
                    </div>
                    <div class="input-wrap">
                        <label>Phone</label>
                        <input class="f-input" type="text" name="phone" placeholder="e.g. 9876543210">
                    </div>
                    <div class="input-wrap">
                        <label>Department</label>
                        <input class="f-input" type="text" name="department" placeholder="e.g. Computer Science">
                    </div>
                    <div class="input-wrap">
                        <label>Course / Designation</label>
                        <input class="f-input" type="text" name="course" placeholder="e.g. B.Tech / Professor">
                    </div>
                    <div class="input-wrap">
                        <label>Year / Level</label>
                        <input class="f-input" type="text" name="year" placeholder="e.g. 2nd Year">
                    </div>
                    <div class="input-wrap">
                        <label>Fingerprint ID</label>
                        <input class="f-input" type="text" name="fingerprint_id" placeholder="e.g. FP-001">
                    </div>
                    <div class="full">
                        <hr class="divider" style="margin-bottom:18px;">
                        <button type="submit" name="add_member" class="submit-btn">Add Member</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SIDE PANEL -->
        <div class="side-panel">
            <div class="info-card">
                <div class="info-card-title">Active Groups</div>
                <div class="group-list">
                    <?php foreach($groups as $g){
                        $cnt = $conn->query("SELECT COUNT(*) as c FROM `".$g['table_name']."`")->fetch_assoc()['c'];
                        echo '<div class="group-item"><span class="group-item-name">'.htmlspecialchars($g['group_name']).'</span><span class="group-item-count">'.$cnt.' members</span></div>';
                    } ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-title">Create New Group</div>
                <?php if($admin_role === 'superadmin'): ?>
                <button class="create-group-toggle" onclick="toggleCreateGroup()">+ New Group</button>
                <div class="create-group-box" id="createGroupBox">
                    <form method="POST">
                        <div class="input-wrap" style="margin-bottom:8px;">
                            <label>Group Name</label>
                            <input class="f-input" type="text" name="group_name" placeholder="e.g. Security Staff" required>
                        </div>
                        <button type="submit" name="create_group" class="create-btn">Create Group</button>
                    </form>
                </div>
                <?php else: ?>
                <p class="superadmin-note">Only Super Admins can create new groups. Contact your Super Admin to add a new group.</p>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <div class="info-card-title">Note</div>
                <p style="font-size:12px;color:var(--muted);line-height:1.7;">
                    Fields marked <span class="req" style="display:inline-block;vertical-align:middle;"></span> are required.
                    Fingerprint ID can be filled later from
                    <a href="manage_memberss.php" style="color:var(--accent);text-decoration:none;font-weight:600;">Manage Members</a>.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('on'); document.getElementById('overlay').classList.toggle('on'); }
function confirmLogout(){ if(confirm("Logout?")){ window.location="logout.php"; } }
function toggleCreateGroup(){ document.getElementById('createGroupBox').classList.toggle('open'); }
</script>
</body>
</html>