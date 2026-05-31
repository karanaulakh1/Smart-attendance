<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* ROLE */
$admin_role = $_SESSION['role'] ?? '';

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
WHERE student_id LIKE '%$search%'
OR name LIKE '%$search%'
OR department LIKE '%$search%'
ORDER BY id DESC
");

/* EDIT DATA */
$editData = null;

if(isset($_GET['edit'])){
    $edit_id = $_GET['edit'];

    $editQuery = mysqli_query($conn,"
        SELECT * FROM students WHERE id='$edit_id'
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

/* ===== SAME DASHBOARD SIDEBAR STYLE ===== */

body{
    margin:0;
    font-family:Poppins;
    background:#0f172a;
    color:white;
}

.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
    transition:0.3s;
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

/* SUPER ADMIN FIX */
.admin-badge{
    margin-bottom:15px;
    padding:8px 12px;
    background:#334155;
    border-radius:8px;
    font-size:13px;
}

/* MAIN */
.main{
    margin-left:260px;
    padding:30px;
}

/* ACTION BUTTON ROW (STRICT A STYLE) */
.action{
    display:flex;
    gap:10px;
    align-items:center;
}

.btn{
    padding:10px 14px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-size:14px;
    font-weight:600;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    min-width:90px;
}

.edit{background:#f59e0b;}
.delete{background:#ef4444;}
.enroll-btn{
    background:#2563eb;
    border:none;
    min-width:120px;
    cursor:pointer;
}

/* MOBILE (same as dashboard) */
@media(max-width:700px){

.sidebar{
    left:-260px;
    position:fixed;
}

.sidebar.active{
    left:0;
}

.main{
    margin-left:0;
}

}

/* TABLE SAFE */
td{
    vertical-align:middle;
}

</style>
</head>

<body>

<!-- SIDEBAR (DASHBOARD STYLE + SUPERADMIN) -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart<br>Attendance</div>

<?php if($admin_role=="superadmin"){ ?>
<div class="admin-badge">👑 Super Admin</div>
<?php } ?>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>

<?php if($admin_role=="superadmin"){ ?>
<a href="admin_management.php">👮 Admin Management</a>
<?php } ?>

<a href="javascript:void(0);" onclick="confirmLogout()">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">

<div class="title">Manage Students</div>

<form class="search-box">
<input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search students...">
<button type="submit">Search</button>
</form>

</div>

<!-- TABLE -->
<div class="table-card">

<table>
<thead>
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
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($students)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['student_id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['department']; ?></td>
<td><?php echo $row['course']; ?></td>
<td><?php echo $row['year']; ?></td>

<td><span class="fp"><?php echo $row['fingerprint_id']; ?></span></td>

<td>

<div class="action">

<a href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>">
<button class="enroll-btn">Enroll</button>
</a>

<a class="btn edit" href="manage_students.php?edit=<?php echo $row['id']; ?>">
Edit
</a>

<a class="btn delete"
href="manage_students.php?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete Student?')">
Delete
</a>

</div>

</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

<!-- EDIT MODAL (UNCHANGED LOGIC) -->
<?php if($editData){ ?>

<div class="modal">
<div class="modal-content">

<div class="modal-title">Edit Student</div>

<form method="POST">

<input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

<div class="form-grid">
<input type="text" name="student_id" value="<?php echo $editData['student_id']; ?>">
<input type="text" name="name" value="<?php echo $editData['name']; ?>">
<input type="email" name="email" value="<?php echo $editData['email']; ?>">
<input type="text" name="phone" value="<?php echo $editData['phone']; ?>">
<input type="text" name="department" value="<?php echo $editData['department']; ?>">
<input type="text" name="course" value="<?php echo $editData['course']; ?>">
<input type="text" name="year" value="<?php echo $editData['year']; ?>">
<input type="text" name="fingerprint_id" value="<?php echo $editData['fingerprint_id']; ?>">
</div>

<button type="submit" name="update_student" class="save-btn">
Update Student
</button>

</form>

</div>
</div>

<?php } ?>

<script>
function confirmLogout(){
    if(confirm("Are you sure you want to logout?")){
        window.location="logout.php";
    }
}

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}
</script>

</body>
</html>