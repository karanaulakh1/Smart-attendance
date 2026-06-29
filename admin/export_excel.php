<?php
/**
 * export_excel.php
 * Generates a real .xlsx file via a Python/openpyxl helper script
 * Columns are auto-sized, headers are styled, summary is highlighted
 */

session_start();
include '../database.php';
if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit(); }

date_default_timezone_set("Asia/Kolkata");

// ── PARAMS ──────────────────────────────────────────────────────────────
$group      = isset($_GET['group'])      ? $_GET['group']      : 'students';
$att_table  = isset($_GET['att_table'])  ? $_GET['att_table']  : 'attendance';
$course     = isset($_GET['course'])     ? $_GET['course']     : '';
$from_date  = isset($_GET['from_date'])  ? $_GET['from_date']  : '';
$to_date    = isset($_GET['to_date'])    ? $_GET['to_date']    : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// ── VALIDATE GROUP ───────────────────────────────────────────────────────
$valid = $conn->query("SELECT * FROM groups_registry WHERE table_name='$group' AND attendance_table='$att_table'");
if(!$valid || $valid->num_rows === 0) die("Invalid group.");
$group_info   = $valid->fetch_assoc();
$group_name   = $group_info['group_name'];
$member_table = $group;

// ── WHERE CLAUSE ─────────────────────────────────────────────────────────
$conditions = [];
if(!empty($course))     $conditions[] = "m.course = '$course'";
if(!empty($from_date))  $conditions[] = "a.date >= '$from_date'";
if(!empty($to_date))    $conditions[] = "a.date <= '$to_date'";
if(!empty($student_id)) $conditions[] = "a.student_id = '$student_id'";
$where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// ── CHECK IN/OUT COLS ────────────────────────────────────────────────────
$col_check = $conn->query("SHOW COLUMNS FROM `$att_table` LIKE 'in_time'");
$has_inout  = ($col_check && $col_check->num_rows > 0);

// ── QUERY ────────────────────────────────────────────────────────────────
$sql = "
    SELECT
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
if(!$result) die("Query error: " . $conn->error);

// ── COLLECT DATA ─────────────────────────────────────────────────────────
$rows            = [];
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
        $p = explode(':', $row['working_hours']);
        if(count($p) === 2){
            $total_hours_sec += ((int)$p[0] * 3600) + ((int)$p[1] * 60);
            $hours_count++;
        }
    }

    $rows[] = [
        $row['student_id']    ?? '',
        $row['name']          ?? '',
        $row['department']    ?? '',
        $row['course']        ?? '',
        $row['year']          ?? '',
        !empty($row['date'])  ? date("d M Y", strtotime($row['date'])) : '',
        $row['day_name']      ?? '',
        $row['status']        ?? '',
        $row['in_time']       ?? 'Not recorded',
        $row['out_time']      ?? 'Not recorded',
        $row['working_hours'] ?? 'N/A',
    ];
}

// ── SUMMARY ──────────────────────────────────────────────────────────────
$total_days = $total_present + $total_absent + $total_late;
$rate       = $total_days > 0 ? round(($total_present / $total_days) * 100) : 0;

$total_h_str = '—';
$avg_h_str   = '—';
if($hours_count > 0){
    $th = floor($total_hours_sec / 3600);
    $tm = floor(($total_hours_sec % 3600) / 60);
    $total_h_str = sprintf("%02d:%02d", $th, $tm);
    $as = $total_hours_sec / $hours_count;
    $avg_h_str = sprintf("%02d:%02d", floor($as/3600), floor(($as%3600)/60));
}

// ── META ─────────────────────────────────────────────────────────────────
$meta = [
    'generated'   => date("d M Y, h:i A"),
    'group'       => $group_name,
    'course'      => $course ?: 'All',
    'member_id'   => $student_id ?: 'All',
    'from_date'   => $from_date ?: 'All',
    'to_date'     => $to_date   ?: 'All',
    'total_records' => count($rows),
];

$summary = [
    'total_present'  => $total_present,
    'total_absent'   => $total_absent,
    'total_late'     => $total_late,
    'total_days'     => $total_days,
    'total_hours'    => $total_h_str,
    'avg_hours'      => $avg_h_str,
    'rate'           => $rate . '%',
];

