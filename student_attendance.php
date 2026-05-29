<?php
error_reporting(0);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Check Attendance</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1d2671, #c33764);
            font-family: 'Segoe UI', sans-serif;
        }

        .box {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
    </style>
</head>

<body>

<div class="box">

    <h4>📊 Check Your Attendance</h4>

    <form method="GET" action="view_attendance.php">
        <input type="text" name="student_id" class="form-control mb-3"
               placeholder="Enter Student ID" required>

        <button class="btn btn-primary w-100">
            View Attendance
        </button>
    </form>

    <a href="index.php" class="btn btn-secondary w-100 mt-3">
        Back to Home
    </a>

</div>

</body>
</html>