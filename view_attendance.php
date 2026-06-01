<?php
include 'database.php';

$student      = null;
$attendance   = [];
$total_present = 0;
$total_absent  = 0;
$total_late    = 0;
$percentage    = 0;

if(isset($_GET['student_id'])){

    $student_id = $_GET['student_id'];

    $student_query = $conn->query("
        SELECT * FROM students WHERE student_id='$student_id'
    ");

    if($student_query->num_rows > 0){

        $student = $student_query->fetch_assoc();

        $attendance_query = $conn->query("
            SELECT * FROM attendance
            WHERE student_id='$student_id'
            ORDER BY date DESC
        ");

        while($row = $attendance_query->fetch_assoc()){
            $attendance[] = $row;
            if($row['status'] == 'Present')     $total_present++;
            elseif($row['status'] == 'Late')    $total_late++;
            else                                $total_absent++;
        }

        $total_classes = count($attendance);
        if($total_classes > 0){
            $percentage = round(($total_present / $total_classes) * 100);
        }
    }
}

// conic-gradient degrees
$deg = round($percentage * 3.6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Attendance — Smart Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:      #060b14;
    --surface: rgba(255,255,255,0.04);
    --surface2:rgba(255,255,255,0.07);
    --border:  rgba(255,255,255,0.08);
    --accent:  #3b6ef8;
    --accent2: #6ee7f7;
    --green:   #22c55e;
    --red:     #f43f5e;
    --amber:   #f59e0b;
    --text:    #f0f4ff;
    --muted:   #64748b;
}

*, *::before, *::after{ margin:0; padding:0; box-sizing:border-box; }

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
}

/* ── BLOBS ── */
.blob{
    position:fixed; border-radius:50%;
    filter:blur(100px); opacity:.12;
    pointer-events:none; z-index:0;
}
.blob-1{ width:500px;height:500px; background:var(--accent); top:-150px;left:-150px; }
.blob-2{ width:400px;height:400px; background:var(--accent2); bottom:-100px;right:-100px; }

/* ── HEADER ── */
header{
    position:sticky; top:0; z-index:100;
    backdrop-filter:blur(20px);
    background:rgba(6,11,20,.8);
    border-bottom:1px solid var(--border);
    padding:0 48px;
    height:64px;
    display:flex; align-items:center; justify-content:space-between;
}
.header-logo{
    display:flex; align-items:center; gap:10px;
    font-size:17px; font-weight:700;
    text-decoration:none; color:var(--text);
}
.header-logo .dot{
    width:9px; height:9px; border-radius:50%;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
}
.back-btn{
    display:inline-flex; align-items:center; gap:7px;
    padding:8px 18px; border-radius:10px;
    background:var(--surface);
    border:1px solid var(--border);
    color:var(--muted);
    font-family:'DM Sans',sans-serif;
    font-size:13px; font-weight:600;
    text-decoration:none; transition:.2s;
}
.back-btn:hover{ color:var(--text); background:var(--surface2); }

/* ── MAIN ── */
.main{
    position:relative; z-index:1;
    max-width:1100px;
    margin:0 auto;
    padding:40px 48px;
}

/* ── STUDENT HERO CARD ── */
.hero-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:22px;
    padding:32px;
    margin-bottom:24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:24px;
    flex-wrap:wrap;
    overflow:hidden;
    position:relative;
}
.hero-card::before{
    content:'';
    position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,var(--accent),var(--accent2),var(--green));
}

.student-info{ flex:1; min-width:200px; }
.student-name{
    font-size:32px; font-weight:700;
    letter-spacing:-.8px; margin-bottom:8px;
}
.student-meta{
    display:flex; flex-wrap:wrap; gap:10px;
    margin-top:6px;
}
.meta-pill{
    display:inline-flex; align-items:center; gap:6px;
    background:var(--surface2);
    border:1px solid var(--border);
    padding:5px 14px; border-radius:50px;
    font-size:12px; font-weight:600;
    color:var(--muted);
    font-family:'DM Mono',monospace;
}
.meta-pill span{ color:var(--text); }

/* ── DONUT ── */
.donut-wrap{
    display:flex; flex-direction:column;
    align-items:center; gap:8px;
    flex-shrink:0;
}
.donut{
    width:120px; height:120px;
    border-radius:50%;
    background:conic-gradient(
        var(--green) 0deg <?php echo $deg; ?>deg,
        rgba(255,255,255,.07) <?php echo $deg; ?>deg 360deg
    );
    display:flex; align-items:center; justify-content:center;
    position:relative;
}
.donut::after{
    content:'';
    position:absolute;
    width:86px; height:86px;
    background:var(--bg);
    border-radius:50%;
}
.donut-inner{
    position:relative; z-index:1;
    text-align:center;
    display:flex; flex-direction:column; align-items:center;
}
.donut-pct{
    font-size:24px; font-weight:700;
    letter-spacing:-1px; line-height:1;
}
.donut-label{
    font-size:10px; font-weight:600;
    color:var(--muted); text-transform:uppercase; letter-spacing:.6px;
    margin-top:2px;
}

