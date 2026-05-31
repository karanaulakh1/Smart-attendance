<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role']; // ✅ superadmin check

$message = "";

if(isset($_POST['add_student'])){

    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $course = $_POST['course'];
    $year = $_POST['year'];
    $fingerprint_id = $_POST['fingerprint_id'];

    $insert = mysqli_query($conn,"
    INSERT INTO students
    (student_id,name,email,phone,department,course,year,fingerprint_id)
    VALUES
    ('$student_id','$name','$email','$phone','$department','$course','$year','$fingerprint_id')
    ");

    if($insert){
        $message = "Student Added Successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Add Student</title>

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
}

.hamburger{
    font-size:26px;
    background:none;
    border:none;
    color:white;
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
    z-index:1000;
    transition:0.25s ease;
}

.sidebar .logo{
    font-size:28px;
    font-weight:700;
    margin-bottom:40px;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:14px;
    border-radius:10px;
    margin-bottom:8px;
}

.sidebar a:hover{
    background:#2563eb;
}

/* ================= MAIN ================= */
.main{
    margin-left:260px;
    padding:40px;
}

.title{
    font-size:36px;
    font-weight:700;
    margin-bottom:25px;
}

/* ================= FORM CARD ================= */
.form-card{
    background:#1e293b;
    border-radius:25px;
    padding:40px;
    max-width:1000px;
}

/* GRID */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}

/* INPUT */
.input-box label{
    display:block;
    margin-bottom:8px;
    color:#cbd5e1;
}

.input-box input{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#0f172a;
    color:white;
}

/* BUTTON */
.submit-btn{
    width:100%;
    margin-top:25px;
    padding:16px;
    border:none;
    border-radius:14px;
    background:#2563eb;
    color:white;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}

/* SUCCESS */
.success{
    background:#10b981;
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
}

/* ================= MOBILE FIX ================= */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

/* sidebar behavior like dashboard */
.sidebar{
    position:fixed;
    left:-280px;
    top:0;
    width:260px;
    height:100vh;
    z-index:6000;
}

.sidebar.active{
    left:0;
}

/* main full width */
.main{
    margin-left:0;
    padding:15px;
}

/* title smaller */
.title{
    font-size:24px;
}

/* FORM CARD MOBILE FIX */
.form-card{
    padding:18px;
    border-radius:18px;
}

/* GRID BECOMES SINGLE COLUMN */
.form-grid{
    grid-template-columns:1fr;
    gap:12px;
}

/* inputs smaller */
.input-box input{
    padding:12px;
    font-size:14px;
}

.submit-btn{
    padding:14px;
    font-size:15px;
}
}

</style>

</head>

<body>

<!-- MOBILE TOPBAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Smart Attendance</div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart<br>Attendance</div>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>

<!-- ✅ SUPER ADMIN ONLY -->
<?php if($admin_role=="superadmin"){ ?>
<a href="admin_management.php">👮 Admin Management</a>
<?php } ?>

<a href="javascript:void(0);" onclick="confirmLogout()">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="title">Add New Student</div>

<?php if($message!=""){ ?>
<div class="success"><?php echo $message; ?></div>
<?php } ?>

<div class="form-card">

<form method="POST">

<div class="form-grid">

<div class="input-box">
<label>Student ID</label>
<input type="text" name="student_id" required>
</div>

<div class="input-box">
<label>Name</label>
<input type="text" name="name" required>
</div>

<div class="input-box">
<label>Email</label>
<input type="email" name="email">
</div>

<div class="input-box">
<label>Phone</label>
<input type="text" name="phone">
</div>

<div class="input-box">
<label>Department</label>
<input type="text" name="department">
</div>

<div class="input-box">
<label>Course</label>
<input type="text" name="course">
</div>

<div class="input-box">
<label>Year</label>
<input type="text" name="year">
</div>

<div class="input-box">
<label>Fingerprint ID</label>
<input type="text" name="fingerprint_id">
</div>

</div>

<button class="submit-btn" type="submit" name="add_student">
Add Student
</button>

</form>

</div>

</div>

<script>

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}

function confirmLogout(){
    if(confirm("Are you sure you want to logout?")){
        window.location="logout.php";
    }
}

</script>

</body>
</html>