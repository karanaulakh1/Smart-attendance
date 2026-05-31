<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Attendance Admin</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

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
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
        }

        /* Sidebar */
        .sidebar {
            width: 230px;
            height: 100vh;
            position: fixed;
            background: linear-gradient(180deg, #1d2671, #c33764);
            color: white;
            padding: 20px;
        }

        .sidebar h4 {
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            margin: 10px 0;
            text-decoration: none;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Topbar */
        .topbar {
            margin-left: 230px;
            height: 60px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Content */
        .content {
            margin-left: 230px;
            padding: 20px;
        }

        /* Cards */
        .card-box {
            border-radius: 12px;
            color: white;
            padding: 20px;
            transition: 0.3s;
        }

        .card-box:hover {
            transform: translateY(-5px);
        }

        .bg1 { background: #1d2671; }
        .bg2 { background: #28a745; }
        .bg3 { background: #ff7f50; }
    </style>
</head>

<body>

<!-- HAMBURGER BUTTON -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="mobile-title">Smart Attendance</div>
</div>

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

<!-- Topbar -->
<div class="topbar">
    <h5>Admin Dashboard</h5>
    <div>
        <i class="fa fa-user"></i> <?php echo $_SESSION['admin']; ?>
    </div>
</div>

<!-- Content Start -->
<div class="content">
</div>
</body>
</html>