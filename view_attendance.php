<?php
include 'database.php';
date_default_timezone_set("Asia/Kolkata");

$student      = null;
$attendance   = [];
$total_present = 0;
$total_absent  = 0;
$total_late    = 0;
$percentage    = 0;
$avg_hours     = "—";
$total_hours_sec = 0;
$hours_count   = 0;
$found_table   = null;
$found_att     = null;
$group_name    = null;

if(isset($_GET['student_id'])){
    $student_id = $_GET['student_id'];

    // Search across all groups
    $groups = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
    while($group = $groups->fetch_assoc()){
        $mt = $group['table_name'];
        $at = $group['attendance_table'];

        $sq = $conn->query("SELECT * FROM `$mt` WHERE student_id='$student_id'");
        if($sq && $sq->num_rows > 0){
            $student     = $sq->fetch_assoc();
            $found_table = $mt;
            $found_att   = $at;
            $group_name  = $group['group_name'];
            break;
        }
    }

    if($student){
        $aq = $conn->query("SELECT * FROM `$found_att` WHERE student_id='$student_id' ORDER BY date DESC");
        while($row = $aq->fetch_assoc()){
            $attendance[] = $row;
            if($row['status'] == 'Present')     $total_present++;
            elseif($row['status'] == 'Late')    $total_late++;
            else                                $total_absent++;

            // accumulate working hours
            if(!empty($row['working_hours']) && $row['working_hours'] !== '00:00'){
                $parts = explode(':', $row['working_hours']);
                $total_hours_sec += ((int)$parts[0] * 3600) + ((int)$parts[1] * 60);
                $hours_count++;
            }
        }

        $total_classes = count($attendance);
        if($total_classes > 0)
            $percentage = round(($total_present / $total_classes) * 100);

        if($hours_count > 0){
            $avg_sec   = $total_hours_sec / $hours_count;
            $avg_h     = floor($avg_sec / 3600);
            $avg_m     = floor(($avg_sec % 3600) / 60);
            $avg_hours = sprintf("%02d:%02d", $avg_h, $avg_m);
        }
    }
}

$deg = round($percentage * 3.6);

/* CALENDAR */
$cal_month = isset($_GET['cal_month']) ? (int)$_GET['cal_month'] : (int)date("m");
$cal_year  = isset($_GET['cal_year'])  ? (int)$_GET['cal_year']  : (int)date("Y");
$cal_month = max(1, min(12, $cal_month));

$first_day     = mktime(0,0,0,$cal_month,1,$cal_year);
$days_in_month = date("t", $first_day);
$start_dow     = (int)date("w", $first_day);

// Index attendance by date for calendar
$att_by_date = [];
foreach($attendance as $row){
    $att_by_date[$row['date']] = $row;
}

$month_names = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Attendance — Smart Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#060b14; --surface:rgba(255,255,255,.04); --surface2:rgba(255,255,255,.07);
    --border:rgba(255,255,255,.08); --accent:#3b6ef8; --accent2:#6ee7f7;
    --green:#22c55e; --red:#f43f5e; --amber:#f59e0b; --purple:#a78bfa;
    --text:#f0f4ff; --muted:#64748b;
}
*,*::before,*::after{ margin:0; padding:0; box-sizing:border-box; }
html{ scroll-behavior:smooth; }
body{ font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

/* BLOBS */
.blob{ position:fixed; border-radius:50%; filter:blur(100px); opacity:.12; pointer-events:none; z-index:0; }
.blob-1{ width:500px;height:500px; background:var(--accent); top:-150px;left:-150px; }
.blob-2{ width:400px;height:400px; background:var(--accent2); bottom:-100px;right:-100px; }

/* HEADER */
header{
    position:sticky; top:0; z-index:100;
    backdrop-filter:blur(20px);
    background:rgba(6,11,20,.8);
    border-bottom:1px solid var(--border);
    padding:0 48px; height:64px;
    display:flex; align-items:center; justify-content:space-between;
}
.header-logo{ display:flex; align-items:center; gap:10px; font-size:17px; font-weight:700; text-decoration:none; color:var(--text); }
.header-logo .dot{ width:9px; height:9px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent2)); animation:pulse 2s infinite; }
@keyframes pulse{ 0%,100%{ box-shadow:0 0 0 0 rgba(59,110,248,.5); } 50%{ box-shadow:0 0 0 8px rgba(59,110,248,0); } }
.back-btn{ display:inline-flex; align-items:center; gap:7px; padding:8px 18px; border-radius:10px; background:var(--surface); border:1px solid var(--border); color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; transition:.2s; }
.back-btn:hover{ color:var(--text); background:var(--surface2); }

