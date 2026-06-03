<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];

/* DELETE STUDENT */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    mysqli_query($conn,"DELETE FROM students WHERE id='$id'");
    header("Location: manage_students.php");
    exit();
}

/* UPDATE STUDENT */
if(isset($_POST['update_student'])){
    $id             = $_POST['id'];
    $student_id     = $_POST['student_id'];
    $name           = $_POST['name'];
    $email          = $_POST['email'];
    $phone          = $_POST['phone'];
    $department     = $_POST['department'];
    $course         = $_POST['course'];
    $year           = $_POST['year'];
    $fingerprint_id = $_POST['fingerprint_id'];

    mysqli_query($conn,"
        UPDATE students SET
        student_id='$student_id', name='$name', email='$email',
        phone='$phone', department='$department', course='$course',
        year='$year', fingerprint_id='$fingerprint_id'
        WHERE id='$id'
    ");
    header("Location: manage_students.php");
    exit();
}

/* SEARCH */
$search = isset($_GET['search']) ? $_GET['search'] : "";

$students = mysqli_query($conn,"
    SELECT * FROM students
    WHERE student_id LIKE '%$search%'
    OR name LIKE '%$search%'
    OR department LIKE '%$search%'
    ORDER BY id DESC
");

$total_students = mysqli_query($conn,"SELECT COUNT(*) as c FROM students")->fetch_row()[0];

/* EDIT DATA */
$editData = null;
if(isset($_GET['edit'])){
    $edit_id  = (int)$_GET['edit'];
    $editQuery = mysqli_query($conn,"SELECT * FROM students WHERE id='$edit_id'");
    $editData  = mysqli_fetch_assoc($editQuery);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Manage Students — Smart Attendance</title>
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
    margin-bottom:3px; font-size:14px; font-weight:500;
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
    margin-bottom:28px;
}
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; }

/* ── SEARCH ── */
.search-form{
    display:flex; gap:10px; align-items:center;
}
.search-wrap{
    position:relative;
}
.search-wrap svg{
    position:absolute; left:13px; top:50%;
    transform:translateY(-50%);
    pointer-events:none;
    color:var(--muted);
}
.search-input{
    padding:11px 14px 11px 38px;
    background:var(--surface2);
    border:1px solid var(--border);
    border-radius:12px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px;
    outline:none;
    width:260px;
    transition:.15s;
}
.search-input::placeholder{ color:var(--muted); }
.search-input:focus{ border-color:var(--accent); width:300px; }

.search-btn{
    padding:11px 20px;
    border:none; border-radius:12px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:13px; font-weight:700;
    cursor:pointer; transition:.2s;
    white-space:nowrap;
}
.search-btn:hover{ transform:translateY(-1px); }

/* search meta */
.search-meta{
    font-size:13px; color:var(--muted);
    margin-bottom:16px;
}
.search-meta span{ color:var(--text); font-weight:600; }

/* ── TABLE CARD ── */
.table-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:0;
    overflow:hidden;
}

.table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}

table{
    width:100%; min-width:900px;
    border-collapse:collapse;
}

thead th{
    padding:13px 16px;
    font-size:11px; font-weight:700;
    color:var(--muted);
    text-transform:uppercase; letter-spacing:.8px;
    text-align:left; white-space:nowrap;
    background:var(--surface2);
    border-bottom:1px solid var(--border);
}

tbody td{
    padding:14px 16px;
    font-size:14px;
    border-bottom:1px solid rgba(255,255,255,.03);
    vertical-align:middle;
}
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }

/* student name cell */
.name-cell{ display:flex; flex-direction:column; gap:2px; }
.name-main{ font-weight:600; }
.name-sub{ font-size:11px; color:var(--muted); }

.id-cell{
    font-family:'DM Mono',monospace;
    font-size:12px; color:var(--muted);
}

/* fp badge */
.fp-badge{
    display:inline-flex; align-items:center;
    padding:4px 12px; border-radius:50px;
    font-size:11px; font-weight:700;
    font-family:'DM Mono',monospace;
    background:rgba(34,197,94,.1);
    color:#4ade80;
    border:1px solid rgba(34,197,94,.2);
}
.fp-none{
    background:rgba(100,116,139,.1);
    color:var(--muted);
    border:1px solid rgba(100,116,139,.15);
}

