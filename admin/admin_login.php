<?php

session_start();
include '../database.php';

$error = "";

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare(
        "SELECT * FROM admin WHERE username=? AND password=?"
    );

    $stmt->bind_param("ss", $username, $password);

    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $admin = $result->fetch_assoc();

        $_SESSION['admin'] = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];

        if(isset($admin['role'])){
            $_SESSION['role'] = $admin['role'];
        }else{
            $_SESSION['role'] = 'admin';
        }

        header("Location: admin_dashboard.php");
        exit();

    }else{

        $error = "Invalid Username or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Poppins',sans-serif;
    min-height:100vh;

    background:
    linear-gradient(rgba(15,23,42,0.88),rgba(15,23,42,0.88)),
    url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1920');

    background-size:cover;
    background-position:center;

    display:flex;
    justify-content:center;
    align-items:center;

    overflow:hidden;
}

/* BACK BUTTON */

.back-home{
    position:absolute;
    top:30px;
    left:30px;
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.1);
    color:white;
    padding:12px 22px;
    border-radius:14px;
    text-decoration:none;
    backdrop-filter:blur(10px);
    transition:0.3s;
}

.back-home:hover{
    background:rgba(255,255,255,0.14);
}

/* LOGIN CARD */

.login-card{
    width:430px;

    background:rgba(255,255,255,0.08);

    border:1px solid rgba(255,255,255,0.1);

    backdrop-filter:blur(18px);

    border-radius:32px;

    padding:45px;

    box-shadow:0 10px 40px rgba(0,0,0,0.35);

    color:white;
}

/* LOGO */

.logo{
    width:90px;
    height:90px;

    background:linear-gradient(135deg,#2563eb,#60a5fa);

    border-radius:50%;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:42px;

    margin:auto;
    margin-bottom:25px;
}

/* TITLE */

.login-title{
    text-align:center;
    font-size:34px;
    font-weight:700;
    margin-bottom:10px;
}

.login-sub{
    text-align:center;
    color:#cbd5e1;
    margin-bottom:35px;
    font-size:15px;
}

/* INPUT */

.form-control{
    height:58px;

    border-radius:16px;

    border:none;

    margin-bottom:22px;

    background:rgba(255,255,255,0.12);

    color:white;

    padding-left:18px;
}

.form-control::placeholder{
    color:#dbeafe;
}

.form-control:focus{
    background:rgba(255,255,255,0.16);
    color:white;
    border:1px solid #60a5fa;
    box-shadow:none;
}

/* BUTTON */

.login-btn{
    width:100%;
    height:58px;

    border:none;

    border-radius:16px;

    background:linear-gradient(135deg,#2563eb,#3b82f6);

    color:white;

    font-size:17px;
    font-weight:600;

    transition:0.3s;
}

.login-btn:hover{
    transform:translateY(-2px);
}

/* ERROR */

.error-box{
    background:rgba(239,68,68,0.18);

    border:1px solid rgba(239,68,68,0.3);

    color:#fecaca;

    padding:14px;

    border-radius:14px;

    margin-bottom:20px;

    text-align:center;
}

/* FOOTER */

.bottom-text{
    text-align:center;
    margin-top:28px;
    color:#cbd5e1;
    font-size:14px;
}

/* MOBILE */

@media(max-width:500px){

.login-card{
    width:92%;
    padding:35px 28px;
}

.login-title{
    font-size:28px;
}

.back-home{
    top:20px;
    left:20px;
}

}

</style>

</head>

<body>

<!-- BACK -->

<a href="../index.php" class="back-home">

← Back to Home

</a>

<!-- LOGIN CARD -->

<div class="login-card">

<div class="logo">

🔐

</div>

<h1 class="login-title">

Admin Login

</h1>

<p class="login-sub">

Smart Attendance Monitoring System

</p>

<?php if($error != ""){ ?>

<div class="error-box">

<?php echo $error; ?>

</div>

<?php } ?>

<form method="POST">

<input type="text"
name="username"
class="form-control"
placeholder="Enter Username"
required>

<input type="password"
name="password"
class="form-control"
placeholder="Enter Password"
required>

<button type="submit"
name="login"
class="login-btn">

Login to Dashboard

</button>

</form>

<div class="bottom-text">

ESP32 • Fingerprint • PHP • MySQL

</div>

</div>

</body>
</html>