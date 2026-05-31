<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];

$success = "";
$error   = "";

if(isset($_POST['add_student'])){

    $student_id   = $_POST['student_id'];
    $name         = $_POST['name'];
    $email        = $_POST['email'];
    $phone        = $_POST['phone'];
    $department   = $_POST['department'];
    $course       = $_POST['course'];
    $year         = $_POST['year'];
    $fingerprint_id = $_POST['fingerprint_id'];

    // Check duplicate student ID
    $check = $conn->query("SELECT id FROM students WHERE student_id='$student_id'");
    if($check->num_rows > 0){
        $error = "Student ID already exists.";
    } else {
        $insert = mysqli_query($conn,"
            INSERT INTO students (student_id,name,email,phone,department,course,year,fingerprint_id)
            VALUES ('$student_id','$name','$email','$phone','$department','$course','$year','$fingerprint_id')
        ");
        if($insert){
            $success = "Student added successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Add Student — Smart Attendance</title>
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
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; }
.student-count{
    background:var(--surface2);
    border:1px solid var(--border);
    padding:9px 18px; border-radius:50px;
    font-size:13px; font-weight:500;
    color:var(--muted);
}
.student-count span{ color:var(--text); font-weight:700; }

/* ── ALERT ── */
.alert{
    display:flex; align-items:center; gap:10px;
    padding:14px 18px; border-radius:14px;
    margin-bottom:24px;
    font-size:14px; font-weight:500;
    animation:slideIn .3s ease;
    max-width:780px;
}
@keyframes slideIn{
    from{ opacity:0; transform:translateY(-8px); }
    to{   opacity:1; transform:translateY(0);    }
}
.alert-ok { background:rgba(34,197,94,.10);  border:1px solid rgba(34,197,94,.22);  color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.10);  border:1px solid rgba(244,63,94,.2);   color:#fb7185; }

/* ── LAYOUT ── */
.content-row{
    display:grid;
    grid-template-columns:1fr 300px;
    gap:24px;
    align-items:start;
    max-width:1100px;
}

/* ── FORM CARD ── */
.card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:28px;
}
.card-head{
    padding-bottom:18px;
    border-bottom:1px solid var(--border);
    margin-bottom:24px;
}
.card-head h2{
    font-size:14px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
    color:var(--muted);
}

/* ── FORM GRID ── */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}
.input-wrap{ display:flex; flex-direction:column; gap:6px; }
.input-wrap label{
    font-size:11px; font-weight:700;
    color:var(--muted);
    text-transform:uppercase; letter-spacing:.8px;
}
.f-input{
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
.f-input::placeholder{ color:var(--muted); opacity:.7; }
.f-input:focus{ border-color:var(--accent); background:#1a2640; }

.form-grid .full{ grid-column:1 / -1; }

.submit-btn{
    width:100%; margin-top:8px;
    padding:14px;
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
.submit-btn:active{ transform:translateY(0); }

/* ── SIDE PANEL ── */
.side-panel{ display:flex; flex-direction:column; gap:16px; }

.info-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:22px;
}
.info-card-title{
    font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.8px;
    color:var(--muted);
    margin-bottom:16px;
    padding-bottom:12px;
    border-bottom:1px solid var(--border);
}

/* field hints */
.hint-list{ display:flex; flex-direction:column; gap:10px; }
.hint-row{
    display:flex; flex-direction:column; gap:2px;
}
.hint-field{
    font-size:12px; font-weight:700;
    color:var(--text);
}
.hint-desc{
    font-size:11px; color:var(--muted); line-height:1.5;
}

/* required indicator */
.req{
    display:inline-block;
    width:6px; height:6px;
    background:var(--red);
    border-radius:50%;
    margin-left:4px;
    vertical-align:middle;
    margin-bottom:2px;
}

/* divider */
.divider{
    border:none;
    border-top:1px solid var(--border);
    margin:4px 0;
}

/* ── MOBILE ── */
@media(max-width:900px){
    .content-row{
        grid-template-columns:1fr;
    }
    .side-panel{ order:-1; }
}

@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }
    .page-title{ font-size:22px; }
    .form-grid{ grid-template-columns:1fr; }
    .form-grid .full{ grid-column:1; }
}

@media(max-width:420px){
    .page-title{ font-size:20px; }
    .card{ padding:18px; }
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
    <a href="add_student.php" class="active">➕ Add Student</a>
    <a href="manage_students.php">👨‍🎓 Manage Students</a>
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
        <div class="page-title">Add Student</div>
        <div class="student-count">
            <span><?php echo $total_students; ?></span> students enrolled
        </div>
    </div>

    <!-- ALERTS -->
    <?php if($success){ ?>
    <div class="alert alert-ok">&#10003; <?php echo $success; ?></div>
    <?php } ?>
    <?php if($error){ ?>
    <div class="alert alert-err">&#9888; <?php echo $error; ?></div>
    <?php } ?>

    <!-- CONTENT -->
    <div class="content-row">

        <!-- FORM CARD -->
        <div class="card">
            <div class="card-head">
                <h2>Student Details</h2>
            </div>

            <form method="POST">
                <div class="form-grid">

                    <div class="input-wrap">
                        <label>Student ID <span class="req"></span></label>
                        <input class="f-input" type="text" name="student_id"
                               placeholder="e.g. STU2024001" required>
                    </div>

                    <div class="input-wrap">
                        <label>Full Name <span class="req"></span></label>
                        <input class="f-input" type="text" name="name"
                               placeholder="e.g. Rahul Sharma" required>
                    </div>

                    <div class="input-wrap">
                        <label>Email Address</label>
                        <input class="f-input" type="email" name="email"
                               placeholder="student@college.edu">
                    </div>

                    <div class="input-wrap">
                        <label>Phone Number</label>
                        <input class="f-input" type="text" name="phone"
                               placeholder="e.g. 9876543210">
                    </div>

                    <div class="input-wrap">
                        <label>Department</label>
                        <input class="f-input" type="text" name="department"
                               placeholder="e.g. Computer Science">
                    </div>

                    <div class="input-wrap">
                        <label>Course</label>
                        <input class="f-input" type="text" name="course"
                               placeholder="e.g. B.Tech">
                    </div>

                    <div class="input-wrap">
                        <label>Year</label>
                        <input class="f-input" type="text" name="year"
                               placeholder="e.g. 2nd Year">
                    </div>

                    <div class="input-wrap">
                        <label>Fingerprint ID</label>
                        <input class="f-input" type="text" name="fingerprint_id"
                               placeholder="e.g. FP-001">
                    </div>

                    <div class="full">
                        <hr class="divider" style="margin-bottom:18px;">
                        <button type="submit" name="add_student" class="submit-btn">
                            Add Student
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- SIDE PANEL -->
        <div class="side-panel">

            <div class="info-card">
                <div class="info-card-title">Field Guide</div>
                <div class="hint-list">
                    <div class="hint-row">
                        <span class="hint-field">Student ID <span class="req"></span></span>
                        <span class="hint-desc">Unique identifier. Must not already exist in the system.</span>
                    </div>
                    <div class="hint-row">
                        <span class="hint-field">Full Name <span class="req"></span></span>
                        <span class="hint-desc">Enter the student's full legal name.</span>
                    </div>
                    <div class="hint-row">
                        <span class="hint-field">Fingerprint ID</span>
                        <span class="hint-desc">Assign fingerprint after that enroll in Manage student </span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-title">Note</div>
                <p style="font-size:12px;color:var(--muted);line-height:1.7;">
                    Fields marked with <span class="req" style="display:inline-block;vertical-align:middle;"></span>
                    are required. All other fields are optional and can be filled later from
                    <a href="manage_students.php" style="color:var(--accent);text-decoration:none;font-weight:600;">Manage Students</a>.
                </p>
            </div>

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
</script>

</body>
</html>