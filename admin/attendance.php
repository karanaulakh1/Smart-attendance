<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role']; // superadmin check

/* MARK ATTENDANCE */

if(isset($_POST['mark_attendance'])){

    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    $date = date("Y-m-d");
    $time = date("h:i:s");

    /* CHECK ALREADY MARKED */
    $check = $conn->query("
    SELECT * FROM attendance
    WHERE student_id='$student_id'
    AND date='$date'
    ");

    if($check->num_rows == 0){
        $conn->query("
        INSERT INTO attendance (student_id, status, date, time)
        VALUES ('$student_id', '$status', '$date', '$time')
        ");
        $success = "Attendance Marked Successfully";
    } else {
        $error = "Attendance Already Marked Today";
    }
}

/* FETCH STUDENTS */
$students = $conn->query("SELECT * FROM students ORDER BY id DESC");

/* TODAY ATTENDANCE */
$today = date("Y-m-d");
$attendance = $conn->query("
SELECT attendance.*, students.name
FROM attendance
LEFT JOIN students ON attendance.student_id = students.student_id
WHERE attendance.date='$today'
ORDER BY attendance.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>

<title>Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* ================= GLOBAL ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#0f172a;
    color:white;
    overflow-x:hidden;
    min-height:100vh;
}

/* ================= MOBILE TOPBAR ================= */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    align-items:center;
    padding:15px 20px;
    background:#0f172a;
    position:sticky;
    top:0;
    z-index:5000;
    border-bottom:1px solid rgba(255,255,255,0.06);
}

.hamburger{
    font-size:26px;
    background:none;
    border:none;
    color:white;
    cursor:pointer;
    padding:4px 8px;
}

.mobile-brand{
    font-size:16px;
    font-weight:600;
}

/* ================= OVERLAY ================= */
.overlay{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.55);
    z-index:5500;
}

.overlay.active{
    display:block;
}

/* ================= SIDEBAR ================= */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
    z-index:6000;
    transition:0.25s ease;
    overflow-y:auto;
}

.sidebar .logo{
    font-size:28px;
    font-weight:700;
    margin-bottom:40px;
    line-height:1.3;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:14px;
    border-radius:10px;
    margin-bottom:8px;
    font-size:15px;
    font-weight:500;
    transition:0.2s;
}

.sidebar a:hover,
.sidebar a.active{
    background:#2563eb;
}

/* ================= MAIN ================= */
.main{
    margin-left:260px;
    padding:40px;
}

/* ================= TOP BAR ================= */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:35px;
    flex-wrap:wrap;
    gap:15px;
}

.page-title{
    font-size:34px;
    font-weight:700;
}

.date-box{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.08);
    padding:12px 20px;
    border-radius:14px;
    font-size:14px;
    white-space:nowrap;
}

/* ================= ALERTS ================= */
.alert{
    padding:14px 18px;
    border-radius:14px;
    margin-bottom:20px;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:10px;
}

