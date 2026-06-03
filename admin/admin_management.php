<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];

/* ONLY SUPERADMIN CAN ACCESS THIS PAGE */
if($admin_role != "superadmin"){
    header("Location: admin_dashboard.php");
    exit();
}

$success = "";
$error   = "";

/* ADD ADMIN */
if(isset($_POST['add_admin'])){
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $check = $conn->query("SELECT id FROM admin WHERE username='$username'");
    if($check->num_rows > 0){
        $error = "Username already exists.";
    } else {
        $conn->query("
            INSERT INTO admin (username, email, password, role)
            VALUES ('$username','$email','$password','$role')
        ");
        $success = "Admin added successfully.";
    }
}

/* DELETE ADMIN */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    if($id == ($_SESSION['admin_id'] ?? 0)){
        $error = "You cannot delete your own account.";
    } else {
        $conn->query("DELETE FROM admin WHERE id=$id");
        header("Location: admin_management.php?deleted=1");
        exit();
    }
}

if(isset($_GET['deleted'])){ $success = "Admin deleted successfully."; }

/* COUNTS */
$totalAdmins     = $conn->query("SELECT COUNT(*) as c FROM admin")->fetch_assoc()['c'];
$superAdminCount = $conn->query("SELECT COUNT(*) as c FROM admin WHERE role='superadmin'")->fetch_assoc()['c'];
$adminCount      = $conn->query("SELECT COUNT(*) as c FROM admin WHERE role='admin'")->fetch_assoc()['c'];

$admins = $conn->query("SELECT * FROM admin ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Admin Management — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:        #080d18;
    --surface:   #0f1929;
    --surface2:  #162035;
    --border:    rgba(255,255,255,0.07);
    --accent:    #3b6ef8;
    --accent2:   #6ee7f7;
    --green:     #22c55e;
    --red:       #f43f5e;
    --amber:     #f59e0b;
    --purple:    #a78bfa;
    --text:      #e2e8f0;
    --muted:     #64748b;
    --sidebar-w: 240px;
}

*{ margin:0; padding:0; box-sizing:border-box; }

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* ── MOBILE TOPBAR ── */
.mob-bar{
    display:none;
    align-items:center;
    justify-content:space-between;
    padding:14px 18px;
    background:var(--surface);
    border-bottom:1px solid var(--border);
    position:sticky; top:0; z-index:800;
}
.mob-bar .brand{ font-size:15px; font-weight:700; }
.hamburger{
    background:none; border:none; color:var(--text);
    font-size:22px; cursor:pointer;
    padding:4px 6px; border-radius:8px; transition:.15s;
}
.hamburger:hover{ background:var(--surface2); }

/* ── OVERLAY ── */
.overlay{
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.6); z-index:900;
}
.overlay.on{ display:block; }

