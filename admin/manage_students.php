<?php

session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role']; // superadmin check

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

/* ================= GLOBAL ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#0f172a;
    color:white;
    overflow-x:hidden;
}

/* ================= MOBILE TOPBAR ================= */
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

/* ================= OVERLAY ================= */
.overlay{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
    z-index:5500;
}

.overlay.active{
    display:block;
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
    z-index:6000;
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
    transition:0.2s;
}

.sidebar a:hover,
.sidebar a.active{
    background:#2563eb;
}

/* ================= MAIN ================= */
.main{
    margin-left:260px;
    padding:40px;
}

/* ================= TOPBAR ================= */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    flex-wrap:wrap;
    gap:15px;
}

.title{
    font-size:34px;
    font-weight:700;
}

/* ================= SEARCH ================= */
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
    background:linear-gradient(135deg,#2563eb,#4f46e5);
    color:white;
    cursor:pointer;
    font-weight:600;
}

/* ================= TABLE CARD ================= */
.table-card{
    background:linear-gradient(135deg,rgba(30,41,59,0.95),rgba(15,23,42,0.95));
    border:1px solid rgba(255,255,255,0.06);
    backdrop-filter:blur(18px);
    border-radius:24px;
    padding:25px;
    overflow-x:auto;
    box-shadow:0 10px 40px rgba(0,0,0,0.35);
}

/* ================= TABLE ================= */
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
    background:linear-gradient(135deg,rgba(37,99,235,0.12),rgba(15,23,42,0.45));
    transition:0.3s;
    border:1px solid rgba(255,255,255,0.05);
}

tbody tr:hover{
    transform:translateY(-2px);
    background:linear-gradient(135deg,rgba(59,130,246,0.22),rgba(30,41,59,0.7));
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

/* ================= BADGES ================= */
.fp{
    background:#10b981;
    padding:8px 14px;
    border-radius:30px;
    font-size:12px;
}

/* ================= ACTION BUTTONS ================= */
.action{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}

.btn{
    padding:10px 15px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-size:14px;
    font-weight:600;
    border:none;
    cursor:pointer;
    display:inline-block;
}

.btn-enroll{
    background:#2563eb;
}

.edit{
    background:#f59e0b;
}

.delete{
    background:#ef4444;
}

/* ================= MODAL ================= */
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
    z-index:9000;
}

.modal-content{
    width:700px;
    max-width:95%;
    background:#111827;
    padding:35px;
    border-radius:24px;
    max-height:90vh;
    overflow-y:auto;
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

.input-box label{
    display:block;
    margin-bottom:8px;
    color:#cbd5e1;
    font-size:14px;
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
    background:linear-gradient(135deg,#2563eb,#4f46e5);
    color:white;
    margin-top:25px;
    cursor:pointer;
    font-size:16px;
    font-weight:600;
}

/* ================= MOBILE ================= */
@media(max-width:700px){

    .topbar-mobile{
        display:flex;
    }

    .sidebar{
        left:-280px;
    }

    .sidebar.active{
        left:0;
    }

    .main{
        margin-left:0;
        padding:15px;
    }

    .title{
        font-size:24px;
    }

    .search-box{
        width:100%;
    }

    .search-box input{
        width:100%;
        flex:1;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .modal-content{
        padding:20px;
    }
}

</style>
</head>

<body>

<!-- MOBILE TOPBAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Smart Attendance</div>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

    <div class="logo">📘 Smart<br>Attendance</div>

    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="add_student.php">➕ Add Student</a>
    <a href="manage_students.php" class="active">👨‍🎓 Manage Students</a>
    <a href="attendance.php">🗓️ Attendance</a>

    <!-- SUPERADMIN ONLY -->
    <?php if($admin_role == "superadmin"){ ?>
    <a href="admin_management.php">👮 Admin Management</a>
    <?php } ?>

    <a href="javascript:void(0);" onclick="confirmLogout()">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">

        <div class="title">Manage Students</div>

        <form class="search-box">
            <input type="text"
                   name="search"
                   placeholder="Search students..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
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
                        <span class="fp"><?php echo $row['fingerprint_id']; ?></span>
                    </td>
                    <td>
                        <div class="action">

                            <a href="save_enroll.php?student_id=<?php echo $row['student_id']; ?>"
                               class="btn btn-enroll">
                                Enroll Fingerprint
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

<!-- EDIT MODAL -->
<?php if($editData){ ?>

<div class="modal">

    <div class="modal-content">

        <div class="modal-title">Edit Student</div>

        <form method="POST">

            <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

            <div class="form-grid">

                <div class="input-box">
                    <label>Student ID</label>
                    <input type="text" name="student_id"
                           value="<?php echo $editData['student_id']; ?>"
                           placeholder="Student ID">
                </div>

                <div class="input-box">
                    <label>Name</label>
                    <input type="text" name="name"
                           value="<?php echo $editData['name']; ?>"
                           placeholder="Name">
                </div>

                <div class="input-box">
                    <label>Email</label>
                    <input type="email" name="email"
                           value="<?php echo $editData['email']; ?>"
                           placeholder="Email">
                </div>

                <div class="input-box">
                    <label>Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo $editData['phone']; ?>"
                           placeholder="Phone">
                </div>

                <div class="input-box">
                    <label>Department</label>
                    <input type="text" name="department"
                           value="<?php echo $editData['department']; ?>"
                           placeholder="Department">
                </div>

                <div class="input-box">
                    <label>Course</label>
                    <input type="text" name="course"
                           value="<?php echo $editData['course']; ?>"
                           placeholder="Course">
                </div>

                <div class="input-box">
                    <label>Year</label>
                    <input type="text" name="year"
                           value="<?php echo $editData['year']; ?>"
                           placeholder="Year">
                </div>

                <div class="input-box">
                    <label>Fingerprint ID</label>
                    <input type="text" name="fingerprint_id"
                           value="<?php echo $editData['fingerprint_id']; ?>"
                           placeholder="Fingerprint ID">
                </div>

            </div>

            <button type="submit" name="update_student" class="save-btn">
                Update Student
            </button>

        </form>

    </div>

</div>

<?php } ?>

<script>

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

function confirmLogout(){
    if(confirm("Are you sure you want to logout?")){
        window.location = "logout.php";
    }
}

</script>

</body>
</html>