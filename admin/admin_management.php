<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];

/* ADD ADMIN */
if(isset($_POST['add_admin'])){

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $conn->query("
        INSERT INTO admin (username,email,password,role)
        VALUES ('$username','$email','$password','$role')
    ");

    header("Location: admin_management.php");
    exit();
}

/* DELETE ADMIN (ONLY SUPERADMIN SAFE CHECK) */
if(isset($_GET['delete']) && $admin_role=="superadmin"){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM admin WHERE id=$id");

    header("Location: admin_management.php");
    exit();
}

$admins = $conn->query("SELECT * FROM admin");
?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Management</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* ================= GLOBAL ================= */
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:Poppins;
}

body{
    background:#0f172a;
    color:white;
}

/* ================= TOPBAR ================= */
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
.container{
    margin-left:260px;
    padding:30px;
}

/* ================= FORM (SAME AS ADD STUDENT STYLE) ================= */
.form-box{
    background:#1e293b;
    padding:25px;
    border-radius:25px;
    margin-bottom:25px;
    max-width:900px;
}

input,select{
    width:100%;
    padding:14px;
    margin:8px 0;
    border:none;
    border-radius:12px;
    background:#0f172a;
    color:white;
}

button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:#2563eb;
    color:white;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
}

/* ================= TABLE ================= */
table{
    width:100%;
    border-collapse:collapse;
    background:#1e293b;
    border-radius:20px;
    overflow:hidden;
}

th,td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid rgba(255,255,255,0.08);
}

th{
    background:#334155;
}

.delete{
    color:#ef4444;
    text-decoration:none;
}

/* ================= MOBILE ================= */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

/* MAIN FULL WIDTH */
.container{
    margin-left:0;
    padding:15px;
}

/* SIDEBAR SLIDE (LIKE ADD STUDENT) */
.sidebar{
    position:fixed;
    left:-280px;
    top:0;
    height:100vh;
    width:260px;
}

.sidebar.active{
    left:0;
}

/* SMALLER FORM CARD */
.form-box{
    padding:18px;
}

/* TABLE SMALL FIX */
table{
    font-size:13px;
}

}

/* OVERLAY */
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

</style>

</head>

<body>

<!-- TOP BAR -->
<div class="topbar-mobile">
    <button onclick="toggleSidebar()" style="font-size:26px;background:none;border:none;color:white;">☰</button>
    <div>Admin Management</div>
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
<div class="container">

<h2>👮 Admin Management</h2>

<!-- FORM -->
<div class="form-box">

<h3>Add New Admin</h3>

<form method="POST">

<input type="text" name="username" placeholder="Username" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="password" placeholder="Password" required>

<select name="role">
    <option value="admin">Admin</option>
    <option value="superadmin">Super Admin</option>
</select>

<button type="submit" name="add_admin">Add Admin</button>

</form>

</div>

<!-- TABLE -->
<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
<th>Action</th>
</tr>

<?php while($row=$admins->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['username']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['role']; ?></td>

<td>
<?php if($admin_role=="superadmin"){ ?>
<a class="delete" href="?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete admin?')">Delete</a>
<?php } else { ?>
<span style="color:#94a3b8;">No Access</span>
<?php } ?>
</td>

</tr>

<?php } ?>

</table>

</div>

<script>

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

</script>

</body>
</html>