/* ── ACTION BUTTONS ── */
.action{ display:flex; gap:6px; align-items:center; flex-wrap:nowrap; }

.act-btn{
    display:inline-flex; align-items:center; gap:5px;
    padding:6px 12px; border-radius:8px;
    font-size:12px; font-weight:600;
    text-decoration:none; color:#fff;
    border:none; cursor:pointer;
    transition:.15s; white-space:nowrap;
}
.act-btn:hover{ transform:translateY(-1px); }

.btn-enroll{
    background:rgba(59,110,248,.2);
    color:#93c5fd;
    border:1px solid rgba(59,110,248,.3);
}
.btn-enroll:hover{ background:rgba(59,110,248,.35); }

.btn-edit{
    background:rgba(245,158,11,.15);
    color:#fbbf24;
    border:1px solid rgba(245,158,11,.25);
}
.btn-edit:hover{ background:rgba(245,158,11,.3); }

.btn-delete{
    background:rgba(244,63,94,.1);
    color:#fb7185;
    border:1px solid rgba(244,63,94,.2);
}
.btn-delete:hover{ background:rgba(244,63,94,.22); }

/* ── EMPTY STATE ── */
.empty{
    text-align:center; padding:60px 20px;
}
.empty p{ color:var(--muted); font-size:14px; margin-top:10px; }

/* ── MODAL ── */
.modal{
    position:fixed; inset:0;
    background:rgba(0,0,0,.75);
    display:flex; justify-content:center; align-items:center;
    z-index:9000;
    padding:16px;
}
.modal-box{
    width:680px; max-width:100%;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:22px;
    padding:32px;
    max-height:90vh; overflow-y:auto;
    animation:popIn .2s ease;
}
@keyframes popIn{
    from{ opacity:0; transform:scale(.96); }
    to{   opacity:1; transform:scale(1);   }
}
.modal-head{
    display:flex; align-items:center;
    justify-content:space-between;
    margin-bottom:24px;
    padding-bottom:16px;
    border-bottom:1px solid var(--border);
}
.modal-head h2{
    font-size:18px; font-weight:700;
    letter-spacing:-.3px;
}
.modal-close{
    background:var(--surface2);
    border:1px solid var(--border);
    color:var(--muted);
    width:32px; height:32px;
    border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:16px; transition:.15s;
    text-decoration:none;
}
.modal-close:hover{ color:var(--text); background:var(--border); }

.modal-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.m-input-wrap{ display:flex; flex-direction:column; gap:6px; }
.m-input-wrap label{
    font-size:11px; font-weight:700;
    color:var(--muted); text-transform:uppercase; letter-spacing:.8px;
}
.m-input{
    padding:12px 14px;
    background:var(--surface2);
    border:1px solid var(--border);
    border-radius:12px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px; outline:none; transition:.15s;
    width:100%;
}
.m-input:focus{ border-color:var(--accent); }

.modal-save{
    width:100%; margin-top:20px;
    padding:13px;
    border:none; border-radius:12px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700;
    cursor:pointer; transition:.2s;
    box-shadow:0 4px 18px rgba(59,110,248,.25);
}
.modal-save:hover{ transform:translateY(-2px); }

