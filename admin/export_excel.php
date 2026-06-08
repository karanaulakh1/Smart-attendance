<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit(); }

date_default_timezone_set("Asia/Kolkata");

// ── GET PARAMS ──────────────────────────────────────────────────────────
$group      = isset($_GET['group'])      ? $_GET['group']      : 'students';
$att_table  = isset($_GET['att_table'])  ? $_GET['att_table']  : 'attendance';
$course     = isset($_GET['course'])     ? $_GET['course']     : '';
$from_date  = isset($_GET['from_date'])  ? $_GET['from_date']  : '';
$to_date    = isset($_GET['to_date'])    ? $_GET['to_date']    : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// ── VALIDATE GROUP ──────────────────────────────────────────────────────
$valid = $conn->query("SELECT * FROM groups_registry WHERE table_name='$group' AND attendance_table='$att_table'");
if(!$valid || $valid->num_rows === 0){ die("Invalid group."); }
$group_info   = $valid->fetch_assoc();
$group_name   = $group_info['group_name'];
$member_table = $group;

// ── BUILD WHERE ─────────────────────────────────────────────────────────
$conditions = [];
if(!empty($course))     $conditions[] = "m.course = '$course'";
if(!empty($from_date))  $conditions[] = "a.date >= '$from_date'";
if(!empty($to_date))    $conditions[] = "a.date <= '$to_date'";
if(!empty($student_id)) $conditions[] = "a.student_id = '$student_id'";
$where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// ── CHECK IN/OUT COLUMNS ────────────────────────────────────────────────
$col_check = $conn->query("SHOW COLUMNS FROM `$att_table` LIKE 'in_time'");
$has_inout  = ($col_check && $col_check->num_rows > 0);

// ── QUERY ───────────────────────────────────────────────────────────────
$sql = "
    SELECT
        a.id,
        a.student_id,
        m.name,
        m.department,
        m.course,
        m.year,
        a.date,
        DAYNAME(a.date) as day_name,
        a.status,
        " . ($has_inout ? "a.in_time, a.out_time, a.working_hours" : "NULL as in_time, NULL as out_time, NULL as working_hours") . "
    FROM `$att_table` a
    LEFT JOIN `$member_table` m ON a.student_id = m.student_id
    $where
    ORDER BY a.date DESC, m.name ASC
";
$result = $conn->query($sql);
if(!$result){ die("Query error: " . $conn->error); }

// ── FILENAME ────────────────────────────────────────────────────────────
$parts = ['attendance', $group_name];
if(!empty($course))     $parts[] = $course;
if(!empty($student_id)) $parts[] = $student_id;
if(!empty($from_date))  $parts[] = $from_date;
if(!empty($to_date))    $parts[] = 'to_'.$to_date;
$filename = strtolower(str_replace([' ','/','\\'], '_', implode('_', $parts))) . '.csv';

// ── USE PHP XLSXWRITER FOR PROPER EXCEL OUTPUT ─────────────────────────
// Since we don't have xlsxwriter, we output a proper UTF-8 CSV
// that Excel can read correctly by using tab-separated or with BOM

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM — fixes encoding issues in Excel (the â€" problem)
fputs($output, "\xEF\xBB\xBF");

// ── REPORT HEADER ───────────────────────────────────────────────────────
fputcsv($output, ['SMART ATTENDANCE SYSTEM', '', '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Generated On:', date("d M Y, h:i A"), '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Group:', $group_name, '', '', '', '', '', '', '', '', '', '']);

if(!empty($course))
    fputcsv($output, ['Course / Dept:', $course, '', '', '', '', '', '', '', '', '', '']);

if(!empty($student_id))
    fputcsv($output, ['Member ID:', $student_id, '', '', '', '', '', '', '', '', '', '']);

if(!empty($from_date) || !empty($to_date))
    fputcsv($output, ['Date Range:', ($from_date ?: 'All') . ' to ' . ($to_date ?: 'All'), '', '', '', '', '', '', '', '', '', '']);

fputcsv($output, ['Total Records:', $result->num_rows, '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '']); // blank row

// ── COLUMN HEADERS ──────────────────────────────────────────────────────
fputcsv($output, [
    '#',
    'Member ID',
    'Name',
    'Department',
    'Course',
    'Year',
    'Date',
    'Day',
    'Status',
    'In Time',
    'Out Time',
    'Working Hours',
]);

// ── DATA ROWS ───────────────────────────────────────────────────────────
$count           = 1;
$total_present   = 0;
$total_absent    = 0;
$total_late      = 0;
$total_hours_sec = 0;
$hours_count     = 0;

while($row = $result->fetch_assoc()){

    if($row['status'] === 'Present')    $total_present++;
    elseif($row['status'] === 'Absent') $total_absent++;
    elseif($row['status'] === 'Late')   $total_late++;

    if(!empty($row['working_hours']) && $row['working_hours'] !== '00:00'){
        $parts_wh = explode(':', $row['working_hours']);
        if(count($parts_wh) === 2){
            $total_hours_sec += ((int)$parts_wh[0] * 3600) + ((int)$parts_wh[1] * 60);
            $hours_count++;
        }
    }

    // Format date nicely
    $formatted_date = !empty($row['date']) ? date("d M Y", strtotime($row['date'])) : '';

    // Use empty string instead of dash to avoid encoding issues
    fputcsv($output, [
        $count++,
        $row['student_id']    ?? '',
        $row['name']          ?? '',
        $row['department']    ?? '',
        $row['course']        ?? '',
        $row['year']          ?? '',
        $formatted_date,
        $row['day_name']      ?? '',
        $row['status']        ?? '',
        $row['in_time']       ?? 'Not recorded',
        $row['out_time']      ?? 'Not recorded',
        $row['working_hours'] ?? 'N/A',
    ]);
}

// ── SUMMARY ─────────────────────────────────────────────────────────────
fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '']); // blank
fputcsv($output, ['SUMMARY', '', '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Total Present',  $total_present,  '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Total Absent',   $total_absent,   '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Total Late',     $total_late,     '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Total Days',     $total_present + $total_absent + $total_late, '', '', '', '', '', '', '', '', '', '']);

if($hours_count > 0){
    $th = floor($total_hours_sec / 3600);
    $tm = floor(($total_hours_sec % 3600) / 60);
    fputcsv($output, ['Total Working Hours',   sprintf("%02d:%02d", $th, $tm), '', '', '', '', '', '', '', '', '', '']);

    $avg_sec = $total_hours_sec / $hours_count;
    $ah = floor($avg_sec / 3600);
    $am = floor(($avg_sec % 3600) / 60);
    fputcsv($output, ['Avg Working Hours/Day', sprintf("%02d:%02d", $ah, $am), '', '', '', '', '', '', '', '', '', '']);
}

$total_days = $total_present + $total_absent + $total_late;
if($total_days > 0){
    $rate = round(($total_present / $total_days) * 100);
    fputcsv($output, ['Attendance Rate', $rate . '%', '', '', '', '', '', '', '', '', '', '']);
}

fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '']);
fputcsv($output, ['Smart Attendance System', '', '', '', '', '', '', '', '', '', '', '']);

fclose($output);
exit();
?>