/* ── SIDEBAR ── */
.sidebar{
    width:var(--sidebar-w);
    height:100vh;
    position:fixed; top:0; left:0;
    background:var(--surface);
    border-right:1px solid var(--border);
    padding:24px 16px;
    z-index:1000;
    transition:.25s ease;
    display:flex; flex-direction:column;
    overflow-y:auto;
}
.sidebar .logo{
    font-size:20px; font-weight:700;
    line-height:1.4; padding:0 6px;
    margin-bottom:32px; letter-spacing:-.3px;
}
.nav-section{
    font-size:10px; font-weight:600;
    color:var(--muted); letter-spacing:1.2px;
    text-transform:uppercase;
    padding:0 8px; margin:20px 0 8px;
}
.sidebar a{
    display:flex; align-items:center; gap:10px;
    color:var(--text); text-decoration:none;
    padding:11px 12px; border-radius:10px;
    margin-bottom:3px;
    font-size:14px; font-weight:500;
    transition:.15s;
}
.sidebar a:hover{ background:var(--surface2); }
.sidebar a.active{ background:var(--accent); color:#fff; font-weight:600; }
.sidebar .spacer{ flex:1; }
.sidebar .logout{ color:#f87171; }
.sidebar .logout:hover{ background:rgba(244,63,94,.12); }

/* ── MAIN ── */
.main{
    margin-left:var(--sidebar-w);
    padding:36px 40px;
    min-height:100vh;
}

/* ── TOP BAR ── */
.top-bar{
    display:flex; align-items:center;
    justify-content:space-between;
    flex-wrap:wrap; gap:14px;
    margin-bottom:32px;
}
.page-title{
    font-size:28px; font-weight:700;
    letter-spacing:-.5px;
}
.superadmin-pill{
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(167,139,250,.12);
    border:1px solid rgba(167,139,250,.25);
    color:var(--purple);
    padding:8px 16px; border-radius:50px;
    font-size:12px; font-weight:600;
    letter-spacing:.4px;
    text-transform:uppercase;
}

/* ── ALERT ── */
.alert{
    display:flex; align-items:center; gap:10px;
    padding:14px 18px; border-radius:14px;
    margin-bottom:22px;
    font-size:14px; font-weight:500;
    animation:slideIn .3s ease;
}
@keyframes slideIn{
    from{ opacity:0; transform:translateY(-8px); }
    to{   opacity:1; transform:translateY(0);    }
}
.alert-ok { background:rgba(34,197,94,.10);  border:1px solid rgba(34,197,94,.22);  color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.10);  border:1px solid rgba(244,63,94,.2);   color:#fb7185; }

/* ── STAT CARDS ── */
.stats-row{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:28px;
}
.stat{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:18px;
    padding:22px 24px;
    position:relative; overflow:hidden;
    transition:.2s;
}
.stat:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.13); }
.stat::before{
    content:''; position:absolute;
    top:0; left:0; right:0; height:3px;
}
.stat.s-total::before { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-super::before { background:linear-gradient(90deg,var(--purple),#c4b5fd); }
.stat.s-admin::before { background:linear-gradient(90deg,var(--amber),#fde68a); }

.stat-val{
    font-size:36px; font-weight:700;
    letter-spacing:-1.5px; line-height:1;
    margin-bottom:6px;
}
.stat-label{
    font-size:11px; color:var(--muted);
    font-weight:600; text-transform:uppercase; letter-spacing:.8px;
}

/* ── CARD ── */
.card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:28px;
    margin-bottom:24px;
}
.card-head{
    display:flex; align-items:center; gap:10px;
    margin-bottom:24px;
    padding-bottom:18px;
    border-bottom:1px solid var(--border);
}
.card-head h2{
    font-size:15px; font-weight:700;
    letter-spacing:-.1px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--muted);
}
.card-head .count-pill{
    margin-left:auto;
    background:var(--surface2);
    border:1px solid var(--border);
    padding:3px 10px; border-radius:50px;
    font-size:11px; font-weight:600; color:var(--muted);
    font-family:'DM Mono',monospace;
}

/* ── FORM GRID ── */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.input-wrap{ display:flex; flex-direction:column; gap:6px; }
.input-wrap label{
    font-size:11px; font-weight:700;
    color:var(--muted); text-transform:uppercase; letter-spacing:.8px;
}
.f-input, .f-select{
    padding:12px 14px;
    background:var(--surface2);
    border:1px solid var(--border);
    border-radius:12px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px;
    outline:none;
    transition:.15s;
    width:100%;
}
.f-input::placeholder{ color:var(--muted); }
.f-input:focus, .f-select:focus{ border-color:var(--accent); }
.f-select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    padding-right:36px;
    cursor:pointer;
}
.form-grid .full{ grid-column:1 / -1; }

.submit-btn{
    width:100%;
    padding:13px;
    border:none; border-radius:12px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700;
    cursor:pointer; transition:.2s;
    letter-spacing:.3px;
    box-shadow:0 4px 18px rgba(59,110,248,.25);
}
.submit-btn:hover{ transform:translateY(-2px); box-shadow:0 6px 22px rgba(59,110,248,.35); }

/* ── TABLE ── */
.table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}
table{
    width:100%; min-width:520px;
    border-collapse:collapse;
}
thead th{
    padding:11px 14px;
    font-size:11px; font-weight:700;
    color:var(--muted);
    text-transform:uppercase; letter-spacing:.8px;
    text-align:left; white-space:nowrap;
    border-bottom:1px solid var(--border);
}
tbody td{
    padding:15px 14px;
    font-size:14px;
    border-bottom:1px solid rgba(255,255,255,.03);
    vertical-align:middle;
}
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.02); }

/* admin cell */
.admin-cell{ display:flex; align-items:center; gap:12px; }
.admin-avatar{
    width:36px; height:36px;
    border-radius:9px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:700;
    flex-shrink:0;
    color:#fff;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
}
.admin-avatar.sa{
    background:linear-gradient(135deg,var(--purple),#c4b5fd);
}
.admin-info{ display:flex; flex-direction:column; gap:2px; }
.admin-name{ font-weight:600; font-size:14px; }
.admin-email{ font-size:11px; color:var(--muted); }
.you-tag{
    font-size:10px; font-weight:700;
    color:var(--accent2);
    background:rgba(110,231,247,.1);
    border:1px solid rgba(110,231,247,.2);
    padding:1px 7px; border-radius:50px;
    margin-left:6px; vertical-align:middle;
    letter-spacing:.3px;
}

/* role badge */
.role-badge{
    display:inline-flex; align-items:center;
    padding:5px 12px; border-radius:50px;
    font-size:11px; font-weight:700; letter-spacing:.4px;
    text-transform:uppercase;
}
.role-super{
    background:rgba(167,139,250,.12);
    color:var(--purple);
    border:1px solid rgba(167,139,250,.22);
}
.role-admin{
    background:rgba(245,158,11,.10);
    color:#fbbf24;
    border:1px solid rgba(245,158,11,.18);
}

/* id cell */
.id-cell{
    font-family:'DM Mono',monospace;
    font-size:12px; color:var(--muted);
}

/* delete button */
.btn-del{
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 14px; border-radius:9px;
    background:rgba(244,63,94,.08);
    border:1px solid rgba(244,63,94,.18);
    color:#fb7185;
    font-size:12px; font-weight:600;
    text-decoration:none;
    transition:.15s;
    letter-spacing:.2px;
}
.btn-del:hover{
    background:rgba(244,63,94,.18);
    transform:translateY(-1px);
}
.no-access{
    font-size:12px; color:var(--muted);
    font-style:italic;
}

/* ── EMPTY ── */
.empty{
    text-align:center; padding:50px 20px;
}
.empty p{ color:var(--muted); font-size:14px; }

/* ── MOBILE ── */
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }

    .stats-row{ grid-template-columns:1fr 1fr; gap:12px; }
    .stat{ padding:16px 18px; }
    .stat-val{ font-size:28px; }

    .page-title{ font-size:22px; }
    .form-grid{ grid-template-columns:1fr; }
    .form-grid .full{ grid-column:1; }

    thead th, tbody td{ padding:11px 10px; font-size:13px; }
    .admin-email{ display:none; }
}

