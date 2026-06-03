<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];
date_default_timezone_set("Asia/Kolkata");
$today = date("Y-m-d");

/* COUNTS PER GROUP */
function getGroupStats($conn, $memberTable, $attendanceTable, $today){
    $total   = $conn->query("SELECT COUNT(*) as c FROM $memberTable")->fetch_assoc()['c'];
    $present = $conn->query("SELECT COUNT(*) as c FROM $attendanceTable WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
    $absent  = $conn->query("SELECT COUNT(*) as c FROM $attendanceTable WHERE date='$today' AND status='Absent'")->fetch_assoc()['c'];
    $late    = $conn->query("SELECT COUNT(*) as c FROM $attendanceTable WHERE date='$today' AND status='Late'")->fetch_assoc()['c'];
    $rate    = $total > 0 ? round(($present / $total) * 100) : 0;
    return compact('total','present','absent','late','rate');
}

$students  = getGroupStats($conn, 'students',  'attendance',            $today);
$teachers  = getGroupStats($conn, 'teachers',  'teachers_attendance',  $today);
$employees = getGroupStats($conn, 'employees', 'employees_attendance', $today);

/* WEEK DATA (students only for chart) */
$week_labels  = [];
$week_present = [];
$week_absent  = [];
for($i = 6; $i >= 0; $i--){
    $d = date("Y-m-d", strtotime("-$i days"));
    $week_labels[]  = date("D", strtotime("-$i days"));
    $week_present[] = (int)$conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$d' AND status='Present'")->fetch_assoc()['c'];
    $week_absent[]  = (int)$conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$d' AND status='Absent'")->fetch_assoc()['c'];
}

/* RECENT ATTENDANCE */
$recent = $conn->query("
    SELECT a.*, s.name, s.department, 'Students' as grp
    FROM attendance a
    LEFT JOIN students s ON a.student_id = s.student_id
    WHERE a.date='$today'
    UNION ALL
    SELECT a.*, s.name, s.department, 'Teachers' as grp
    FROM teachers_attendance a
    LEFT JOIN teachers s ON a.student_id = s.student_id
    WHERE a.date='$today'
    UNION ALL
    SELECT a.*, s.name, s.department, 'Employees' as grp
    FROM employees_attendance a
    LEFT JOIN employees s ON a.student_id = s.student_id
    WHERE a.date='$today'
    ORDER BY id DESC
    LIMIT 10
");

/* ALL GROUPS for sidebar menu */
$groups = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Dashboard — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{
    --bg:#080d18; --surface:#0f1929; --surface2:#162035;
    --border:rgba(255,255,255,0.07); --accent:#3b6ef8; --accent2:#6ee7f7;
    --green:#22c55e; --red:#f43f5e; --amber:#f59e0b; --purple:#a78bfa;
    --text:#e2e8f0; --muted:#64748b; --sidebar-w:240px;
}
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; overflow-x:hidden; }

/* MOBILE BAR */
.mob-bar{ display:none; align-items:center; justify-content:space-between; padding:14px 18px; background:var(--surface); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:800; }
.mob-bar .brand{ font-size:15px; font-weight:700; }
.hamburger{ background:none; border:none; color:var(--text); font-size:22px; cursor:pointer; padding:4px 6px; border-radius:8px; }
.hamburger:hover{ background:var(--surface2); }

/* OVERLAY */
.overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:900; }
.overlay.on{ display:block; }

