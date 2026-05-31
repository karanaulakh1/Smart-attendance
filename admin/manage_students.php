<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* ROLE (SUPER ADMIN CHECK) */
$admin_role = $_SESSION['role'] ?? "admin";

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

/* EDIT */
$editData = null;

if(isset($_GET['edit'])){
    $edit_id = $_GET['edit'];

    $editQuery = mysqli_query($conn,"SELECT * FROM students WHERE id='$edit_id'");
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

/* GLOBAL */
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
}

/* TOPBAR MOBILE */
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

/* OVERLAY (IMPORTANT SAME AS DASHBOARD) */
.overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    display:none;
    z-index:4000;
}

.overlay.active{
    display:block;
}

/* SIDEBAR (SAME AS DASHBOARD) */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
    z-index:5000;
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

/* MAIN */
.main{
    margin-left:260px;
    padding:30px;
}

/* TOP BAR */
.topbar{
    display:flex;
    justify-content:space-between;
    margin-bottom:25px;
}

/* SEARCH */
.search-box{
    display:flex;
    gap:10px;
}

.search-box input{
    width:280px;
    padding:12px;
    border-radius:10px;
    border:none;
    outline:none;
}

.search-box button{
    padding:12px 18px;
    border:none;
    background:#2563eb;
    color:white;
    border-radius:10px;
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
    min-width:1000px;
    border-collapse:collapse;
}

th,td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    white-space:nowrap;
}

/* FP BADGE */
.fp{
    background:#10b981;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
}

/* BUTTON LAYOUT FIX */
.action-box{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    align-items:center;
}

/* BUTTONS */
.btn{
    padding:7px 10px;
    font-size:12px;
    border-radius:8px;
    text-decoration:none;
    color:white;
    display:inline-block;
}

.edit{ background:#f59e0b; }
.delete{ background:#ef4444; }
.enroll{ background:#2563eb; }

/* MODAL */
.modal{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.75);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:6000;
}

.modal-content{
    background:#111827;
    padding:30px;
    border-radius:20px;
    width:700px;
    max-width:95%;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

.input-box input{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:none;
}

/* MOBILE FIX */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

.main{
    margin-left:0;
    padding:15px;
}

.sidebar{
    left:-260px;
}

.sidebar.active{
    left:0;
}

.form-grid{
    grid-template-columns:1fr;
}

.search-box input{
    width:100%;
}

/* BUTTONS STACK FIX MOBILE */
.action-box{
    flex-direction:column;
    align-items:flex-start;
}

}

</style>
</head>

<body>

<!-- TOP BAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Manage Students</div>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart<br>Attendance</div>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>

<?php if($admin_role=="superadmin"){ ?>
<a href="admin_management.php">👮 Admin Management</a>
<?php } ?>

<a href="logout.php">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">

<h2>Manage Students</h2>

<form class="search-box">
<input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search">
<button>Search</button>
</form>

</div>

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

<?php while($row=mysqli_fetch_assoc($students)){ ?>

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

<div class="action-box">

<a class="btn enroll"
href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>">
Enroll
</a>

<a class="btn edit"
href="manage_students.php?edit=<?php echo $row['id']; ?>">
Edit
</a>

<a class="btn delete"
href="manage_students.php?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete?')">
Delete
</a>

</div>

</td>
</tr>

<?php } ?>

</table>

</div>

</div>

<script>

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

</script>

</body>
</html>