// ── FILENAME ─────────────────────────────────────────────────────────────
$fname_parts = ['attendance', $group_name];
if(!empty($course))     $fname_parts[] = $course;
if(!empty($student_id)) $fname_parts[] = $student_id;
if(!empty($from_date))  $fname_parts[] = $from_date;
if(!empty($to_date))    $fname_parts[] = 'to_'.$to_date;
$filename = strtolower(str_replace([' ','/','\\'], '_', implode('_', $fname_parts))) . '.xlsx';

// ── WRITE PYTHON SCRIPT + JSON DATA TO TEMP FILES ────────────────────────
$tmp_dir  = sys_get_temp_dir();
$data_file = $tmp_dir . '/att_data_' . session_id() . '.json';
$out_file  = $tmp_dir . '/att_out_'  . session_id() . '.xlsx';
$py_file   = $tmp_dir . '/att_gen_'  . session_id() . '.py';

// Write data as JSON
file_put_contents($data_file, json_encode([
    'meta'    => $meta,
    'rows'    => $rows,
    'summary' => $summary,
    'filename'=> $filename,
]));

// Write Python script
$py_script = <<<'PYTHON'
import json, sys
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

data_file = sys.argv[1]
out_file  = sys.argv[2]

with open(data_file) as f:
    d = json.load(f)

meta    = d['meta']
rows    = d['rows']
summary = d['summary']

wb = Workbook()
ws = wb.active
ws.title = "Attendance"

# ── COLORS ───────────────────────────────────────────────────────
C_HEADER_BG  = "1E3A5F"   # dark blue
C_HEADER_FG  = "FFFFFF"   # white
C_META_BG    = "EEF2FF"   # light lavender
C_META_FG    = "1E3A5F"
C_COL_HEADER = "2D6A4F"   # dark green
C_COL_FG     = "FFFFFF"
C_PRESENT    = "D1FAE5"   # light green
C_ABSENT     = "FFE4E6"   # light red
C_LATE       = "FEF3C7"   # light amber
C_SUMMARY_BG = "1E3A5F"
C_SUMMARY_FG = "FFFFFF"
C_ALT_ROW    = "F8FAFC"   # very light grey

def hfont(bold=False, color="000000", size=11):
    return Font(name="Arial", bold=bold, color=color, size=size)

def hfill(color):
    return PatternFill("solid", start_color=color, fgColor=color)

def hborder():
    s = Side(style="thin", color="D1D5DB")
    return Border(left=s, right=s, top=s, bottom=s)

def center():
    return Alignment(horizontal="center", vertical="center", wrap_text=True)

def left():
    return Alignment(horizontal="left", vertical="center", wrap_text=True)

# ── REPORT TITLE ─────────────────────────────────────────────────
ws.merge_cells("A1:K1")
title_cell = ws["A1"]
title_cell.value     = "SMART ATTENDANCE SYSTEM — Export Report"
title_cell.font      = hfont(bold=True, color=C_HEADER_FG, size=14)
title_cell.fill      = hfill(C_HEADER_BG)
title_cell.alignment = center()
ws.row_dimensions[1].height = 28

# ── META INFO ────────────────────────────────────────────────────
meta_rows = [
    ("Generated On",  meta['generated']),
    ("Group",         meta['group']),
    ("Course / Dept", meta['course']),
    ("Member ID",     meta['member_id']),
    ("Date Range",    meta['from_date'] + " to " + meta['to_date']),
    ("Total Records", str(meta['total_records'])),
]
for i, (label, value) in enumerate(meta_rows, 2):
    lc = ws.cell(row=i, column=1, value=label)
    vc = ws.cell(row=i, column=2, value=value)
    ws.merge_cells(f"B{i}:K{i}")
    lc.font = hfont(bold=True, color=C_META_FG)
    vc.font = hfont(color=C_META_FG)
    for col in range(1, 12):
        ws.cell(row=i, column=col).fill = hfill(C_META_BG)
        ws.cell(row=i, column=col).alignment = left()

# blank row after meta
blank_row = len(meta_rows) + 2 + 1  # row 9
ws.row_dimensions[blank_row].height = 8

# ── COLUMN HEADERS ───────────────────────────────────────────────
header_row = blank_row + 1  # row 10
headers = ["#", "Member ID", "Name", "Department", "Course",
           "Year", "Date", "Day", "Status", "In Time", "Out Time", "Working Hours"]