/* SIDEBAR */
.sidebar{ width:var(--sidebar-w); height:100vh; position:fixed; top:0; left:0; background:var(--surface); border-right:1px solid var(--border); padding:24px 16px; z-index:1000; transition:.25s ease; display:flex; flex-direction:column; overflow-y:auto; }
.sidebar .logo{ font-size:20px; font-weight:700; line-height:1.4; padding:0 6px; margin-bottom:32px; letter-spacing:-.3px; }
.nav-section{ font-size:10px; font-weight:600; color:var(--muted); letter-spacing:1.2px; text-transform:uppercase; padding:0 8px; margin:20px 0 8px; }
.sidebar a{ display:flex; align-items:center; gap:10px; color:var(--text); text-decoration:none; padding:11px 12px; border-radius:10px; margin-bottom:3px; font-size:14px; font-weight:500; transition:.15s; }
.sidebar a:hover{ background:var(--surface2); }
.sidebar a.active{ background:var(--accent); color:#fff; font-weight:600; }
.sidebar .spacer{ flex:1; }
.sidebar .logout{ color:#f87171; }
.sidebar .logout:hover{ background:rgba(244,63,94,.12); }

/* MAIN */
.main{ margin-left:var(--sidebar-w); padding:36px 40px; min-height:100vh; }

/* TOP BAR */
.top-bar{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; margin-bottom:28px; }
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; }
.date-pill{ background:var(--surface2); border:1px solid var(--border); padding:9px 18px; border-radius:50px; font-size:13px; font-weight:500; color:var(--accent2); white-space:nowrap; }

/* ESP32 WIDGET */
.esp-bar{
    display:flex; align-items:center; gap:12px;
    background:var(--surface); border:1px solid var(--border);
    border-radius:14px; padding:12px 18px;
    margin-bottom:24px; transition:.3s;
}
.esp-bar.online{ border-color:rgba(34,197,94,.3); background:rgba(34,197,94,.06); }
.esp-bar.offline{ border-color:rgba(244,63,94,.25); background:rgba(244,63,94,.05); }
.esp-led{ width:10px; height:10px; border-radius:50%; flex-shrink:0; background:var(--muted); transition:.3s; }
.esp-bar.online  .esp-led{ background:#4ade80; box-shadow:0 0 0 3px rgba(74,222,128,.2); animation:ledPulse 1.5s infinite; }
.esp-bar.offline .esp-led{ background:#f43f5e; box-shadow:none; animation:none; }
@keyframes ledPulse{ 0%,100%{ box-shadow:0 0 0 3px rgba(74,222,128,.2); } 50%{ box-shadow:0 0 0 7px rgba(74,222,128,0); } }
.esp-info{ flex:1; }
.esp-device-name{ font-size:13px; font-weight:700; }
.esp-status-text{ font-size:12px; color:var(--muted); margin-top:1px; transition:.3s; }
.esp-bar.online  .esp-status-text{ color:#4ade80; }
.esp-bar.offline .esp-status-text{ color:#f87171; }
.esp-time{ font-family:'DM Mono',monospace; font-size:11px; color:var(--muted); white-space:nowrap; }

/* GROUP TABS */
.group-tabs{ display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.gtab{ padding:8px 18px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:13px; font-weight:600; cursor:pointer; transition:.15s; text-decoration:none; }
.gtab:hover{ color:var(--text); background:var(--surface2); }
.gtab.active{ background:var(--accent); border-color:var(--accent); color:#fff; }

/* STATS */
.stats-row{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.stat{ background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:22px 24px; position:relative; overflow:hidden; transition:.2s; }
.stat:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.13); }
.stat::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.stat.s-present::before{ background:linear-gradient(90deg,#16a34a,var(--green)); }
.stat.s-absent::before { background:linear-gradient(90deg,#be123c,var(--red)); }
.stat.s-total::before  { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-rate::before   { background:linear-gradient(90deg,var(--purple),#c4b5fd); }
.stat-val{ font-size:38px; font-weight:700; letter-spacing:-1.5px; line-height:1; margin-bottom:6px; }
.stat-label{ font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.8px; }

/* CHARTS */
.charts-row{ display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:24px; }
.card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:26px; }
.card-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
.card-head h2{ font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
.chart-wrap canvas{ max-height:240px; }
.donut-wrap{ display:flex; align-items:center; justify-content:center; }
.donut-wrap canvas{ max-height:200px; }
.chart-legend{ display:flex; gap:16px; margin-top:16px; }
.legend-item{ display:flex; align-items:center; gap:7px; font-size:12px; color:var(--muted); font-weight:500; }
.legend-dot{ width:8px; height:8px; border-radius:50%; }
.donut-stats{ display:flex; flex-direction:column; gap:10px; margin-top:18px; }
.donut-row{ display:flex; justify-content:space-between; align-items:center; font-size:13px; }
.donut-label{ color:var(--muted); font-weight:500; }
.donut-val{ font-weight:700; font-family:'DM Mono',monospace; }

/* TABLE */
.table-card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:26px; }
.table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; margin-top:4px; }
table{ width:100%; min-width:520px; border-collapse:collapse; }
thead th{ padding:11px 14px; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; text-align:left; white-space:nowrap; border-bottom:1px solid var(--border); }
tbody td{ padding:14px; font-size:14px; border-bottom:1px solid rgba(255,255,255,.03); vertical-align:middle; }
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }
.student-cell{ display:flex; flex-direction:column; gap:2px; }
.student-name{ font-weight:600; }
.student-meta{ font-size:11px; color:var(--muted); }
.id-cell{ font-family:'DM Mono',monospace; font-size:12px; color:var(--muted); }
.time-cell{ font-family:'DM Mono',monospace; font-size:12px; color:var(--muted); }
.grp-pill{ display:inline-flex; align-items:center; padding:3px 10px; border-radius:50px; font-size:11px; font-weight:700; }
.grp-students{ background:rgba(59,110,248,.12); color:#93c5fd; border:1px solid rgba(59,110,248,.2); }
.grp-teachers{ background:rgba(245,158,11,.1); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }
.grp-employees{ background:rgba(167,139,250,.12); color:#c4b5fd; border:1px solid rgba(167,139,250,.2); }
.badge{ display:inline-flex; align-items:center; padding:5px 12px; border-radius:50px; font-size:11px; font-weight:700; letter-spacing:.3px; text-transform:uppercase; }
.b-present{ background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.22); }
.b-absent{ background:rgba(244,63,94,.10); color:#fb7185; border:1px solid rgba(244,63,94,.18); }
.b-late{ background:rgba(245,158,11,.10); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }
.empty{ text-align:center; padding:50px 20px; }
.empty p{ color:var(--muted); font-size:14px; }

/* MOBILE */
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }
    .page-title{ font-size:22px; }
    .stats-row{ grid-template-columns:1fr 1fr; gap:12px; }
    .stat{ padding:16px 18px; }
    .stat-val{ font-size:28px; }
    .charts-row{ grid-template-columns:1fr; }
    thead th, tbody td{ padding:10px; font-size:13px; }
    .student-meta{ display:none; }
}
@media(max-width:420px){
    .stats-row{ grid-template-columns:1fr 1fr; }
    .stat-val{ font-size:24px; }
}
</style>
</head>
<body>

<div class="mob-bar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="brand">📘 Smart Attendance</div>
    <div></div>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="logo">📘 Smart<br>Attendance</div>
    <div class="nav-section">Menu</div>
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>
    <a href="add_member.php">➕ Add Member</a>
    <a href="manage_memberss.php">👥 Manage Members</a>
    <a href="attendance.php">🗓️ Attendance</a>
    <?php if($admin_role=='superadmin'){ ?>
    <a href="admin_management.php">👮 Admin Management</a>
    <?php } ?>
    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<div class="main">

    <div class="top-bar">
        <div class="page-title">Dashboard</div>
        <div class="date-pill">📅 <?php echo date("l, d M Y"); ?></div>
    </div>

    <!-- ESP32 STATUS -->
    <div class="esp-bar" id="espBar">
        <div class="esp-led" id="espLed"></div>
        <div class="esp-info">
            <div class="esp-device-name" id="espName">ESP32 Device</div>
            <div class="esp-status-text" id="espStatus">Checking...</div>
        </div>
        <div class="esp-time" id="espTime">—</div>
    </div>

    <!-- GROUP TABS -->
    <div class="group-tabs">
        <a href="#" class="gtab active" onclick="switchGroup('students',this)">Students</a>
        <a href="#" class="gtab" onclick="switchGroup('teachers',this)">Teachers</a>
        <a href="#" class="gtab" onclick="switchGroup('employees',this)">Employees</a>
        <?php
        $groups->data_seek(0);
        while($g = $groups->fetch_assoc()){
            if(!in_array($g['group_name'],['Students','Teachers','Employees'])){
                echo '<a href="#" class="gtab" onclick="switchGroup(\''.$g['table_name'].'\',this)">'.htmlspecialchars($g['group_name']).'</a>';
            }
        }
        ?>
    </div>

    <!-- STAT CARDS -->
    <?php
    $allStats = [
        'students'  => $students,
        'teachers'  => $teachers,
        'employees' => $employees,
    ];
    foreach($allStats as $key => $s){
        $hidden = $key === 'students' ? '' : 'style="display:none"';
        echo '<div class="stats-row group-stats" data-group="'.$key.'" '.$hidden.'>
            <div class="stat s-present"><div class="stat-val">'.$s['present'].'</div><div class="stat-label">Present Today</div></div>
            <div class="stat s-absent"><div class="stat-val">'.$s['absent'].'</div><div class="stat-label">Absent Today</div></div>
            <div class="stat s-total"><div class="stat-val">'.$s['total'].'</div><div class="stat-label">Total</div></div>
            <div class="stat s-rate"><div class="stat-val">'.$s['rate'].'%</div><div class="stat-label">Rate</div></div>
        </div>';
    }
    ?>

    <!-- CHARTS -->
    <div class="charts-row">
        <div class="card">
            <div class="card-head"><h2>Weekly Overview — Students</h2></div>
            <div class="chart-wrap"><canvas id="barChart"></canvas></div>
            <div class="chart-legend">
                <div class="legend-item"><div class="legend-dot" style="background:#4ade80;"></div>Present</div>
                <div class="legend-item"><div class="legend-dot" style="background:#fb7185;"></div>Absent</div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2>Today's Split</h2></div>
            <div class="donut-wrap"><canvas id="pieChart"></canvas></div>
            <div class="donut-stats">
                <div class="donut-row"><span class="donut-label">Present</span><span class="donut-val" style="color:#4ade80;"><?php echo $students['present']; ?></span></div>
                <div class="donut-row"><span class="donut-label">Absent</span><span class="donut-val" style="color:#fb7185;"><?php echo $students['absent']; ?></span></div>
                <div class="donut-row"><span class="donut-label">Total</span><span class="donut-val"><?php echo $students['total']; ?></span></div>
            </div>
        </div>
    </div>

    <!-- RECENT TABLE -->
    <div class="table-card">
        <div class="card-head">
            <h2>Today's Attendance — All Groups</h2>
            <a href="attendance.php" style="font-size:13px;color:var(--accent);text-decoration:none;font-weight:600;">View all</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Group</th><th>Status</th><th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $hasRows = false;
                while($row = $recent->fetch_assoc()):
                    $hasRows = true;
                    $s = $row['status'];
                    $bClass = $s=='Present' ? 'b-present' : ($s=='Late' ? 'b-late' : 'b-absent');
                    $gClass = strtolower($row['grp']);
                ?>
                <tr>
                    <td class="id-cell"><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td>
                        <div class="student-cell">
                            <span class="student-name"><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="student-meta"><?php echo htmlspecialchars($row['department'] ?? ''); ?></span>
                        </div>
                    </td>
                    <td><span class="grp-pill grp-<?php echo $gClass; ?>"><?php echo $row['grp']; ?></span></td>
                    <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                    <td class="time-cell"><?php echo $row['time']; ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(!$hasRows): ?>
                <tr><td colspan="5"><div class="empty"><p>No attendance recorded today yet.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
Chart.defaults.color='#64748b';
Chart.defaults.font.family="'DM Sans',sans-serif";

new Chart(document.getElementById("barChart"),{
    type:"bar",
    data:{
        labels:<?php echo json_encode($week_labels); ?>,
        datasets:[
            { label:"Present", data:<?php echo json_encode($week_present); ?>, backgroundColor:"rgba(34,197,94,.25)", borderColor:"#22c55e", borderWidth:2, borderRadius:8, borderSkipped:false },
            { label:"Absent",  data:<?php echo json_encode($week_absent); ?>,  backgroundColor:"rgba(244,63,94,.18)",  borderColor:"#f43f5e", borderWidth:2, borderRadius:8, borderSkipped:false }
        ]
    },
    options:{ responsive:true, plugins:{ legend:{ display:false }, tooltip:{ backgroundColor:'#0f1929', borderColor:'rgba(255,255,255,.08)', borderWidth:1, padding:12 } }, scales:{ x:{ grid:{ color:'rgba(255,255,255,.04)' }, border:{ display:false } }, y:{ grid:{ color:'rgba(255,255,255,.04)' }, border:{ display:false }, ticks:{ precision:0 } } } }
});

new Chart(document.getElementById("pieChart"),{
    type:"doughnut",
    data:{
        labels:["Present","Absent"],
        datasets:[{ data:[<?php echo $students['present']; ?>,<?php echo $students['absent']; ?>], backgroundColor:["rgba(34,197,94,.8)","rgba(244,63,94,.7)"], borderColor:["#22c55e","#f43f5e"], borderWidth:2, hoverOffset:6 }]
    },
    options:{ responsive:true, cutout:"72%", plugins:{ legend:{ display:false }, tooltip:{ backgroundColor:'#0f1929', borderColor:'rgba(255,255,255,.08)', borderWidth:1, padding:12 } } }
});

function switchGroup(group, el){
    document.querySelectorAll('.gtab').forEach(function(t){ t.classList.remove('active'); });
    el.classList.add('active');
    document.querySelectorAll('.group-stats').forEach(function(s){ s.style.display = 'none'; });
    var target = document.querySelector('.group-stats[data-group="'+group+'"]');
    if(target) target.style.display = 'grid';
    return false;
}

// ESP32 poll every second
(function(){
    var bar    = document.getElementById('espBar');
    var status = document.getElementById('espStatus');
    var timeEl = document.getElementById('espTime');
    var name   = document.getElementById('espName');
    function poll(){
        fetch('../esp_status.php?action=status')
            .then(function(r){ return r.json(); })
            .then(function(d){
                name.textContent   = d.device_name || 'ESP32 Device';
                if(d.online){
                    bar.className      = 'esp-bar online';
                    status.textContent = 'Online — Active';
                    timeEl.textContent = 'Last seen: ' + d.time_ago;
                } else {
                    bar.className      = 'esp-bar offline';
                    status.textContent = d.last_ping ? 'Offline' : 'Never Connected';
                    timeEl.textContent = d.last_ping ? 'Last: ' + d.time_ago : '—';
                }
            })
            .catch(function(){
                bar.className      = 'esp-bar offline';
                status.textContent = 'Cannot reach server';
                timeEl.textContent = '—';
            });
    }
    poll();
    setInterval(poll, 1000);
})();

function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('on'); document.getElementById('overlay').classList.toggle('on'); }
function confirmLogout(){ if(confirm("Are you sure you want to logout?")){ window.location="logout.php"; } }
</script>
</body>
</html>