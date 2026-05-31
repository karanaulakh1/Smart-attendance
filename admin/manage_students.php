<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* SUPER ADMIN ROLE */
$admin_role = $_SESSION['role'] ?? 'admin';

/* DELETE STUDENT */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn,"DELETE FROM students WHERE id='$id'");
    header("Location: manage_students.php");
    exit();
}

/* UPDATE STUDENT */
if(isset($_POST['update_student'])){

    $id = $_POST['id'];

    mysqli_query($conn,"
        UPDATE students SET
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
    $edit_id = $_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM students WHERE id='$edit_id'"));
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Students</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* GLOBAL */
*{box-sizing:border-box;}
body{
    margin:0;
    font-family:Poppins;
    background:#0f172a;
    color:white;
    overflow-x:hidden;
}

/* TOPBAR MOBILE */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    align-items:center;
    padding:15px 18px;
    background:#0f172a;
    position:sticky;
    top:0;
    z-index:3000;
}
.hamburger{
    font-size:26px;
    background:none;
    border:none;
    color:white;
}

/* SIDEBAR */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
    z-index:2000;
    transition:0.25s ease;
}

.sidebar a{
    display:block;
    padding:12px;
    color:white;
    text-decoration:none;
    border-radius:10px;
    margin-bottom:8px;
}
.sidebar a:hover{background:#2563eb;}

/* MAIN */
.main{
    margin-left:260px;
    padding:25px;
}

/* TOP */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

/* SEARCH */
.search-box input{
    padding:12px;
    border-radius:10px;
    border:none;
    background:#1e293b;
    color:white;
}
.search-box button{
    padding:12px 18px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:white;
}

/* TABLE */
.table-card{
    background:#1e293b;
    padding:20px;
    border-radius:18px;
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:10px;
    font-size:14px;
    border-bottom:1px solid rgba(255,255,255,0.08);
}

/* FINGERPRINT */
.fp{
    background:#10b981;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
}

/* ACTION BUTTONS */
.actions{
    display:flex;
    flex-direction:column;
    gap:8px;
}

/* row buttons */
.btn-row{
    display:flex;
    gap:8px;
}

.btn{
    padding:8px 10px;
    font-size:12px;
    border-radius:8px;
    text-decoration:none;
    color:white;
    text-align:center;
    flex:1;
}

.enroll{background:#2563eb;}
.delete{background:#ef4444;}
.edit{
    background:#f59e0b;
    width:100%;
}

/* MODAL */
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
    border-radius:15px;
    width:90%;
    max-width:600px;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.input-box input{
    width:100%;
    padding:10px;
    background:#1e293b;
    border:none;
    border-radius:8px;
    color:white;
}

.save-btn{
    width:100%;
    margin-top:15px;
    padding:12px;
    background:#2563eb;
    border:none;
    color:white;
    border-radius:10px;
}

/* MOBILE FIX LIKE DASHBOARD */
@media(max-width:768px){

.topbar-mobile{display:flex;}

.main{margin-left:0;}

.sidebar{
    left:-280px;
    position:fixed;
}

.sidebar.active{
    left:0;
}

/* buttons fix */
.btn-row{
    flex-direction:row;
}

.actions{
    align-items:stretch;
}
}
</style>
</head>

<body>

<!-- MOBILE TOPBAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Manage Students</div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div style="font-size:22px;font-weight:700;margin-bottom:20px;">
📘 Smart Attendance
</div>

<a href="admin_dashboard.php">Dashboard</a>
<a href="add_student.php">Add Student</a>
<a href="manage_students.php">Manage Students</a>

<?php if($admin_role == "superadmin"){ ?>
<a href="admin_management.php">Admin Management</a>
<?php } ?>

<a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<h2>Manage Students</h2>

<form class="search-box">
<input name="search" value="<?php echo $search; ?>" placeholder="Search...">
<button>Search</button>
</form>
</div>

<div class="table-card">
<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Dept</th>
<th>Fingerprint</th>
<th>Actions</th>
</tr>

<?php while($row=mysqli_fetch_assoc($students)){ ?>

<tr>
<td><?php echo $row['student_id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['department']; ?></td>

<td><span class="fp"><?php echo $row['fingerprint_id']; ?></span></td>

<td>

<div class="actions">

<div class="btn-row">

<a class="btn enroll" href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>">Enroll</a>

<a class="btn delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete?')">Delete</a>

</div>

<a class="btn edit" href="?edit=<?php echo $row['id']; ?>">Edit</a>

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

<input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

<div class="form-grid">

<input name="student_id" value="<?php echo $editData['student_id']; ?>">
<input name="name" value="<?php echo $editData['name']; ?>">
<input name="email" value="<?php echo $editData['email']; ?>">
<input name="phone" value="<?php echo $editData['phone']; ?>">
<input name="department" value="<?php echo $editData['department']; ?>">
<input name="course" value="<?php echo $editData['course']; ?>">
<input name="year" value="<?php echo $editData['year']; ?>">
<input name="fingerprint_id" value="<?php echo $editData['fingerprint_id']; ?>">

</div>

<button class="save-btn" name="update_student">Update</button>

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