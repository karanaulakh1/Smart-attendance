<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* DELETE */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn,"DELETE FROM students WHERE id='$id'");
    header("Location: manage_students.php");
    exit();
}

/* UPDATE */
if(isset($_POST['update_student'])){

    $id = $_POST['id'];

    mysqli_query($conn,"UPDATE students SET
        student_id='{$_POST['student_id']}',
        name='{$_POST['name']}',
        email='{$_POST['email']}',
        phone='{$_POST['phone']}',
        department='{$_POST['department']}',
        course='{$_POST['course']}',
        year='{$_POST['year']}',
        fingerprint_id='{$_POST['fingerprint_id']}'
        WHERE id='$id'
    ");

    header("Location: manage_students.php");
    exit();
}

/* SEARCH */
$search = $_GET['search'] ?? "";

$students = mysqli_query($conn,"
SELECT * FROM students
WHERE student_id LIKE '%$search%'
OR name LIKE '%$search%'
OR department LIKE '%$search%'
ORDER BY id DESC
");

/* EDIT */
$editData = null;
if(isset($_GET['edit'])){
    $id = $_GET['edit'];
    $res = mysqli_query($conn,"SELECT * FROM students WHERE id='$id'");
    $editData = mysqli_fetch_assoc($res);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Students</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* ===== GLOBAL ===== */
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins;}
body{
    background:linear-gradient(135deg,#0f172a,#1e293b,#312e81);
    color:white;
    overflow-x:hidden;
}

/* ===== TOPBAR MOBILE ===== */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    padding:15px 20px;
    background:rgba(15,23,42,0.95);
    position:sticky;
    top:0;
    z-index:2000;
}

/* ===== SIDEBAR ===== */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    left:0;
    top:0;
    padding:25px;
    background:linear-gradient(180deg,#0f172a,#1e293b);
    border-right:1px solid rgba(255,255,255,0.08);
}

.logo{
    font-size:28px;
    font-weight:700;
    margin-bottom:40px;
}

.sidebar a{
    display:block;
    padding:14px;
    margin-bottom:8px;
    border-radius:12px;
    text-decoration:none;
    color:white;
}

.sidebar a:hover{
    background:#2563eb;
}

/* ===== MAIN ===== */
.main{
    margin-left:260px;
    padding:30px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.title{
    font-size:30px;
    font-weight:700;
}

/* ===== SEARCH ===== */
.search-box{
    display:flex;
    gap:10px;
}

.search-box input{
    padding:12px;
    border-radius:10px;
    border:none;
    width:260px;
    background:rgba(255,255,255,0.08);
    color:white;
}

.search-box button{
    padding:12px 18px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:white;
}

/* ===== TABLE ===== */
.table-card{
    background:rgba(30,41,59,0.8);
    padding:20px;
    border-radius:20px;
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}

th,td{
    padding:12px;
    text-align:left;
}

/* ===== BUTTON ROW FIX ===== */
.action-box{
    display:flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
}

.btn{
    padding:8px 12px;
    border:none;
    border-radius:8px;
    font-size:12px;
    cursor:pointer;
    text-decoration:none;
    color:white;
}

.enroll{background:#2563eb;}
.delete{background:#ef4444;}
.edit{background:#f59e0b; padding:8px 14px;}

/* ===== MODAL ===== */
.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.7);
    display:flex;
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:#111827;
    padding:25px;
    border-radius:20px;
    width:600px;
}

/* ===== MOBILE ===== */
@media(max-width:768px){

.topbar-mobile{display:flex;}

.sidebar{
    position:fixed;
    left:-280px;
    transition:0.3s;
    z-index:3000;
}

.sidebar.active{
    left:0;
}

.main{
    margin-left:0;
    padding:15px;
}

.topbar{
    flex-direction:column;
    align-items:stretch;
    gap:10px;
}

/* SEARCH MOBILE FIX */
.search-box{
    flex-direction:column;
}

.search-box input{
    width:100%;
}

.action-box{
    flex-direction:row;
    flex-wrap:wrap;
}

}

</style>
</head>

<body>

<!-- MOBILE TOPBAR -->
<div class="topbar-mobile">
    <button onclick="toggleSidebar()" style="font-size:24px;background:none;border:none;color:white;">☰</button>
    <div>Manage Students</div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="logo">📘 Smart<br>Attendance</div>

    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_student.php">Add Student</a>
    <a href="manage_students.php">Manage Students</a>
    <a href="attendance.php">Attendance</a>
    <a href="admin_management.php">Admin</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
    <div class="title">Manage Students</div>

    <form class="search-box">
        <input name="search" value="<?= $search ?>" placeholder="Search">
        <button>Search</button>
    </form>
</div>

<div class="table-card">
<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Dept</th>
<th>Fingerprint</th>
<th>Actions</th>
</tr>

<?php while($row=mysqli_fetch_assoc($students)){ ?>

<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['department'] ?></td>
<td><?= $row['fingerprint_id'] ?></td>

<td>
<div class="action-box">

<a class="btn enroll" href="save_enroll.php?student_id=<?= $row['student_id'] ?>">
Enroll
</a>

<a class="btn delete" href="?delete=<?= $row['id'] ?>">Delete</a>

<a class="btn edit" href="?edit=<?= $row['id'] ?>">Edit</a>

</div>
</td>

</tr>

<?php } ?>

</table>
</div>

</div>

<?php if($editData){ ?>
<div class="modal">
<div class="modal-content">
<h3>Edit Student</h3>

<form method="POST">
<input type="hidden" name="id" value="<?= $editData['id'] ?>">

<input name="name" value="<?= $editData['name'] ?>">
<input name="department" value="<?= $editData['department'] ?>">
<input name="fingerprint_id" value="<?= $editData['fingerprint_id'] ?>">

<button type="submit" name="update_student">Update</button>
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