/* ── MOBILE ── */
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }
    .page-title{ font-size:22px; }

    .top-bar{ flex-direction:column; align-items:flex-start; }
    .search-form{ width:100%; }
    .search-wrap{ flex:1; }
    .search-input{ width:100% !important; }

    .modal-grid{ grid-template-columns:1fr; }
    .modal-box{ padding:20px; }

    thead th, tbody td{ padding:11px 10px; font-size:13px; }
    .name-sub{ display:none; }
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
    <a href="manage_students.php" class="active">👨‍🎓 Manage Students</a>
    <a href="attendance.php">🗓️ Attendance</a>
    <?php if($admin_role == "superadmin"){ ?>
    <a href="admin_management.php">👮 Admin Management</a>
    <?php } ?>

    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-title">Manage Students</div>

        <form class="search-form" method="GET">
            <div class="search-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input class="search-input" type="text" name="search"
                       placeholder="Search by name, ID, department..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>

    <!-- SEARCH META -->
    <?php
    $count = mysqli_num_rows($students);
    if($search != ""){
        echo "<div class='search-meta'>Showing <span>$count</span> result".($count!=1?'s':'')." for \"<span>".htmlspecialchars($search)."</span>\" — <a href='manage_students.php' style='color:var(--accent);text-decoration:none;font-weight:600;'>Clear</a></div>";
    } else {
        echo "<div class='search-meta'><span>$total_students</span> student".($total_students!=1?'s':'')." enrolled</div>";
    }
    mysqli_data_seek($students, 0);
    ?>

    <!-- TABLE -->
    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Department / Course</th>
                        <th>Year</th>
                        <th>Fingerprint</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $hasRows = false;
                while($row = mysqli_fetch_assoc($students)):
                    $hasRows = true;
                    $hasFp = !empty($row['fingerprint_id']);
                ?>
                <tr>
                    <td class="id-cell"><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td>
                        <div class="name-cell">
                            <span class="name-main"><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="name-sub"><?php echo htmlspecialchars($row['email']); ?></span>
                        </div>
                    </td>
                    <td style="font-size:13px;color:var(--muted);">
                        <?php echo htmlspecialchars($row['phone']); ?>
                    </td>
                    <td>
                        <div class="name-cell">
                            <span class="name-main" style="font-size:13px;"><?php echo htmlspecialchars($row['department']); ?></span>
                            <span class="name-sub"><?php echo htmlspecialchars($row['course']); ?></span>
                        </div>
                    </td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($row['year']); ?></td>
                    <td>
                        <span class="fp-badge <?php echo $hasFp ? '' : 'fp-none'; ?>">
                            <?php echo $hasFp ? htmlspecialchars($row['fingerprint_id']) : 'Not enrolled'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action">
                            <a href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>"
                               class="act-btn btn-enroll">Enroll</a>
                            <a href="manage_students.php?edit=<?php echo $row['id']; ?>"
                               class="act-btn btn-edit">Edit</a>
                            <a href="manage_students.php?delete=<?php echo $row['id']; ?>"
                               class="act-btn btn-delete"
                               onclick="return confirmDelete('<?php echo htmlspecialchars($row['name']); ?>')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if(!$hasRows): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <p><?php echo $search ? 'No students match your search.' : 'No students enrolled yet.'; ?></p>
                            <?php if($search): ?>
                            <a href="manage_students.php" style="color:var(--accent);font-size:13px;font-weight:600;text-decoration:none;display:inline-block;margin-top:8px;">Clear search</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- EDIT MODAL -->
<?php if($editData): ?>
<div class="modal">
    <div class="modal-box">

        <div class="modal-head">
            <h2>Edit Student</h2>
            <a href="manage_students.php" class="modal-close">✕</a>
        </div>

        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

            <div class="modal-grid">

                <div class="m-input-wrap">
                    <label>Student ID</label>
                    <input class="m-input" type="text" name="student_id"
                           value="<?php echo htmlspecialchars($editData['student_id']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Full Name</label>
                    <input class="m-input" type="text" name="name"
                           value="<?php echo htmlspecialchars($editData['name']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Email</label>
                    <input class="m-input" type="email" name="email"
                           value="<?php echo htmlspecialchars($editData['email']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Phone</label>
                    <input class="m-input" type="text" name="phone"
                           value="<?php echo htmlspecialchars($editData['phone']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Department</label>
                    <input class="m-input" type="text" name="department"
                           value="<?php echo htmlspecialchars($editData['department']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Course</label>
                    <input class="m-input" type="text" name="course"
                           value="<?php echo htmlspecialchars($editData['course']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Year</label>
                    <input class="m-input" type="text" name="year"
                           value="<?php echo htmlspecialchars($editData['year']); ?>">
                </div>

                <div class="m-input-wrap">
                    <label>Fingerprint ID</label>
                    <input class="m-input" type="text" name="fingerprint_id"
                           value="<?php echo htmlspecialchars($editData['fingerprint_id']); ?>">
                </div>

            </div>

            <button type="submit" name="update_student" class="modal-save">
                Save Changes
            </button>
        </form>

    </div>
</div>
<?php endif; ?>

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
    return confirm("Delete student \"" + name + "\"? This cannot be undone.");
}
</script>

</body>
</html>