@media(max-width:420px){
    .stats-row{ grid-template-columns:1fr 1fr; }
    .stat-val{ font-size:24px; }
}
</style>
</head>
<body>

<!-- MOBILE BAR -->
<div class="mob-bar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="brand">📘 Smart Attendance</div>
    <div></div>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="logo">📘 Smart<br>Attendance</div>

    <div class="nav-section">Menu</div>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="add_member.php">➕ Add Student</a>
    <a href="manage_students.php">👨‍🎓 Manage Students</a>
    <a href="attendance.php">🗓️ Attendance</a>
    <?php if($admin_role == "superadmin"){ ?>
    <a href="admin_management.php" class="active">👮 Admin Management</a>
    <?php } ?>

    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-title">Admin Management</div>
        <div class="superadmin-pill">Super Admin Panel</div>
    </div>

    <!-- ALERTS -->
    <?php if($success){ ?>
    <div class="alert alert-ok"><?php echo $success; ?></div>
    <?php } ?>
    <?php if($error){ ?>
    <div class="alert alert-err"><?php echo $error; ?></div>
    <?php } ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat s-total">
            <div class="stat-val"><?php echo $totalAdmins; ?></div>
            <div class="stat-label">Total Admins</div>
        </div>
        <div class="stat s-super">
            <div class="stat-val"><?php echo $superAdminCount; ?></div>
            <div class="stat-label">Super Admins</div>
        </div>
        <div class="stat s-admin">
            <div class="stat-val"><?php echo $adminCount; ?></div>
            <div class="stat-label">Admins</div>
        </div>
    </div>

    <!-- ADD ADMIN CARD -->
    <div class="card">
        <div class="card-head">
            <h2>Add New Admin</h2>
        </div>

        <form method="POST">
            <div class="form-grid">

                <div class="input-wrap">
                    <label>Username</label>
                    <input class="f-input" type="text" name="username" placeholder="e.g. john_admin" required>
                </div>

                <div class="input-wrap">
                    <label>Email</label>
                    <input class="f-input" type="email" name="email" placeholder="admin@school.com" required>
                </div>

                <div class="input-wrap">
                    <label>Password</label>
                    <input class="f-input" type="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="input-wrap">
                    <label>Role</label>
                    <select class="f-select" name="role">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>

                <div class="full">
                    <button type="submit" name="add_admin" class="submit-btn">Add Admin</button>
                </div>

            </div>
        </form>
    </div>

    <!-- ADMINS TABLE CARD -->
    <div class="card">
        <div class="card-head">
            <h2>All Admins</h2>
            <span class="count-pill"><?php echo $totalAdmins; ?></span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Admin</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $admins->data_seek(0);
                while($row = $admins->fetch_assoc()):
                    $isSelf  = ($row['id'] == ($_SESSION['admin_id'] ?? 0));
                    $initial = strtoupper(substr($row['username'], 0, 1));
                    $isSuper = $row['role'] == 'superadmin';
                ?>
                <tr>
                    <td class="id-cell"><?php echo str_pad($i++, 2, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <div class="admin-cell">
                            <div class="admin-avatar <?php echo $isSuper ? 'sa' : ''; ?>">
                                <?php echo $initial; ?>
                            </div>
                            <div class="admin-info">
                                <span class="admin-name">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                    <?php if($isSelf){ ?><span class="you-tag">YOU</span><?php } ?>
                                </span>
                                <span class="admin-email"><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge <?php echo $isSuper ? 'role-super' : 'role-admin'; ?>">
                            <?php echo $isSuper ? 'Super Admin' : 'Admin'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if(!$isSelf): ?>
                        <a class="btn-del"
                           href="?delete=<?php echo $row['id']; ?>"
                           onclick="return confirmDelete('<?php echo htmlspecialchars($row['username']); ?>')">
                           Delete
                        </a>
                        <?php else: ?>
                        <span class="no-access">Current session</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if($totalAdmins == 0): ?>
                <tr>
                    <td colspan="4">
                        <div class="empty">
                            <p>No admins found.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('on');
    document.getElementById('overlay').classList.toggle('on');
}
function confirmLogout(){
    if(confirm("Are you sure you want to logout?")){
        window.location = "logout.php";
    }
}
function confirmDelete(name){
    return confirm("Delete admin \"" + name + "\"? This cannot be undone.");
}
</script>

</body>
</html>