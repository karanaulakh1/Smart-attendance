<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

/* ADD ADMIN */
if(isset($_POST['add_admin'])){

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $conn->query("
        INSERT INTO admin (username, email, password, role)
        VALUES ('$username', '$email', '$password', '$role')
    ");

    header("Location: admin_management.php");
    exit();
}

/* DELETE ADMIN */
if(isset($_GET['delete'])){
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
body{
    margin:0;
    font-family:Poppins;
    background:linear-gradient(135deg,#0f172a,#1e293b);
    color:white;
    overflow-x:hidden;
}

/* ================= TOP BAR ================= */
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

/* ================= SIDEBAR (DESKTOP) ================= */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#111827; /* SOLID COLOR */
    padding:25px;
    z-index:2000;
}

.sidebar .logo{
    font-size:26px;
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

/* ================= OVERLAY ================= */
.overlay{
    display:none;
}

/* ================= FORM ================= */
.form-box{
    background:rgba(30,41,59,0.8);
    padding:25px;
    border-radius:20px;
    margin-bottom:30px;
}

input, select{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:none;
}

button{
    padding:12px 18px;
    background:#2563eb;
    border:none;
    color:white;
    border-radius:10px;
    cursor:pointer;
}

/* ================= TABLE ================= */
table{
    width:100%;
    border-collapse:collapse;
    background:rgba(30,41,59,0.8);
    border-radius:20px;
    overflow:hidden;
}

th, td{
    padding:15px;
    border-bottom:1px solid rgba(255,255,255,0.1);
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

.container{
    margin-left:0;
    padding:15px;
}

/* overlay active */
.overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    z-index:4000;
}

.overlay.active{
    display:block;
}

/* POP OUT ANIMATION (NOT SLIDE) */
.sidebar{
    left:50%;
    top:50%;
    transform:translate(-50%,-50%) scale(0.6);
    opacity:0;
    width:85%;
    height:auto;
    border-radius:20px;
    transition:0.25s ease;
}

/* active state */
.sidebar.active{
    transform:translate(-50%,-50%) scale(1);
    opacity:1;
}

}

</style>

</head>

<body>

<!-- MOBILE TOP BAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
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
<a href="admin_management.php">👮 Admin Management</a>

<a href="javascript:void(0);" onclick="confirmLogout()">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="container">

<h2>👮 Admin Management</h2>

<!-- ADD ADMIN -->
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
<th>Password</th>
<th>Role</th>
<th>Action</th>
</tr>

<?php while($row=$admins->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['username']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['password']; ?></td>
<td><?php echo $row['role']; ?></td>
<td>
<a class="delete" href="?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete admin?')">
Delete
</a>
</td>
</tr>

<?php } ?>

</table>

</div>

<!-- JS -->
<script>

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

/* close on outside click */
document.addEventListener("click", function(e){

    let sidebar = document.getElementById("sidebar");
    let button = document.querySelector(".hamburger");

    if(window.innerWidth <= 700){
        if(!sidebar.contains(e.target) && !button.contains(e.target)){
            sidebar.classList.remove("active");
            document.getElementById("overlay").classList.remove("active");
        }
    }

});

/* reset on resize */
window.addEventListener("resize", function(){
    if(window.innerWidth > 700){
        document.getElementById("sidebar").classList.remove("active");
        document.getElementById("overlay").classList.remove("active");
    }
});

</script>

</body>
</html>