/* MAIN */
.main{ position:relative; z-index:1; max-width:1060px; margin:0 auto; padding:40px 48px; }

/* HERO CARD */
.hero-card{ background:var(--surface); border:1px solid var(--border); border-radius:22px; padding:30px; margin-bottom:22px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap; position:relative; overflow:hidden; }
.hero-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--accent),var(--accent2),var(--green)); }
.student-info{ flex:1; min-width:200px; }
.student-name{ font-size:30px; font-weight:700; letter-spacing:-.6px; margin-bottom:10px; }
.meta-row{ display:flex; flex-wrap:wrap; gap:8px; }
.meta-pill{ display:inline-flex; align-items:center; gap:6px; background:var(--surface2); border:1px solid var(--border); padding:5px 14px; border-radius:50px; font-size:12px; font-weight:600; color:var(--muted); font-family:'DM Mono',monospace; }
.meta-pill span{ color:var(--text); }

/* DONUT */
.donut-wrap{ display:flex; flex-direction:column; align-items:center; gap:6px; flex-shrink:0; }
.donut{ width:110px; height:110px; border-radius:50%; display:flex; align-items:center; justify-content:center; position:relative; }
<?php
if($percentage >= 75)     echo '.donut{ background:conic-gradient(var(--green) 0deg '.$deg.'deg, rgba(255,255,255,.07) '.$deg.'deg 360deg); }';
elseif($percentage >= 50) echo '.donut{ background:conic-gradient(var(--amber) 0deg '.$deg.'deg, rgba(255,255,255,.07) '.$deg.'deg 360deg); }';
else                      echo '.donut{ background:conic-gradient(var(--red) 0deg '.$deg.'deg, rgba(255,255,255,.07) '.$deg.'deg 360deg); }';
?>
.donut::after{ content:''; position:absolute; width:78px; height:78px; background:var(--bg); border-radius:50%; }
.donut-inner{ position:relative; z-index:1; text-align:center; }
.donut-pct{ font-size:22px; font-weight:700; letter-spacing:-1px; line-height:1; }
.donut-lbl{ font-size:10px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }

/* STATUS BANNER */
.status-banner{ padding:13px 18px; border-radius:14px; margin-bottom:22px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:10px; }
.banner-ok  { background:rgba(34,197,94,.1);  border:1px solid rgba(34,197,94,.22);  color:#4ade80; }
.banner-warn{ background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.2);  color:#fbbf24; }
.banner-bad { background:rgba(244,63,94,.1);  border:1px solid rgba(244,63,94,.2);   color:#fb7185; }

/* STAT CARDS */
.stats-row{ display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:22px; }
.stat{ background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:18px 20px; position:relative; overflow:hidden; transition:.2s; }
.stat:hover{ transform:translateY(-2px); }
.stat::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.stat.s-present::before{ background:linear-gradient(90deg,#16a34a,var(--green)); }
.stat.s-absent::before { background:linear-gradient(90deg,#be123c,var(--red)); }
.stat.s-late::before   { background:linear-gradient(90deg,#d97706,var(--amber)); }
.stat.s-total::before  { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-hours::before  { background:linear-gradient(90deg,var(--purple),#c4b5fd); }
.stat-val{ font-size:30px; font-weight:700; letter-spacing:-1px; line-height:1; margin-bottom:5px; }
.stat-label{ font-size:10px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.8px; }

/* TABLE CARD */
.card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; overflow:hidden; }
.card-head{ display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:10px; }
.card-head h3{ font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
.count-pill{ background:var(--surface2); border:1px solid var(--border); padding:3px 12px; border-radius:50px; font-size:11px; font-weight:600; color:var(--muted); font-family:'DM Mono',monospace; }

/* VIEW TOGGLE */
.view-toggle{ display:flex; gap:6px; }
.vtab{ padding:6px 14px; border-radius:8px; font-size:12px; font-weight:700; border:1px solid var(--border); background:var(--surface2); color:var(--muted); cursor:pointer; transition:.15s; }
.vtab.active{ background:var(--accent); border-color:var(--accent); color:#fff; }

/* TABLE */
.table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
table{ width:100%; min-width:560px; border-collapse:collapse; }
thead th{ padding:11px 20px; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; text-align:left; white-space:nowrap; border-bottom:1px solid var(--border); }
tbody td{ padding:13px 20px; font-size:14px; border-bottom:1px solid rgba(255,255,255,.03); vertical-align:middle; }
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }
.row-num{ font-family:'DM Mono',monospace; font-size:12px; color:var(--muted); }
.date-cell{ font-weight:600; }
.day-sub{ display:block; font-size:11px; color:var(--muted); margin-top:1px; }
.time-mono{ font-family:'DM Mono',monospace; font-size:12px; }
.time-null{ font-family:'DM Mono',monospace; font-size:12px; color:var(--muted); }
.hours-cell{ font-family:'DM Mono',monospace; font-size:12px; font-weight:700; }
.hours-good{ color:#4ade80; }
.hours-short{ color:#fbbf24; }
.hours-null{ color:var(--muted); }
.badge{ display:inline-flex; align-items:center; padding:5px 13px; border-radius:50px; font-size:11px; font-weight:700; letter-spacing:.3px; text-transform:uppercase; }
.b-present{ background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.22); }
.b-absent { background:rgba(244,63,94,.10); color:#fb7185; border:1px solid rgba(244,63,94,.18); }
.b-late   { background:rgba(245,158,11,.10); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }
.table-view{ display:block; }
.table-view.hidden{ display:none; }

/* CALENDAR */
.calendar-wrap{ display:none; padding:24px; }
.calendar-wrap.active{ display:block; }
.cal-nav{ display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
.cal-nav-title{ font-size:17px; font-weight:700; letter-spacing:-.3px; }
.cal-nav-btns{ display:flex; gap:8px; }
.cal-nav-btn{ padding:6px 14px; border-radius:8px; border:1px solid var(--border); background:var(--surface2); color:var(--muted); font-size:12px; font-weight:600; text-decoration:none; transition:.15s; }
.cal-nav-btn:hover{ color:var(--text); }
.cal-grid{ display:grid; grid-template-columns:repeat(7,1fr); gap:5px; }
.cal-header{ text-align:center; padding:8px 4px; font-size:10px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }
.cal-day{ background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:8px 6px; min-height:70px; transition:.15s; }
.cal-day.empty{ background:transparent; border-color:transparent; }
.cal-day.today{ border-color:var(--accent); }
.cal-day.d-present{ background:rgba(34,197,94,.08); border-color:rgba(34,197,94,.28); }
.cal-day.d-absent { background:rgba(244,63,94,.07);  border-color:rgba(244,63,94,.22); }
.cal-day.d-late   { background:rgba(245,158,11,.07); border-color:rgba(245,158,11,.2); }
.cal-day.d-none   { opacity:.4; }
.cal-day-num{ font-size:12px; font-weight:700; margin-bottom:4px; }
.cal-day.today .cal-day-num{ color:var(--accent2); }
.cal-status{ font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
.cs-present{ color:#4ade80; }
.cs-absent { color:#fb7185; }
.cs-late   { color:#fbbf24; }
.cal-time{ font-family:'DM Mono',monospace; font-size:9px; color:var(--muted); line-height:1.4; }
.cal-hours{ font-family:'DM Mono',monospace; font-size:9px; font-weight:700; margin-top:2px; }

/* LEGEND */
.cal-legend{ display:flex; gap:16px; margin-top:14px; font-size:11px; color:var(--muted); flex-wrap:wrap; }
.cal-legend span{ display:flex; align-items:center; gap:5px; }
.leg-dot{ width:9px; height:9px; border-radius:3px; }

/* EMPTY STATE */
.empty-state{ text-align:center; padding:80px 24px; }
.empty-state h2{ font-size:22px; font-weight:700; margin-bottom:8px; }
.empty-state p{ color:var(--muted); font-size:14px; line-height:1.7; max-width:320px; margin:0 auto 24px; }
.try-btn{ display:inline-flex; align-items:center; gap:8px; padding:11px 24px; border-radius:12px; background:linear-gradient(135deg,var(--accent),#5b8af9); color:#fff; font-size:14px; font-weight:700; text-decoration:none; transition:.2s; }
.try-btn:hover{ transform:translateY(-2px); }

/* FOOTER */
footer{ position:relative; z-index:1; border-top:1px solid var(--border); padding:20px 48px; display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--muted); flex-wrap:wrap; gap:10px; margin-top:40px; }
footer a{ color:var(--muted); text-decoration:none; }
footer a:hover{ color:var(--text); }

/* MOBILE */
@media(max-width:768px){
    header{ padding:0 18px; }
    .main{ padding:20px 16px; }
    .student-name{ font-size:22px; }
    .stats-row{ grid-template-columns:repeat(3,1fr); gap:10px; }
    .stat{ padding:14px 12px; }
    .stat-val{ font-size:24px; }
    thead th, tbody td{ padding:10px 12px; font-size:13px; }
    .cal-grid{ gap:3px; }
    .cal-day{ padding:5px 4px; min-height:58px; border-radius:8px; }
    .cal-day-num{ font-size:11px; }
    footer{ padding:16px; flex-direction:column; align-items:center; text-align:center; }
}
@media(max-width:480px){
    .stats-row{ grid-template-columns:repeat(2,1fr); }
    .cal-time,.cal-hours{ display:none; }
}
</style>
</head>
<body>

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

<div class="main">

<?php if($student): ?>

    <!-- HERO CARD -->
    <div class="hero-card">
        <div class="student-info">
            <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
            <div class="meta-row">
                <div class="meta-pill">ID: <span><?php echo htmlspecialchars($student['student_id']); ?></span></div>
                <div class="meta-pill">Group: <span><?php echo htmlspecialchars($group_name); ?></span></div>
                <?php if(!empty($student['department'])): ?><div class="meta-pill"><?php echo htmlspecialchars($student['department']); ?></div><?php endif; ?>
                <?php if(!empty($student['course'])): ?><div class="meta-pill"><?php echo htmlspecialchars($student['course']); ?></div><?php endif; ?>
                <?php if(!empty($student['year'])): ?><div class="meta-pill"><?php echo htmlspecialchars($student['year']); ?></div><?php endif; ?>
            </div>
        </div>
        <div class="donut-wrap">
            <div class="donut">
                <div class="donut-inner">
                    <div class="donut-pct"><?php echo $percentage; ?>%</div>
                    <div class="donut-lbl">Rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS BANNER -->
    <?php
    if($percentage >= 75)     { $bc='banner-ok';   $bm="Good standing — attendance is above 75%."; }
    elseif($percentage >= 50) { $bc='banner-warn'; $bm="Warning — attendance is below 75%. Improvement needed."; }
    else                      { $bc='banner-bad';  $bm="Critical — attendance is below 50%. Immediate attention required."; }
    ?>
    <div class="status-banner <?php echo $bc; ?>"><?php echo $bm; ?></div>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat s-present"><div class="stat-val"><?php echo $total_present; ?></div><div class="stat-label">Present</div></div>
        <div class="stat s-absent"><div class="stat-val"><?php echo $total_absent; ?></div><div class="stat-label">Absent</div></div>
        <div class="stat s-late"><div class="stat-val"><?php echo $total_late; ?></div><div class="stat-label">Late</div></div>
        <div class="stat s-total"><div class="stat-val"><?php echo count($attendance); ?></div><div class="stat-label">Total Days</div></div>
        <div class="stat s-hours"><div class="stat-val" style="font-size:20px;"><?php echo $avg_hours; ?></div><div class="stat-label">Avg Hours</div></div>
    </div>

    <!-- ATTENDANCE HISTORY CARD -->
    <div class="card">
        <div class="card-head">
            <h3>Attendance History</h3>
            <span class="count-pill"><?php echo count($attendance); ?> records</span>
            <div class="view-toggle">
                <button class="vtab active" id="btnTable" onclick="switchView('table')">Table</button>
                <button class="vtab" id="btnCal" onclick="switchView('calendar')">Calendar</button>
            </div>
        </div>

        <!-- TABLE VIEW -->
        <div class="table-view" id="tableView">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Working Hrs</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $count = 1;
                    foreach($attendance as $row):
                        $s = $row['status'];
                        $bClass = $s=='Present' ? 'b-present' : ($s=='Late' ? 'b-late' : 'b-absent');
                        $wh = $row['working_hours'] ?? null;
                        $whClass = 'hours-null';
                        if($wh && $wh !== '00:00'){
                            $hrs = (int)explode(':', $wh)[0];
                            $whClass = $hrs >= 7 ? 'hours-good' : 'hours-short';
                        }
                    ?>
                    <tr>
                        <td class="row-num"><?php echo str_pad($count++, 2, '0', STR_PAD_LEFT); ?></td>
                        <td class="date-cell">
                            <?php echo date("d M Y", strtotime($row['date'])); ?>
                            <span class="day-sub"><?php echo date("l", strtotime($row['date'])); ?></span>
                        </td>
                        <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                        <td class="<?php echo $row['in_time'] ? 'time-mono' : 'time-null'; ?>"><?php echo $row['in_time'] ?? '—'; ?></td>
                        <td class="<?php echo $row['out_time'] ? 'time-mono' : 'time-null'; ?>"><?php echo $row['out_time'] ?? '—'; ?></td>
                        <td class="hours-cell <?php echo $whClass; ?>"><?php echo ($wh && $wh!=='00:00') ? $wh : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($attendance)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">No attendance records found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CALENDAR VIEW -->
        <div class="calendar-wrap" id="calView">
            <div class="cal-nav">
                <div class="cal-nav-title"><?php echo $month_names[$cal_month].' '.$cal_year; ?></div>
                <div class="cal-nav-btns">
                    <?php
                    $pm=$cal_month-1; $py=$cal_year; if($pm<1){$pm=12;$py--;}
                    $nm=$cal_month+1; $ny=$cal_year; if($nm>12){$nm=1;$ny++;}
                    $sid_url = urlencode($_GET['student_id'] ?? '');
                    ?>
                    <a href="view_attendance.php?student_id=<?php echo $sid_url; ?>&cal_month=<?php echo $pm; ?>&cal_year=<?php echo $py; ?>" class="cal-nav-btn">← Prev</a>
                    <a href="view_attendance.php?student_id=<?php echo $sid_url; ?>&cal_month=<?php echo date('m'); ?>&cal_year=<?php echo date('Y'); ?>" class="cal-nav-btn">Today</a>
                    <a href="view_attendance.php?student_id=<?php echo $sid_url; ?>&cal_month=<?php echo $nm; ?>&cal_year=<?php echo $ny; ?>" class="cal-nav-btn">Next →</a>
                </div>
            </div>

            <div class="cal-grid">
                <?php
                $dow_labels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                foreach($dow_labels as $lbl) echo '<div class="cal-header">'.$lbl.'</div>';
                for($i=0; $i<$start_dow; $i++) echo '<div class="cal-day empty"></div>';

                $today_d = (int)date("j");
                $today_m = (int)date("m");
                $today_y = (int)date("Y");

                for($d=1; $d<=$days_in_month; $d++){
                    $dt  = sprintf("%04d-%02d-%02d", $cal_year, $cal_month, $d);
                    $rec = $att_by_date[$dt] ?? null;
                    $is_today = ($d===$today_d && $cal_month===$today_m && $cal_year===$today_y);

                    $cls = 'cal-day';
                    if($is_today) $cls .= ' today';
                    if($rec){
                        $st = $rec['status'];
                        if($st==='Present')      $cls .= ' d-present';
                        elseif($st==='Absent')   $cls .= ' d-absent';
                        elseif($st==='Late')     $cls .= ' d-late';
                    } else {
                        // Past day with no record
                        if(strtotime($dt) < strtotime(date('Y-m-d'))) $cls .= ' d-none';
                    }

                    echo '<div class="'.$cls.'">';
                    echo '<div class="cal-day-num">'.$d.'</div>';
                    if($rec){
                        $st = $rec['status'];
                        $sc = $st==='Present' ? 'cs-present' : ($st==='Late' ? 'cs-late' : 'cs-absent');
                        echo '<div class="cal-status '.$sc.'">'.$st.'</div>';
                        if($rec['in_time'] || $rec['out_time']){
                            echo '<div class="cal-time">';
                            if($rec['in_time'])  echo 'IN: '.htmlspecialchars($rec['in_time']).'<br>';
                            if($rec['out_time']) echo 'OUT: '.htmlspecialchars($rec['out_time']);
                            echo '</div>';
                        }
                        if(!empty($rec['working_hours']) && $rec['working_hours'] !== '00:00'){
                            $wh_hrs = (int)explode(':',$rec['working_hours'])[0];
                            $wh_col = $wh_hrs >= 7 ? '#4ade80' : '#fbbf24';
                            echo '<div class="cal-hours" style="color:'.$wh_col.';">'.$rec['working_hours'].'h</div>';
                        }
                    }
                    echo '</div>';
                }

                $trailing = (7 - (($start_dow + $days_in_month) % 7)) % 7;
                for($i=0; $i<$trailing; $i++) echo '<div class="cal-day empty"></div>';
                ?>
            </div>

            <div class="cal-legend">
                <span><span class="leg-dot" style="background:rgba(34,197,94,.4);"></span>Present</span>
                <span><span class="leg-dot" style="background:rgba(244,63,94,.35);"></span>Absent</span>
                <span><span class="leg-dot" style="background:rgba(245,158,11,.35);"></span>Late</span>
                <span><span class="leg-dot" style="border:1px solid var(--accent);background:transparent;"></span>Today</span>
                <span><span class="leg-dot" style="background:var(--surface2);opacity:.4;"></span>No record</span>
            </div>
        </div>

    </div>

<?php else: ?>

    <div class="empty-state">
        <div style="font-size:52px;margin-bottom:16px;opacity:.4;">🔍</div>
        <h2>Member Not Found</h2>
        <p>No records found for that ID. Please check and try again from the home page.</p>
        <a href="index.php" class="try-btn">Try Again</a>
    </div>

<?php endif; ?>

</div>

<footer>
    <span>Smart Attendance Monitoring System &copy; 2026</span>
    <a href="aboutus.php">About Us</a>
</footer>

<script>
function switchView(view){
    var tv  = document.getElementById('tableView');
    var cv  = document.getElementById('calView');
    var bt  = document.getElementById('btnTable');
    var bc  = document.getElementById('btnCal');
    if(view === 'table'){
        tv.classList.remove('hidden');
        cv.classList.remove('active');
        bt.classList.add('active');
        bc.classList.remove('active');
    } else {
        tv.classList.add('hidden');
        cv.classList.add('active');
        bt.classList.remove('active');
        bc.classList.add('active');
    }
}
</script>
</body>
</html>