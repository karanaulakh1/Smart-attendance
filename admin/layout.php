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

<!-- Sidebar -->
<div class="sidebar">
    <h4>📊 Smart Attendance</h4>

    <a href="admin_dashboard.php"><i class="fa fa-chart-bar"></i> Dashboard</a>
    <a href="add_student.php"><i class="fa fa-user-plus"></i> Add Student</a>
    <a href="manage_students.php"><i class="fa fa-users"></i> Manage Students</a>
    <a href="attendance.php"><i class="fa fa-calendar-check"></i> Attendance</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
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