.alert-success{ background:#059669; }
.alert-error{   background:#dc2626; }

/* ================= CARD ================= */
.card{
    background:linear-gradient(145deg, rgba(30,41,59,0.85), rgba(15,23,42,0.95));
    border:1px solid rgba(255,255,255,0.06);
    border-radius:24px;
    padding:28px;
    backdrop-filter:blur(18px);
    box-shadow:0 10px 40px rgba(0,0,0,0.3);
    margin-bottom:28px;
}

.card-title{
    font-size:22px;
    font-weight:700;
    margin-bottom:22px;
}

/* ================= EXPORT FORM ================= */
.export-row{
    display:flex;
    gap:14px;
    align-items:center;
    flex-wrap:wrap;
    padding-bottom:22px;
    border-bottom:1px solid rgba(255,255,255,0.07);
    margin-bottom:22px;
}

.export-row select{
    flex:1;
    min-width:180px;
    padding:13px 16px;
    border:none;
    border-radius:12px;
    background:#0f172a;
    color:white;
    font-size:14px;
    font-family:'Poppins',sans-serif;
    outline:none;
    cursor:pointer;
}

.export-row button{
    padding:13px 24px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg,#059669,#10b981);
    color:white;
    font-size:14px;
    font-weight:600;
    font-family:'Poppins',sans-serif;
    cursor:pointer;
    transition:0.2s;
    white-space:nowrap;
}

.export-row button:hover{
    transform:translateY(-2px);
}

/* ================= MARK FORM ================= */
.mark-grid{
    display:grid;
    grid-template-columns:1fr 1fr auto;
    gap:16px;
    align-items:end;
}

.mark-grid select{
    width:100%;
    padding:14px 16px;
    border:none;
    border-radius:12px;
    background:#0f172a;
    color:white;
    font-size:14px;
    font-family:'Poppins',sans-serif;
    outline:none;
    cursor:pointer;
}

.mark-btn{
    padding:14px 26px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg,#2563eb,#4f46e5);
    color:white;
    font-size:14px;
    font-weight:600;
    font-family:'Poppins',sans-serif;
    cursor:pointer;
    transition:0.2s;
    white-space:nowrap;
}

.mark-btn:hover{
    transform:translateY(-2px);
}

/* ================= TABLE ================= */
.table-box{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}

table{
    width:100%;
    min-width:520px;
    border-collapse:collapse;
}

th{
    text-align:left;
    padding:14px 16px;
    color:#93c5fd;
    font-size:13px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:0.5px;
    white-space:nowrap;
}

td{
    padding:16px;
    border-top:1px solid rgba(255,255,255,0.05);
    font-size:14px;
}

tbody tr:hover{
    background:rgba(255,255,255,0.03);
}

/* ================= STATUS BADGES ================= */
.badge{
    display:inline-block;
    padding:5px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.badge-present{ background:#059669; }
.badge-absent{  background:#dc2626; }
.badge-late{    background:#d97706; }

/* ================= EMPTY STATE ================= */
.empty-state{
    text-align:center;
    padding:40px 20px;
    color:#64748b;
}

.empty-state .icon{
    font-size:48px;
    margin-bottom:12px;
}

/* ================= MOBILE ================= */
@media(max-width:700px){

    .topbar-mobile{
        display:flex;
    }

    .sidebar{
        left:-280px;
    }

    .sidebar.active{
        left:0;
    }

    .main{
        margin-left:0;
        padding:16px;
    }

    .page-title{
        font-size:24px;
    }

    .top-bar{
        margin-bottom:20px;
    }

    .card{
        padding:18px;
        border-radius:18px;
    }

    /* Stack mark form on mobile */
    .mark-grid{
        grid-template-columns:1fr;
    }

    .mark-btn{
        width:100%;
    }

    /* Stack export form on mobile */
    .export-row{
        flex-direction:column;
        align-items:stretch;
    }

    .export-row select{
        min-width:unset;
        width:100%;
    }

    .export-row button{
        width:100%;
    }

    /* Smaller table text on mobile */
    td, th{
        padding:12px 10px;
        font-size:13px;
    }
}

@media(max-width:400px){
    .page-title{ font-size:20px; }
    .card-title{ font-size:18px; }
}

</style>
</head>

<body>

<!-- MOBILE TOPBAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="mobile-brand">📘 Smart Attendance</div>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

    <div class="logo">📘 Smart<br>Attendance</div>

    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="add_student.php">➕ Add Student</a>
    <a href="manage_students.php">👨‍🎓 Manage Students</a>
    <a href="attendance.php" class="active">🗓️ Attendance</a>

    <?php if($admin_role == "superadmin"){ ?>
    <a href="admin_management.php">👮 Admin Management</a>
    <?php } ?>

    <a href="javascript:void(0);" onclick="confirmLogout()">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-title">Attendance</div>
        <div class="date-box">📅 <?php echo date("d M Y"); ?></div>
    </div>

    <!-- ALERTS -->
    <?php if(isset($success)){ ?>
    <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php } ?>

    <?php if(isset($error)){ ?>
    <div class="alert alert-error">❌ <?php echo $error; ?></div>
    <?php } ?>

    <!-- ACTIONS CARD -->
    <div class="card">

        <!-- EXPORT ROW -->
        <div class="card-title">Export Attendance</div>
        <form method="GET" action="export_excel.php">
            <div class="export-row">
                <select name="course" required>
                    <option value="">Select Course</option>
                    <option value="IOT">IOT</option>
                    <option value="AI">AI</option>
                </select>
                <button type="submit">📥 Export Excel</button>
            </div>
        </form>

        <!-- MARK ATTENDANCE ROW -->
        <div class="card-title">Mark Attendance</div>
        <form method="POST">
            <div class="mark-grid">

                <select name="student_id" required>
                    <option value="">Select Student</option>
                    <?php while($row = $students->fetch_assoc()){ ?>
                    <option value="<?php echo $row['student_id']; ?>">
                        <?php echo $row['name']; ?> (<?php echo $row['student_id']; ?>)
                    </option>
                    <?php } ?>
                </select>

                <select name="status" required>
                    <option value="">Select Status</option>
                    <option value="Present">✅ Present</option>
                    <option value="Absent">❌ Absent</option>
                    <option value="Late">⏰ Late</option>
                </select>

                <button type="submit" name="mark_attendance" class="mark-btn">
                    Mark
                </button>

            </div>
        </form>

    </div>

    <!-- TODAY'S TABLE CARD -->
    <div class="card">

        <div class="card-title">📋 Today's Attendance</div>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $count = 1;
                $hasRows = false;
                while($att = $attendance->fetch_assoc()){
                    $hasRows = true;
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($att['name']); ?></td>
                    <td>
                        <?php
                        $s = $att['status'];
                        if($s == "Present"){
                            echo '<span class="badge badge-present">Present</span>';
                        } elseif($s == "Late"){
                            echo '<span class="badge badge-late">Late</span>';
                        } else {
                            echo '<span class="badge badge-absent">Absent</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo $att['date']; ?></td>
                    <td><?php echo $att['time']; ?></td>
                </tr>
                <?php } ?>

                <?php if(!$hasRows){ ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <div>No attendance marked today yet.</div>
                        </div>
                    </td>
                </tr>
                <?php } ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

function confirmLogout(){
    if(confirm("Are you sure you want to logout?")){
        window.location = "logout.php";
    }
}
</script>

</body>
</html>