<?php

session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'] ?? 'admin';

/* DELETE STUDENT */
if(isset($_GET['delete'])){

    $id = $_GET['delete'];

    mysqli_query($conn,"
    DELETE FROM students
    WHERE id='$id'
    ");

    header("Location: manage_students.php");
    exit();
}

/* UPDATE STUDENT */
if(isset($_POST['update_student'])){

    $id = $_POST['id'];

    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $course = $_POST['course'];
    $year = $_POST['year'];
    $fingerprint_id = $_POST['fingerprint_id'];

    mysqli_query($conn,"
    UPDATE students SET
    student_id='$student_id',
    name='$name',
    email='$email',
    phone='$phone',
    department='$department',
    course='$course',
    year='$year',
    fingerprint_id='$fingerprint_id'
    WHERE id='$id'
    ");

    header("Location: manage_students.php");
    exit();
}

/* SEARCH */
$search = "";

if(isset($_GET['search'])){
    $search = $_GET['search'];
}

$students = mysqli_query($conn,"
SELECT * FROM students
WHERE
student_id LIKE '%$search%'
OR name LIKE '%$search%'
OR department LIKE '%$search%'
ORDER BY id DESC
");

/* EDIT DATA */
$editData = null;

if(isset($_GET['edit'])){

    $edit_id = $_GET['edit'];

    $editQuery = mysqli_query($conn,"
    SELECT * FROM students
    WHERE id='$edit_id'
    ");

    $editData = mysqli_fetch_assoc($editQuery);
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Manage Students</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
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

/* TOPBAR MOBILE */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    padding:15px;
    background:#1e293b;
}

/* SIDEBAR (MATCH DASHBOARD STYLE) */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
}

.sidebar .logo{
    font-size:28px;
    font-weight:700;
    margin-bottom:30px;
}

.sidebar a{
    display:block;
    padding:12px;
    color:white;
    text-decoration:none;
    border-radius:8px;
}

.sidebar a:hover{
    background:#2563eb;
}

/* SUPER ADMIN CONTROL */
.superadmin-only{
    display:block;
}

/* MAIN */
.main{
    margin-left:260px;
    padding:25px;
}

/* TABLE */
.table-card{
    background:#1e293b;
    padding:20px;
    border-radius:15px;
    overflow-x:auto;
}

table{
    width:100%;
    min-width:1100px;
    border-collapse:collapse;
}

th,td{
    padding:12px;
    text-align:left;
}

/* BUTTONS FIX */
.btn-row{
    display:flex;
    gap:10px;
    align-items:center;
}

.btn{
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    color:white;
    font-size:13px;
    font-weight:600;
    display:inline-block;
    min-width:90px;
    text-align:center;
}

.enroll{ background:#2563eb; }
.edit{ background:#f59e0b; }
.delete{ background:#ef4444; }

/* MODAL */
.modal{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.7);
    display:flex;
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:#111827;
    padding:25px;
    border-radius:15px;
    width:600px;
}

/* MOBILE */
@media(max-width:700px){

.sidebar{
    left:-260px;
    position:fixed;
    transition:0.3s;
}

.sidebar.active{
    left:0;
}

.main{
    margin-left:0;
}

.topbar-mobile{
    display:flex;
}
}
</style>
</head>

<body>

<!-- MOBILE TOPBAR -->
<div class="topbar-mobile">
    <button onclick="toggleSidebar()">☰</button>
    <div>Manage Students</div>
</div>

<!-- SIDEBAR (SUPERADMIN FIXED) -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart Attendance</div>

<a href="admin_dashboard.php">Dashboard</a>
<a href="add_student.php">Add Student</a>
<a href="manage_students.php">Manage Students</a>
<a href="attendance.php">Attendance</a>

<?php if($admin_role === "superadmin"){ ?>
<a href="admin_management.php">Admin Management</a>
<?php } ?>

<a href="logout.php">Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<h2>Manage Students</h2>

<div class="table-card">

<table>

<tr>
<th>ID</th>
<th>Student ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Department</th>
<th>Course</th>
<th>Year</th>
<th>Fingerprint</th>
<th>Actions</th>
</tr>

<?php while($row = mysqli_fetch_assoc($students)){ ?>

<tr>

<td><?= $row['id'] ?></td>
<td><?= $row['student_id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['email'] ?></td>
<td><?= $row['phone'] ?></td>
<td><?= $row['department'] ?></td>
<td><?= $row['course'] ?></td>
<td><?= $row['year'] ?></td>

<td><?= $row['fingerprint_id'] ?></td>

<td>

<div class="btn-row">

<a class="btn enroll"
href="save_enroll.php?student_id=<?= $row['student_id'] ?>">
Enroll
</a>

<a class="btn edit"
href="manage_students.php?edit=<?= $row['id'] ?>">
Edit
</a>

<a class="btn delete"
href="manage_students.php?delete=<?= $row['id'] ?>"
onclick="return confirm('Delete Student?')">
Delete
</a>

</div>

</td>

</tr>

<?php } ?>

</table>

</div>
</div>

<!-- EDIT MODAL -->
<?php if($editData){ ?>
<div class="modal">
<div class="modal-content">

<h3>Edit Student</h3>

<form method="POST">

<input type="hidden" name="id" value="<?= $editData['id'] ?>">

<input name="student_id" value="<?= $editData['student_id'] ?>">
<input name="name" value="<?= $editData['name'] ?>">
<input name="email" value="<?= $editData['email'] ?>">
<input name="phone" value="<?= $editData['phone'] ?>">
<input name="department" value="<?= $editData['department'] ?>">
<input name="course" value="<?= $editData['course'] ?>">
<input name="year" value="<?= $editData['year'] ?>">
<input name="fingerprint_id" value="<?= $editData['fingerprint_id'] ?>">

<button name="update_student">Update</button>

</form>

</div>
</div>
<?php } ?>

<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}
</script>

</body>
</html>