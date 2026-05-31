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

/* FETCH ADMINS */
$admins = $conn->query("SELECT * FROM admin");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Management</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* MOBILE TOPBAR */
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

/* SIDEBAR ANIMATION */
.sidebar{
    transition:0.3s ease;
}

/* MOBILE */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

.sidebar{
    position:fixed;
    left:-260px;
    top:0;
    height:100%;
    z-index:999;
}

.sidebar.active{
    left:0;
}

.main{
    margin-left:0;
}
}
body{
    margin:0;
    font-family:Poppins;
    background:linear-gradient(135deg,#0f172a,#1e293b);
    color:white;
}

/* CONTAINER */
.container{
    padding:30px;
    margin-left:260px;
}

/* FORM */
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

/* BUTTON */
button{
    padding:12px 18px;
    background:#2563eb;
    border:none;
    color:white;
    border-radius:10px;
    cursor:pointer;
}

/* TABLE */
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


/* DELETE */
.delete{
    color:#ef4444;
    text-decoration:none;
}

/* MOBILE */
@media(max-width:700px){
    .container{
        margin-left:0;
        padding:15px;
    }
}

</style>
</head>

<body>

<div class="container">

<h2>👮 Admin Management</h2>

<!-- ADD ADMIN FORM -->
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

<!-- ADMIN TABLE -->
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
        onclick="return confirm('Delete this admin?')">
        Delete
        </a>
    </td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>