# Add extra # column
for col, h in enumerate(headers, 1):
    c = ws.cell(row=header_row, column=col, value=h)
    c.font      = hfont(bold=True, color=C_COL_FG, size=10)
    c.fill      = hfill(C_COL_HEADER)
    c.alignment = center()
    c.border    = hborder()
ws.row_dimensions[header_row].height = 22

# ── DATA ROWS ────────────────────────────────────────────────────
for i, row in enumerate(rows, 1):
    excel_row = header_row + i
    # Row number first
    num_cell = ws.cell(row=excel_row, column=1, value=i)
    num_cell.font      = hfont(color="9CA3AF", size=10)
    num_cell.alignment = center()
    num_cell.border    = hborder()

    status = row[7] if len(row) > 7 else ""
    if status   == "Present": row_fill = hfill(C_PRESENT)
    elif status == "Absent":  row_fill = hfill(C_ABSENT)
    elif status == "Late":    row_fill = hfill(C_LATE)
    else:                     row_fill = hfill(C_ALT_ROW) if i % 2 == 0 else PatternFill()

    for col, val in enumerate(row, 2):
        c = ws.cell(row=excel_row, column=col, value=val)
        c.font      = hfont(size=10)
        c.alignment = center() if col in [1, 6, 7, 8, 9, 10, 11, 12] else left()
        c.border    = hborder()
        c.fill      = row_fill

    ws.row_dimensions[excel_row].height = 18

# ── BLANK ROW ────────────────────────────────────────────────────
summary_start = header_row + len(rows) + 2

# ── SUMMARY HEADER ───────────────────────────────────────────────
ws.merge_cells(f"A{summary_start}:K{summary_start}")
sh = ws.cell(row=summary_start, column=1, value="ATTENDANCE SUMMARY")
sh.font      = hfont(bold=True, color=C_HEADER_FG, size=12)
sh.fill      = hfill(C_HEADER_BG)
sh.alignment = center()
ws.row_dimensions[summary_start].height = 24

sum_rows = [
    ("Total Present",        str(summary['total_present'])),
    ("Total Absent",         str(summary['total_absent'])),
    ("Total Late",           str(summary['total_late'])),
    ("Total Days",           str(summary['total_days'])),
    ("Total Working Hours",  summary['total_hours']),
    ("Avg Working Hours/Day",summary['avg_hours']),
    ("Attendance Rate",      summary['rate']),
]
for j, (label, value) in enumerate(sum_rows):
    r = summary_start + 1 + j
    lc = ws.cell(row=r, column=1, value=label)
    vc = ws.cell(row=r, column=2, value=value)
    ws.merge_cells(f"B{r}:K{r}")
    lc.font = hfont(bold=True, color=C_META_FG)
    vc.font = hfont(bold=True, color="1D4ED8")
    for col in range(1, 12):
        ws.cell(row=r, column=col).fill = hfill("EEF2FF")
        ws.cell(row=r, column=col).border = hborder()
        ws.cell(row=r, column=col).alignment = left()
    ws.row_dimensions[r].height = 18

# ── COLUMN WIDTHS ────────────────────────────────────────────────
col_widths = [5, 14, 26, 22, 18, 10, 14, 12, 10, 12, 12, 15]
for i, w in enumerate(col_widths, 1):
    ws.column_dimensions[get_column_letter(i)].width = w

# ── FREEZE PANES ─────────────────────────────────────────────────
ws.freeze_panes = f"A{header_row + 1}"

wb.save(out_file)
print("OK")
PYTHON;

file_put_contents($py_file, $py_script);

// ── RUN PYTHON ───────────────────────────────────────────────────────────
$cmd    = escapeshellcmd("python3 $py_file $data_file $out_file") . " 2>&1";
$output = shell_exec($cmd);

// Cleanup temp files
@unlink($data_file);
@unlink($py_file);

if(!file_exists($out_file) || trim($output) !== 'OK'){
    // Fallback to CSV if Python fails
    @unlink($out_file);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('.xlsx','.csv',$filename) . '"');
    $out = fopen('php://output','w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['#','Member ID','Name','Department','Course','Year','Date','Day','Status','In Time','Out Time','Working Hours']);
    foreach($rows as $i => $row){
        fputcsv($out, array_merge([$i+1], $row));
    }
    fclose($out);
    exit();
}

// ── SEND XLSX ────────────────────────────────────────────────────────────
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($out_file));
header('Pragma: no-cache');
header('Expires: 0');

readfile($out_file);
@unlink($out_file);
exit();
?>