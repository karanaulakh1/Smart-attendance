<?php

$conn = mysqli_connect("localhost","root","","attendance_system");

if(!$conn){
    die("Connection Failed");
}

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

WHERE

student_id LIKE '%$search%'

OR

name LIKE '%$search%'

OR

department LIKE '%$search%'

ORDER BY id DESC

");

/* EDIT DATA */

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

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

html{
    overflow-x:hidden;
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
    overflow-x:hidden;
}

/* CONTAINER */

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

/* LOGO */

.logo{

    font-size:30px;

    font-weight:700;

    color:white;

    line-height:1.3;

    margin-bottom:50px;

    padding-left:8px;
}

/* MENU */

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

/* HOVER */

.menu a:hover{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    transform:translateX(4px);
}

/* ACTIVE */

.menu .active{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    color:white;

    box-shadow:
    0 8px 25px rgba(37,99,235,0.35);
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

    padding:14px 18px;

    border:none;

    border-radius:14px;

    background:rgba(255,255,255,0.08);

    color:white;

    outline:none;
}

.search-box input::placeholder{
    color:#cbd5e1;
}

.search-box button{

    border:none;

    padding:14px 24px;

    border-radius:14px;

    background:linear-gradient(
    135deg,
    #2563eb,
    #4f46e5
    );

    color:white;

    cursor:pointer;

    font-weight:600;
}

/* TABLE CARD */

.table-card{

    background:linear-gradient(
    135deg,
    rgba(30,41,59,0.95),
    rgba(15,23,42,0.95)
    );

    border:1px solid rgba(255,255,255,0.06);

    backdrop-filter:blur(18px);

    border-radius:24px;

    padding:25px;

    overflow-x:auto;

    overflow-y:hidden;

    box-shadow:
    0 10px 40px rgba(0,0,0,0.35);
}

/* TABLE */

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

tbody tr{

    background:linear-gradient(
    135deg,
    rgba(37,99,235,0.12),
    rgba(15,23,42,0.45)
    );

    transition:0.3s;

    border:1px solid rgba(255,255,255,0.05);
}

tbody tr:hover{

    transform:translateY(-2px);

    background:linear-gradient(
    135deg,
    rgba(59,130,246,0.22),
    rgba(30,41,59,0.7)
    );
}

td{

    padding:20px 16px;
}

tbody tr td:first-child{

    border-radius:16px 0 0 16px;
}

tbody tr td:last-child{

    border-radius:0 16px 16px 0;
}

/* BADGES */


.fp{

    background:#10b981;

    padding:8px 14px;

    border-radius:30px;

    font-size:12px;
}

/* BUTTONS */

.action{

    display:flex;

    gap:10px;
}

.btn{

    padding:10px 15px;

    border-radius:10px;

    text-decoration:none;

    color:white;

    font-size:14px;

    font-weight:600;

    border:none;
}

.edit{

    background:#f59e0b;
}

.delete{

    background:#ef4444;
}

/* MODAL */

.modal{

    position:fixed;

    top:0;
    left:0;

    width:100%;
    height:100%;

    background:rgba(0,0,0,0.75);

    display:flex;

    justify-content:center;

    align-items:center;

    z-index:1000;
}

.modal-content{

    width:700px;

    max-width:95%;

    background:#111827;

    padding:35px;

    border-radius:24px;
}

.modal-title{

    font-size:28px;

    margin-bottom:25px;

    font-weight:700;
}

.form-grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:18px;
}

.input-box input{

    width:100%;

    padding:14px;

    border:none;

    border-radius:12px;

    background:#1e293b;

    color:white;

    outline:none;
}

.save-btn{

    width:100%;

    padding:16px;

    border:none;

    border-radius:14px;

    background:linear-gradient(
    135deg,
    #2563eb,
    #4f46e5
    );

    color:white;

    margin-top:25px;

    cursor:pointer;

    font-size:16px;

    font-weight:600;
}

/* MOBILE */

@media(max-width:900px){

    .sidebar{
        position:relative;
        width:100%;
        min-height:auto;
    }

    .main{
        margin-left:0;
        width:100%;
        padding:20px;
    }

    .container{
        flex-direction:column;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
        gap:20px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .search-box{
        width:100%;
    }

    .search-box input{
        width:100%;
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

<a href="add_student.php">
➕ Add Student
</a>

<a href="manage_students.php" class="active">
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

<div class="topbar">

<div class="title">
Manage Students
</div>

<form class="search-box">

<input type="text"
name="search"
placeholder="Search students..."
value="<?php echo $search; ?>">

<button type="submit">
Search
</button>

</form>

</div>

<!-- TABLE -->

<div class="table-card">

<table>

<thead>

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
<th>Actions</th>

</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($students)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['student_id']; ?></td>

<td><?php echo $row['name']; ?></td>

<td><?php echo $row['email']; ?></td>

<td><?php echo $row['phone']; ?></td>

<td><?php echo $row['department']; ?></td>

<td><?php echo $row['course']; ?></td>

<td><?php echo $row['year']; ?></td>

<td>
<span class="fp">
<?php echo $row['fingerprint_id']; ?>
</span>
</td>

<td>

<a href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>">

<button style="
background:#2563eb;
color:white;
border:none;
padding:10px 18px;
border-radius:10px;
cursor:pointer;
">

Enroll Fingerprint

</button>

</a>

</a>
<a class="btn edit"
href="manage_students.php?edit=<?php echo $row['id']; ?>">
Edit
</a>

<a class="btn delete"
href="manage_students.php?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete Student?')">
Delete
</a>

</div>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<!-- EDIT MODAL -->

<?php if($editData){ ?>

<div class="modal">

<div class="modal-content">

<div class="modal-title">
Edit Student
</div>

<form method="POST">

<input type="hidden"
name="id"
value="<?php echo $editData['id']; ?>">

<div class="form-grid">

<div class="input-box">
<input type="text"
name="student_id"
value="<?php echo $editData['student_id']; ?>"
placeholder="Student ID">
</div>

<div class="input-box">
<input type="text"
name="name"
value="<?php echo $editData['name']; ?>"
placeholder="Name">
</div>

<div class="input-box">
<input type="email"
name="email"
value="<?php echo $editData['email']; ?>"
placeholder="Email">
</div>

<div class="input-box">
<input type="text"
name="phone"
value="<?php echo $editData['phone']; ?>"
placeholder="Phone">
</div>

<div class="input-box">
<input type="text"
name="department"
value="<?php echo $editData['department']; ?>"
placeholder="Department">
</div>

<div class="input-box">
<input type="text"
name="course"
value="<?php echo $editData['course']; ?>"
placeholder="Course">
</div>

<div class="input-box">
<input type="text"
name="year"
value="<?php echo $editData['year']; ?>"
placeholder="Year">
</div>

<div class="input-box">
<input type="text"
name="fingerprint_id"
value="<?php echo $editData['fingerprint_id']; ?>"
placeholder="Fingerprint ID">
</div>

</div>

<button type="submit"
name="update_student"
class="save-btn">

Update Student

</button>

</form>

</div>

</div>

<?php } ?>
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