/* status tint for donut */
<?php
if($percentage >= 75)      echo '.donut{ background:conic-gradient(var(--green) 0deg '.$deg.'deg, rgba(255,255,255,.07) '.$deg.'deg 360deg); }';
elseif($percentage >= 50)  echo '.donut{ background:conic-gradient(var(--amber) 0deg '.$deg.'deg, rgba(255,255,255,.07) '.$deg.'deg 360deg); }';
else                       echo '.donut{ background:conic-gradient(var(--red) 0deg '.$deg.'deg, rgba(255,255,255,.07) '.$deg.'deg 360deg); }';
?>

/* ── STAT CARDS ── */
.stats-row{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:24px;
}
.stat{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:18px;
    padding:22px 24px;
    position:relative; overflow:hidden;
    transition:.2s;
}
.stat:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.12); }
.stat::before{
    content:''; position:absolute;
    top:0; left:0; right:0; height:3px;
}
.stat.s-present::before{ background:linear-gradient(90deg,#16a34a,var(--green)); }
.stat.s-absent::before { background:linear-gradient(90deg,#be123c,var(--red)); }
.stat.s-total::before  { background:linear-gradient(90deg,var(--accent),var(--accent2)); }

.stat-val{
    font-size:36px; font-weight:700;
    letter-spacing:-1.5px; line-height:1;
    margin-bottom:6px;
}
.stat-label{
    font-size:11px; color:var(--muted);
    font-weight:600; text-transform:uppercase; letter-spacing:.8px;
}

/* ── ATTENDANCE STATUS BANNER ── */
.status-banner{
    padding:14px 20px;
    border-radius:14px;
    margin-bottom:24px;
    font-size:14px; font-weight:600;
    display:flex; align-items:center; gap:10px;
}
.banner-ok {
    background:rgba(34,197,94,.1);
    border:1px solid rgba(34,197,94,.22);
    color:#4ade80;
}
.banner-warn{
    background:rgba(245,158,11,.1);
    border:1px solid rgba(245,158,11,.2);
    color:#fbbf24;
}
.banner-bad {
    background:rgba(244,63,94,.1);
    border:1px solid rgba(244,63,94,.2);
    color:#fb7185;
}

/* ── TABLE CARD ── */
.table-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    overflow:hidden;
}
.table-head-row{
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 24px;
    border-bottom:1px solid var(--border);
    flex-wrap:wrap; gap:10px;
}
.table-head-row h3{
    font-size:14px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
    color:var(--muted);
}
.count-pill{
    background:var(--surface2);
    border:1px solid var(--border);
    padding:3px 12px; border-radius:50px;
    font-size:11px; font-weight:600; color:var(--muted);
    font-family:'DM Mono',monospace;
}

.table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}
table{
    width:100%; min-width:420px;
    border-collapse:collapse;
}
thead th{
    padding:11px 20px;
    font-size:11px; font-weight:700;
    color:var(--muted);
    text-transform:uppercase; letter-spacing:.8px;
    text-align:left; white-space:nowrap;
    background:var(--surface2);
    border-bottom:1px solid var(--border);
}
tbody td{
    padding:14px 20px;
    font-size:14px;
    border-bottom:1px solid rgba(255,255,255,.03);
    vertical-align:middle;
}
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }

.row-num{
    font-family:'DM Mono',monospace;
    font-size:12px; color:var(--muted);
}
.date-cell{
    font-weight:600;
}
.day-sub{
    display:block;
    font-size:11px; color:var(--muted);
    margin-top:1px;
}
.time-cell{
    font-family:'DM Mono',monospace;
    font-size:12px; color:var(--muted);
}

.badge{
    display:inline-flex; align-items:center;
    padding:5px 13px; border-radius:50px;
    font-size:11px; font-weight:700; letter-spacing:.3px;
    text-transform:uppercase;
}
.b-present{ background:rgba(34,197,94,.12);  color:#4ade80; border:1px solid rgba(34,197,94,.22); }
.b-absent{  background:rgba(244,63,94,.10);  color:#fb7185; border:1px solid rgba(244,63,94,.18); }
.b-late{    background:rgba(245,158,11,.10); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }

/* ── EMPTY / NOT FOUND ── */
.empty-state{
    text-align:center;
    padding:80px 24px;
}
.empty-icon{
    font-size:52px; margin-bottom:16px; opacity:.4;
}
.empty-state h2{
    font-size:22px; font-weight:700;
    margin-bottom:8px; letter-spacing:-.3px;
}
.empty-state p{
    color:var(--muted); font-size:14px; line-height:1.7;
    max-width:340px; margin:0 auto 24px;
}
.try-again-btn{
    display:inline-flex; align-items:center; gap:8px;
    padding:11px 24px; border-radius:12px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700;
    text-decoration:none; transition:.2s;
    box-shadow:0 4px 16px rgba(59,110,248,.3);
}
.try-again-btn:hover{ transform:translateY(-2px); }

/* ── MOBILE ── */
@media(max-width:768px){
    header{ padding:0 18px; }
    .main{ padding:20px 16px; }

    .student-name{ font-size:24px; }
    .stats-row{ grid-template-columns:1fr 1fr; gap:12px; }
    .stat{ padding:16px 18px; }
    .stat-val{ font-size:28px; }

    thead th, tbody td{ padding:11px 14px; font-size:13px; }
}

@media(max-width:480px){
    .stats-row{ grid-template-columns:1fr 1fr; }
    .stat-val{ font-size:24px; }
    .donut{ width:100px; height:100px; }
    .donut::after{ width:72px; height:72px; }
    .donut-pct{ font-size:20px; }
}
</style>
</head>
<body>

<!-- BLOBS -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<!-- HEADER -->
<header>
    <a href="index.php" class="header-logo">
        <div class="dot"></div>
        Smart Attendance
    </a>
    <a href="index.php" class="back-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back
    </a>
</header>

<!-- MAIN -->
<div class="main">

<?php if($student): ?>

    <!-- HERO CARD -->
    <div class="hero-card">
        <div class="student-info">
            <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
            <div class="student-meta">
                <div class="meta-pill">ID: <span><?php echo htmlspecialchars($student['student_id']); ?></span></div>
                <?php if(!empty($student['department'])): ?>
                <div class="meta-pill"><?php echo htmlspecialchars($student['department']); ?></div>
                <?php endif; ?>
                <?php if(!empty($student['course'])): ?>
                <div class="meta-pill"><?php echo htmlspecialchars($student['course']); ?></div>
                <?php endif; ?>
                <?php if(!empty($student['year'])): ?>
                <div class="meta-pill"><?php echo htmlspecialchars($student['year']); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="donut-wrap">
            <div class="donut">
                <div class="donut-inner">
                    <div class="donut-pct"><?php echo $percentage; ?>%</div>
                    <div class="donut-label">Rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS BANNER -->
    <?php
    if($percentage >= 75)      { $bClass = 'banner-ok';   $bMsg = "Good standing — attendance is above 75%."; }
    elseif($percentage >= 50)  { $bClass = 'banner-warn'; $bMsg = "Warning — attendance is below 75%. Improvement needed."; }
    else                       { $bClass = 'banner-bad';  $bMsg = "Critical — attendance is below 50%. Immediate attention required."; }
    ?>
    <div class="status-banner <?php echo $bClass; ?>">
        <?php echo $bMsg; ?>
    </div>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat s-present">
            <div class="stat-val"><?php echo $total_present; ?></div>
            <div class="stat-label">Present Days</div>
        </div>
        <div class="stat s-absent">
            <div class="stat-val"><?php echo $total_absent; ?></div>
            <div class="stat-label">Absent Days</div>
        </div>
        <div class="stat s-total">
            <div class="stat-val"><?php echo $total_classes; ?></div>
            <div class="stat-label">Total Classes</div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
        <div class="table-head-row">
            <h3>Attendance History</h3>
            <span class="count-pill"><?php echo $total_classes; ?> records</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 1;
                foreach($attendance as $row):
                    $s = $row['status'];
                    $bClass = $s=='Present' ? 'b-present' : ($s=='Late' ? 'b-late' : 'b-absent');
                    $dayName = date("D", strtotime($row['date']));
                ?>
                <tr>
                    <td class="row-num"><?php echo str_pad($count++, 2, '0', STR_PAD_LEFT); ?></td>
                    <td class="date-cell">
                        <?php echo date("d M Y", strtotime($row['date'])); ?>
                        <span class="day-sub"><?php echo $dayName; ?></span>
                    </td>
                    <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                    <td class="time-cell"><?php echo $row['time']; ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if(empty($attendance)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;padding:40px;color:var(--muted);">
                        No attendance records found.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>

    <!-- NOT FOUND -->
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h2>Student Not Found</h2>
        <p>No records found for that Student ID. Please check the ID and try again.</p>
        <a href="index.php" class="try-again-btn">Try Again</a>
    </div>

<?php endif; ?>

</div>

</body>
</html>