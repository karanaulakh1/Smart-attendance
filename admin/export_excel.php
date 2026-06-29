<?php
/**
 * export_excel.php
 * Pure PHP .xlsx writer — no Python, no libraries, works on Render free tier
 * .xlsx = ZIP containing XML files — built with PHP's built-in ZipArchive
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
$valid = $conn->query("SELECT * FROM groups_registry
    WHERE table_name='$group' AND attendance_table='$att_table'");
if(!$valid || $valid->num_rows === 0) die("Invalid group.");
$group_info   = $valid->fetch_assoc();
$group_name   = $group_info['group_name'];
$member_table = $group;

// ── WHERE CLAUSE ─────────────────────────────────────────────────────────
$conds = [];
if(!empty($course))     $conds[] = "m.course='$course'";
if(!empty($from_date))  $conds[] = "a.date>='$from_date'";
if(!empty($to_date))    $conds[] = "a.date<='$to_date'";
if(!empty($student_id)) $conds[] = "a.student_id='$student_id'";
$where = $conds ? "WHERE ".implode(" AND ",$conds) : "";

// ── CHECK IN/OUT COLUMNS ──────────────────────────────────────────────────
$col_check = $conn->query("SHOW COLUMNS FROM `$att_table` LIKE 'in_time'");
$has_inout  = ($col_check && $col_check->num_rows > 0);

// ── QUERY ─────────────────────────────────────────────────────────────────
$sql = "SELECT a.student_id, m.name, m.department, m.course, m.year,
               a.date, DAYNAME(a.date) as day_name, a.status,
               ".($has_inout ? "a.in_time, a.out_time, a.working_hours" :
                               "NULL as in_time, NULL as out_time, NULL as working_hours")."
        FROM `$att_table` a
        LEFT JOIN `$member_table` m ON a.student_id=m.student_id
        $where
        ORDER BY a.date DESC, m.name ASC";

$result = $conn->query($sql);
if(!$result) die("Query error: ".$conn->error);

// ── COLLECT DATA ──────────────────────────────────────────────────────────
$rows = [];
$total_present = $total_absent = $total_late = 0;
$total_sec = $hours_count = 0;

while($r = $result->fetch_assoc()){
    if($r['status']==='Present')     $total_present++;
    elseif($r['status']==='Absent')  $total_absent++;
    elseif($r['status']==='Late')    $total_late++;

    if(!empty($r['working_hours']) && $r['working_hours']!=='00:00'){
        $p = explode(':', $r['working_hours']);
        if(count($p)===2){
            $total_sec += (int)$p[0]*3600 + (int)$p[1]*60;
            $hours_count++;
        }
    }
    $rows[] = $r;
}

$total_days = $total_present + $total_absent + $total_late;
$rate       = $total_days > 0 ? round($total_present/$total_days*100) : 0;
$total_h    = $hours_count > 0 ? sprintf("%02d:%02d", floor($total_sec/3600), floor(($total_sec%3600)/60)) : 'N/A';
$avg_h      = $hours_count > 0 ? sprintf("%02d:%02d", floor(($total_sec/$hours_count)/3600), floor((($total_sec/$hours_count)%3600)/60)) : 'N/A';

// ── FILENAME ──────────────────────────────────────────────────────────────
$fparts = ['attendance', $group_name];
if($course)     $fparts[] = $course;
if($student_id) $fparts[] = $student_id;
if($from_date)  $fparts[] = $from_date;
if($to_date)    $fparts[] = 'to_'.$to_date;
$filename = strtolower(str_replace([' ','/','\\'],'_',implode('_',$fparts))).'.xlsx';

// ════════════════════════════════════════════════════════════════════════
// PURE PHP XLSX BUILDER
// .xlsx = ZIP with these files inside:
//   [Content_Types].xml
//   _rels/.rels
//   xl/workbook.xml
//   xl/_rels/workbook.xml.rels
//   xl/styles.xml
//   xl/sharedStrings.xml  (string table)
//   xl/worksheets/sheet1.xml
// ════════════════════════════════════════════════════════════════════════

// ── HELPER: escape XML special chars ─────────────────────────────────────
function xe($s){ return htmlspecialchars((string)$s, ENT_XML1, 'UTF-8'); }

// ── HELPER: col letter from 1-based index ────────────────────────────────
function colLetter($n){
    $s='';
    while($n>0){
        $n--; $s=chr(65+($n%26)).$s; $n=floor($n/26);
    }
    return $s;
}

// ── SHARED STRINGS TABLE ─────────────────────────────────────────────────
// All cell text goes through a shared strings table for proper UTF-8
$strings    = [];
$strIndex   = [];
function si($val){
    global $strings, $strIndex;
    $val = (string)$val;
    if(!isset($strIndex[$val])){
        $strIndex[$val] = count($strings);
        $strings[] = $val;
    }
    return $strIndex[$val];
}

// ── STYLE INDEXES (defined in styles.xml) ────────────────────────────────
// 0 = default
// 1 = title        (bold, white, dark-blue bg)
// 2 = meta-label   (bold, dark-blue text, light-blue bg)
// 3 = meta-value   (normal, dark-blue text, light-blue bg)
// 4 = col-header   (bold, white, dark-green bg)
// 5 = data-normal  (normal, borders)
// 6 = data-present (normal, light-green bg, borders)
// 7 = data-absent  (normal, light-red bg, borders)
// 8 = data-late    (normal, light-amber bg, borders)
// 9 = summary-hdr  (bold, white, dark-blue bg)
// 10= summary-val  (bold, blue text, light-blue bg)
// 11= data-altrow  (normal, light-grey bg, borders)

// ── BUILD WORKSHEET ROWS ─────────────────────────────────────────────────
$xmlRows = '';

// row helper: builds one <row> element
// $cells = array of [colIdx, value, styleIdx, isNumber]
function buildRow($rowNum, $cells){
    $r = '<row r="'.$rowNum.'">';
    foreach($cells as $c){
        list($col, $val, $style, $isNum) = $c + [3=>false];
        $ref = colLetter($col).$rowNum;
        if($isNum){
            $r .= '<c r="'.$ref.'" s="'.$style.'"><v>'.xe($val).'</v></c>';
        } else {
            $idx = si($val);
            $r .= '<c r="'.$ref.'" t="s" s="'.$style.'"><v>'.$idx.'</v></c>';
        }
    }
    $r .= '</row>';
    return $r;
}

$ROW = 1;
$COLS = 12; // total columns

// ── ROW 1: TITLE ──────────────────────────────────────────────────────────
$cells = [[1, 'SMART ATTENDANCE SYSTEM — Export Report', 1, false]];
// fill remaining cols with title style for merged look
for($c=2;$c<=$COLS;$c++) $cells[] = [$c,'',1,false];
$xmlRows .= buildRow($ROW++, $cells);

// ── ROWS 2-7: META INFO ───────────────────────────────────────────────────
$metaData = [
    ['Generated On',  date("d M Y, h:i A")],
    ['Group',         $group_name],
    ['Course / Dept', $course ?: 'All'],
    ['Member ID',     $student_id ?: 'All'],
    ['Date Range',    ($from_date?:'All').' to '.($to_date?:'All')],
    ['Total Records', count($rows)],
];
foreach($metaData as $m){
    $cells = [[1, $m[0], 2, false], [2, $m[1], 3, false]];
    for($c=3;$c<=$COLS;$c++) $cells[] = [$c,'',3,false];
    $xmlRows .= buildRow($ROW++, $cells);
}

// ── ROW 8: BLANK ─────────────────────────────────────────────────────────
$cells=[];
for($c=1;$c<=$COLS;$c++) $cells[] = [$c,'',0,false];
$xmlRows .= buildRow($ROW++, $cells);

// ── ROW 9: COLUMN HEADERS ─────────────────────────────────────────────────
$headers = ['#','Member ID','Name','Department','Course','Year',
            'Date','Day','Status','In Time','Out Time','Working Hours'];
$cells=[];
foreach($headers as $i=>$h) $cells[] = [$i+1, $h, 4, false];
$xmlRows .= buildRow($ROW++, $cells);
$dataStartRow = $ROW;

// ── DATA ROWS ─────────────────────────────────────────────────────────────
foreach($rows as $i=>$r){
    $s = $r['status'];
    if($s==='Present')     $style = 6;
    elseif($s==='Absent')  $style = 7;
    elseif($s==='Late')    $style = 8;
    else                   $style = ($i%2===0) ? 11 : 5;

    $wh = $r['working_hours'] ?: 'N/A';
    if($wh==='00:00') $wh='N/A';

    $cells = [
        [1,  $i+1,                                                 $style, true],
        [2,  $r['student_id']  ?? '',                              $style, false],
        [3,  $r['name']        ?? '',                              $style, false],
        [4,  $r['department']  ?? '',                              $style, false],
        [5,  $r['course']      ?? '',                              $style, false],
        [6,  $r['year']        ?? '',                              $style, false],
        [7,  !empty($r['date']) ? date("d M Y",strtotime($r['date'])) : '', $style, false],
        [8,  $r['day_name']    ?? '',                              $style, false],
        [9,  $s,                                                   $style, false],
        [10, $r['in_time']     ?? 'Not recorded',                  $style, false],
        [11, $r['out_time']    ?? 'Not recorded',                  $style, false],
        [12, $wh,                                                  $style, false],
    ];
    $xmlRows .= buildRow($ROW++, $cells);
}

// ── BLANK ROW BEFORE SUMMARY ──────────────────────────────────────────────
$cells=[];
for($c=1;$c<=$COLS;$c++) $cells[] = [$c,'',0,false];
$xmlRows .= buildRow($ROW++, $cells);

// ── SUMMARY HEADER ────────────────────────────────────────────────────────
$cells = [[1,'ATTENDANCE SUMMARY',9,false]];
for($c=2;$c<=$COLS;$c++) $cells[] = [$c,'',9,false];
$xmlRows .= buildRow($ROW++, $cells);

// ── SUMMARY DATA ──────────────────────────────────────────────────────────
$summaryData = [
    ['Total Present',        $total_present],
    ['Total Absent',         $total_absent],
    ['Total Late',           $total_late],
    ['Total Days',           $total_days],
    ['Total Working Hours',  $total_h],
    ['Avg Working Hours/Day',$avg_h],
    ['Attendance Rate',      $rate.'%'],
];
foreach($summaryData as $sd){
    $cells = [[1,$sd[0],2,false],[2,$sd[1],10,false]];
    for($c=3;$c<=$COLS;$c++) $cells[] = [$c,'',3,false];
    $xmlRows .= buildRow($ROW++, $cells);
}

// ── BUILD sharedStrings.xml ───────────────────────────────────────────────
$ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
$ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
$ssXml .= ' count="'.count($strings).'" uniqueCount="'.count($strings).'">'."\n";
foreach($strings as $s){
    $ssXml .= '<si><t xml:space="preserve">'.xe($s).'</t></si>'."\n";
}
$ssXml .= '</sst>';

// ── BUILD styles.xml ─────────────────────────────────────────────────────
// Border index 0=none, 1=thin-grey
$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="6">
  <font><sz val="11"/><name val="Arial"/></font>
  <font><sz val="14"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
  <font><sz val="10"/><b/><color rgb="FF1E3A5F"/><name val="Arial"/></font>
  <font><sz val="10"/><color rgb="FF1E3A5F"/><name val="Arial"/></font>
  <font><sz val="10"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
  <font><sz val="10"/><b/><color rgb="FF1D4ED8"/><name val="Arial"/></font>
</fonts>
<fills count="9">
  <fill><patternFill patternType="none"/></fill>
  <fill><patternFill patternType="gray125"/></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/></patternFill></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FFEEF2FF"/></patternFill></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FF2D6A4F"/></patternFill></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/></patternFill></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FFFFE4E6"/></patternFill></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>
  <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
</fills>
<borders count="2">
  <border><left/><right/><top/><bottom/><diagonal/></border>
  <border>
    <left style="thin"><color rgb="FFD1D5DB"/></left>
    <right style="thin"><color rgb="FFD1D5DB"/></right>
    <top style="thin"><color rgb="FFD1D5DB"/></top>
    <bottom style="thin"><color rgb="FFD1D5DB"/></bottom>
  </border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="12">
  <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
  <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center"/></xf>
  <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
  <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center"/></xf>
  <xf numFmtId="0" fontId="5" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
  <xf numFmtId="0" fontId="0" fillId="8" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
</cellXfs>
</styleSheet>';

// ── BUILD worksheet/sheet1.xml ────────────────────────────────────────────
$colWidths  = [5, 14, 26, 22, 18, 10, 14, 12, 10, 12, 12, 15];
$colXml = '';
foreach($colWidths as $i=>$w)
    $colXml .= '<col min="'.($i+1).'" max="'.($i+1).'" width="'.$w.'" customWidth="1"/>';

$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$dataStartRow.'" topLeftCell="A'.($dataStartRow+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>
<sheetFormatPr defaultRowHeight="18"/>
<cols>'.$colXml.'</cols>
<sheetData>'.$xmlRows.'</sheetData>
</worksheet>';

// ── BUILD workbook.xml ────────────────────────────────────────────────────
$wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Attendance" sheetId="1" r:id="rId1"/></sheets>
</workbook>';

// ── BUILD _rels ───────────────────────────────────────────────────────────
$wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';

$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml"  ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml"            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml"   ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml"              ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/xl/sharedStrings.xml"       ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';

// ── ASSEMBLE ZIP IN MEMORY ────────────────────────────────────────────────
$tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_').'.xlsx';

$zip = new ZipArchive();
if($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
    die("Cannot create xlsx file");

$zip->addFromString('[Content_Types].xml',              $contentTypes);
$zip->addFromString('_rels/.rels',                      $rootRels);
$zip->addFromString('xl/workbook.xml',                  $wbXml);
$zip->addFromString('xl/_rels/workbook.xml.rels',       $wbRels);
$zip->addFromString('xl/styles.xml',                    $stylesXml);
$zip->addFromString('xl/sharedStrings.xml',             $ssXml);
$zip->addFromString('xl/worksheets/sheet1.xml',         $sheetXml);
$zip->close();

// ── SEND TO BROWSER ───────────────────────────────────────────────────────
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.filesize($tmpFile));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tmpFile);
@unlink($tmpFile);
exit();
?>