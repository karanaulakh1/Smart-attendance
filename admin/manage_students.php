<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$role = $_SESSION['role'] ?? 'admin';

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
$search = $_GET['search'] ?? '';

$students = mysqli_query($conn,"
SELECT * FROM students
WHERE student_id LIKE '%$search%'
OR name LIKE '%$search%'
OR department LIKE '%$search%'
ORDER BY id DESC
");

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

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* GLOBAL */
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins;}
body{
    background:linear-gradient(135deg,#0f172a,#1e293b,#312e81);
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
    cursor:pointer;
}

/* SIDEBAR */
.sidebar{
    width:260px;
    min-height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
    z-index:1000;
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

/* TOPBAR */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

/* SEARCH */
.search-box{
    display:flex;
    gap:10px;
}
.search-box input{
    padding:12px;
    border-radius:10px;
    border:none;
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

/* TABLE */
.table-card{
    background:#1e293b;
    padding:20px;
    border-radius:20px;
    overflow-x:auto;
}

table{
    width:100%;
    min-width:1100px;
    border-collapse:collapse;
}
th,td{
    padding:14px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

/* FINGERPRINT */
.fp{
    background:#10b981;
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
}

/* ACTION BUTTONS (IMPROVED) */
.action{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.btn{
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    transition:0.2s;
    display:inline-flex;
    align-items:center;
    gap:5px;
}

.btn:hover{
    transform:scale(1.05);
}

.edit{background:#f59e0b;color:white;}
.delete{background:#ef4444;color:white;}
.enroll{background:#2563eb;color:white;}

/* MODAL */
.modal{
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.7);
    display:flex;
    justify-content:center;
    align-items:center;
}
.modal-content{
    background:#111827;
    padding:30px;
    border-radius:20px;
    width:600px;
}

/* MOBILE FIX */
@media(max-width:768px){

.topbar-mobile{display:flex;}

.main{
    margin-left:0;
    padding:15px;
}

.topbar{
    flex-direction:column;
    align-items:flex-start;
    gap:10px;
}

table{
    min-width:900px;
}

/* buttons smaller on mobile */
.btn{
    font-size:12px;
    padding:7px 10px;
}
}

/* SIDEBAR MOBILE POP */
.overlay{
    display:none;
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.6);
    z-index:2000;
}
.overlay.active{display:block;}

@media(max-width:768px){
.sidebar{
    left:-280px;
    transition:0.25s ease;
    z-index:3000;
}
.sidebar.active{
    left:0;
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

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div style="font-size:28px;font-weight:700;margin-bottom:20px;">📘 Smart Attendance</div>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>

<?php if($role=="superadmin"){ ?>
<a href="admin_management.php">👮 Admin Management</a>
<?php } ?>

<a href="logout.php">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">

<h2>Manage Students</h2>

<form class="search-box">
<input type="text" name="search" placeholder="Search..." value="<?php echo $search; ?>">
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
<th>Action</th>
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
<div class="action">

<a class="btn enroll" href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>">🔵 Enroll</a>

<a class="btn edit" href="?edit=<?php echo $row['id']; ?>">✏ Edit</a>

<a class="btn delete" onclick="return confirm('Delete?')" href="?delete=<?php echo $row['id']; ?>">🗑 Delete</a>

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