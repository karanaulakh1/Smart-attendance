<?php

session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* SUPER ADMIN FACTOR */
$role = $_SESSION['role'] ?? 'admin';

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
WHERE student_id LIKE '%$search%'
OR name LIKE '%$search%'
OR department LIKE '%$search%'
ORDER BY id DESC
");

/* EDIT */
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

/* SAME THEME (UNCHANGED) */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

html{overflow-x:hidden;}

body{
    background:linear-gradient(135deg,#0f172a,#1e293b,#312e81);
    min-height:100vh;
    color:white;
    overflow-x:hidden;
}

.container{display:flex;}

/* TOPBAR MOBILE */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    align-items:center;
    padding:15px 20px;
    background:rgba(15,23,42,0.95);
    position:sticky;
    top:0;
    z-index:1000;
}

.hamburger{
    font-size:26px;
    background:none;
    border:none;
    color:white;
    cursor:pointer;
}

/* SIDEBAR (FIXED LIKE ADD STUDENT) */
.sidebar{
    width:260px;
    min-height:100vh;
    background:linear-gradient(180deg,rgba(15,23,42,0.98),rgba(30,41,59,0.96));
    backdrop-filter:blur(20px);
    border-right:1px solid rgba(255,255,255,0.06);
    padding:28px 18px;
    position:fixed;
    top:0;
    left:0;
    transition:0.3s ease;
    z-index:2000;
}

.logo{
    font-size:30px;
    font-weight:700;
    margin-bottom:50px;
    padding-left:8px;
}

/* MENU */
.menu a{
    display:block;
    color:white;
    padding:14px;
    border-radius:12px;
    text-decoration:none;
}

/* MAIN */
.main{
    margin-left:260px;
    width:calc(100% - 260px);
    padding:40px;
}

/* TOPBAR */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.title{
    font-size:34px;
    font-weight:700;
}

/* SEARCH */
.search-box{
    display:flex;
    gap:12px;
}

.search-box input{
    width:280px;
    padding:14px;
    border-radius:14px;
    border:none;
    background:rgba(255,255,255,0.08);
    color:white;
}

.search-box button{
    padding:14px 24px;
    border:none;
    border-radius:14px;
    background:#2563eb;
    color:white;
    font-weight:600;
}

/* TABLE */
.table-card{
    background:rgba(30,41,59,0.9);
    padding:25px;
    border-radius:24px;
    overflow-x:auto;
}

table{
    width:100%;
    min-width:1200px;
    border-collapse:separate;
    border-spacing:0 14px;
}

th{
    text-align:left;
    padding:16px;
    color:#93c5fd;
    font-size:14px;
}

td{
    padding:14px;
}

/* ACTION BUTTON FIX */
.action{
    display:flex;
    gap:6px;
    align-items:center;
}

.btn{
    padding:7px 10px;
    border-radius:8px;
    font-size:11px;
    font-weight:600;
    color:white;
    text-decoration:none;
}

.enroll-btn{background:#2563eb;}
.delete{background:#ef4444;}
.edit{background:#f59e0b;}

/* MOBILE FIX */
@media(max-width:700px){

.topbar-mobile{display:flex;}

.sidebar{
    position:fixed;
    left:-260px;
    height:100%;
    width:260px;
}

.sidebar.active{left:0;}

.main{
    margin-left:0;
    width:100%;
    padding:15px;
}

.search-box{
    flex-direction:column;
}

.search-box input{
    width:100%;
}

}

</style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Manage Students</div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart<br>Attendance</div>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>

<?php if($role === "superadmin"){ ?>
<a href="admin_management.php">👮 Admin Management</a>
<?php } ?>

<a href="logout.php">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">

<div class="title">Manage Students</div>

<form class="search-box">
<input name="search" value="<?php echo $search; ?>" placeholder="Search">
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

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['department']; ?></td>
<td><?php echo $row['fingerprint_id']; ?></td>

<td>
<div class="action">

<a class="btn enroll-btn" href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>">Enroll</a>

<a class="btn delete" href="?delete=<?php echo $row['id']; ?>">Delete</a>

<a class="btn edit" href="?edit=<?php echo $row['id']; ?>">Edit</a>

</div>
</td>

</tr>

<?php } ?>

</table>

</div>

</div>

<!-- JS -->
<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}
</script>

</body>
</html>