<?php

$conn = mysqli_connect("gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com","JmzF5kyqRvEwnxn.root","nRavqZA6tYW3LGNf","attendance_system","4000");

if(!$conn){
    die("Connection Failed");
}

$message = "";

if(isset($_POST['add_student'])){

    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $course = $_POST['course'];
    $year = $_POST['year'];
    $fingerprint_id = $_POST['fingerprint_id'];

    $insert = mysqli_query($conn,"

    INSERT INTO students
    (
    student_id,
    name,
    email,
    phone,
    department,
    course,
    year,
    fingerprint_id
    )

    VALUES
    (
    '$student_id',
    '$name',
    '$email',
    '$phone',
    '$department',
    '$course',
    '$year',
    '$fingerprint_id'
    )

    ");

    if($insert){
        $message = "Student Added Successfully!";
    }
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Add Student</title>

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{

    background:
    linear-gradient(
    135deg,
    #0f172a,
    #1e293b,
    #312e81
    );

    min-height:100vh;
    color:white;
}

.container{
    display:flex;
}

/* SIDEBAR */

.sidebar{

    width:260px;

    min-height:100vh;

    background:
    linear-gradient(
    180deg,
    rgba(15,23,42,0.98),
    rgba(30,41,59,0.96)
    );

    backdrop-filter:blur(20px);

    border-right:
    1px solid rgba(255,255,255,0.06);

    padding:28px 18px;

    position:fixed;

    top:0;
    left:0;
}

.logo{

    font-size:30px;

    font-weight:700;

    color:white;

    line-height:1.3;

    margin-bottom:50px;

    padding-left:8px;
}

.menu{

    display:flex;

    flex-direction:column;

    gap:8px;
}

.menu a{

    display:flex;

    align-items:center;

    gap:14px;

    text-decoration:none;

    color:#ffffff;

    font-size:18px;

    font-weight:500;

    padding:16px 18px;

    border-radius:16px;

    transition:0.3s;
}

.menu a:hover{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    transform:translateX(4px);
}

.menu .active{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    box-shadow:
    0 8px 25px rgba(37,99,235,0.35);
}

/* MAIN */

.main{

    margin-left:260px;

    width:calc(100% - 260px);

    padding:40px;
}

.title{

    font-size:40px;

    font-weight:700;

    margin-bottom:30px;
}

/* FORM */

.form-card{

    background:
    rgba(255,255,255,0.08);

    backdrop-filter:blur(18px);

    border-radius:30px;

    padding:40px;

    max-width:1000px;
}

.form-grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:25px;
}

.input-box label{

    display:block;

    margin-bottom:10px;

    color:#cbd5e1;
}

.input-box input{

    width:100%;

    padding:16px;

    border:none;

    border-radius:16px;

    background:
    rgba(255,255,255,0.08);

    color:white;

    font-size:15px;
}

.input-box input::placeholder{

    color:#94a3b8;
}

.submit-btn{

    width:100%;

    margin-top:30px;

    padding:18px;

    border:none;

    border-radius:18px;

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    color:white;

    font-size:18px;

    font-weight:600;

    cursor:pointer;
}

.success{

    background:#10b981;

    padding:16px;

    border-radius:16px;

    margin-bottom:20px;
}

@media(max-width:900px){

.main{
    margin-left:0;
    width:100%;
}

.sidebar{
    position:relative;
    width:100%;
    min-height:auto;
}

.container{
    flex-direction:column;
}

.form-grid{
    grid-template-columns:1fr;
}

}

</style>

</head>

<body>

<div class="container">

<!-- SIDEBAR -->

<div class="sidebar">

<div class="logo">
📘 Smart<br>Attendance
</div>

<div class="menu">

<a href="admin_dashboard.php">
🏠 Dashboard
</a>

<a href="add_student.php" class="active">
➕ Add Student
</a>

<a href="manage_students.php">
👨‍🎓 Manage Students
</a>

<a href="attendance.php">
🗓️ Attendance
</a>

<a href="javascript:void(0);"
class="logout-btn"
onclick="confirmLogout()">

🚪 Logout

</a>

</div>

</div>

<!-- MAIN -->

<div class="main">

<div class="title">
Add New Student
</div>

<?php if($message!=""){ ?>

<div class="success">
<?php echo $message; ?>
</div>

<?php } ?>

<div class="form-card">

<form method="POST">

<div class="form-grid">

<div class="input-box">
<label>Student ID</label>
<input type="text" name="student_id" required>
</div>

<div class="input-box">
<label>Name</label>
<input type="text" name="name" required>
</div>

<div class="input-box">
<label>Email</label>
<input type="email" name="email">
</div>

<div class="input-box">
<label>Phone</label>
<input type="text" name="phone">
</div>

<div class="input-box">
<label>Department</label>
<input type="text" name="department">
</div>

<div class="input-box">
<label>Course</label>
<input type="text" name="course">
</div>

<div class="input-box">
<label>Year</label>
<input type="text" name="year">
</div>

<div class="input-box">
<label>Fingerprint ID</label>
<input type="text" name="fingerprint_id">
</div>

</div>

<button class="submit-btn"
type="submit"
name="add_student">

Add Student

</button>

</form>

</div>

</div>

</div>
<script>

function confirmLogout(){

    let confirmAction = confirm(
    "Are you sure you want to logout?"
    );

    if(confirmAction){

        window.location = "logout.php";

    }

}

</script>

</body>
</html>
</body>
</html>