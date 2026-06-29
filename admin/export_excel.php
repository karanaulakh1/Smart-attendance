<?php
/**
 * export_excel.php
 * Pure PHP .xlsx writer — no ZipArchive, no Python, no extensions
 * Works on ANY PHP host including Render free tier
 * Only requires: gzdeflate() — available everywhere
 */

session_start();
include '../database.php';
if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit(); }

date_default_timezone_set("Asia/Kolkata");

// ════════════════════════════════════════════════════════
// PURE PHP ZIP BUILDER (no ZipArchive needed)
// ════════════════════════════════════════════════════════

function zipEntry($name, $data){
    $nb   = $name;
    $nlen = strlen($nb);
    $crc  = crc32($data);
    $zd   = gzdeflate($data, 6);
    $clen = strlen($zd);
    $ulen = strlen($data);
    $lf   = pack('VvvvvvVVVvv',
                0x04034b50, 20, 0, 8, 0, 0,
                $crc, $clen, $ulen, $nlen, 0)
            . $nb . $zd;
    return [
        'lf'  => $lf,
        'crc' => $crc, 'clen' => $clen, 'ulen' => $ulen,
        'nb'  => $nb,  'nlen' => $nlen,
    ];
}

function buildXlsx($files){
    $zip = ''; $cd = '';
    foreach($files as $name => $content){
        $off = strlen($zip);
        $e   = zipEntry($name, $content);
        $zip .= $e['lf'];
        $cd  .= pack('VvvvvvvVVVvvvvvVV',
                    0x02014b50, 20, 20, 0, 8, 0, 0,
                    $e['crc'], $e['clen'], $e['ulen'],
                    $e['nlen'], 0, 0, 0, 0, 0, $off)
                . $e['nb'];
    }
    $coff = strlen($zip);
    $csz  = strlen($cd);
    $cnt  = count($files);
    $eocd = pack('VvvvvVVv',
                0x06054b50, 0, 0, $cnt, $cnt, $csz, $coff, 0);
    return $zip . $cd . $eocd;
}

// ════════════════════════════════════════════════════════
// XML / CELL HELPERS
// ════════════════════════════════════════════════════════

function xe($s){ return htmlspecialchars((string)$s, ENT_XML1, 'UTF-8'); }

function colLetter($n){
    $s = '';
    while($n > 0){ $n--; $s = chr(65+($n%26)).$s; $n = (int)($n/26); }
    return $s;
}

$_strings = []; $_strIdx = [];
function si($v){
    global $_strings, $_strIdx;
    $v = (string)$v;
    if(!isset($_strIdx[$v])){ $_strIdx[$v] = count($_strings); $_strings[] = $v; }
    return $_strIdx[$v];
}

// Build one <row> element
// $cells = [ [colNum, value, styleIdx, isNumber?], ... ]
function mkRow($rn, $cells){
    $r = '<row r="'.$rn.'">';
    foreach($cells as $c){
        $col = $c[0]; $val = $c[1]; $st = $c[2]; $num = isset($c[3]) ? $c[3] : false;
        $ref = colLetter($col).$rn;
        if($num){
            $r .= '<c r="'.$ref.'" s="'.$st.'"><v>'.xe($val).'</v></c>';
        } else {
            $idx = si($val);
            $r .= '<c r="'.$ref.'" t="s" s="'.$st.'"><v>'.$idx.'</v></c>';
        }
    }
    return $r.'</row>';
}

// Fill a whole row with one style (for title/summary spanning all cols)
function fillRow($rn, $first_val, $first_style, $rest_style, $ncols){
    $cells = [[1, $first_val, $first_style, false]];
    for($c=2;$c<=$ncols;$c++) $cells[] = [$c,'', $rest_style, false];
    return mkRow($rn, $cells);
}

// ════════════════════════════════════════════════════════
// PARAMS & QUERY
// ════════════════════════════════════════════════════════

$group      = isset($_GET['group'])      ? $_GET['group']      : 'students';
$att_table  = isset($_GET['att_table'])  ? $_GET['att_table']  : 'attendance';
$course     = isset($_GET['course'])     ? $_GET['course']     : '';
$from_date  = isset($_GET['from_date'])  ? $_GET['from_date']  : '';
$to_date    = isset($_GET['to_date'])    ? $_GET['to_date']    : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

$vq = $conn->query("SELECT * FROM groups_registry
    WHERE table_name='$group' AND attendance_table='$att_table'");
if(!$vq || $vq->num_rows===0) die("Invalid group.");
$gi = $vq->fetch_assoc();
$group_name = $gi['group_name'];
$mt = $group;

$conds = [];
if($course)     $conds[] = "m.course='$course'";
if($from_date)  $conds[] = "a.date>='$from_date'";
if($to_date)    $conds[] = "a.date<='$to_date'";
if($student_id) $conds[] = "a.student_id='$student_id'";
$where = $conds ? "WHERE ".implode(" AND ",$conds) : "";

$cc = $conn->query("SHOW COLUMNS FROM `$att_table` LIKE 'in_time'");
$has_inout = ($cc && $cc->num_rows > 0);

$sql = "SELECT a.student_id, m.name, m.department, m.course, m.year,
               a.date, DAYNAME(a.date) as dn, a.status,
               ".($has_inout
                 ? "a.in_time, a.out_time, a.working_hours"
                 : "NULL as in_time, NULL as out_time, NULL as working_hours")."
        FROM `$att_table` a
        LEFT JOIN `$mt` m ON a.student_id=m.student_id
        $where ORDER BY a.date DESC, m.name ASC";

$res = $conn->query($sql);
if(!$res) die("Query error: ".$conn->error);

$rows=[]; $tp=$ta=$tl=0; $tsec=$hcnt=0;
while($r=$res->fetch_assoc()){
    if($r['status']==='Present') $tp++;
    elseif($r['status']==='Absent') $ta++;
    elseif($r['status']==='Late') $tl++;
    if(!empty($r['working_hours']) && $r['working_hours']!=='00:00'){
        $p=explode(':',$r['working_hours']);
        if(count($p)===2){ $tsec+=(int)$p[0]*3600+(int)$p[1]*60; $hcnt++; }
    }
    $rows[]=$r;
}
$td   = $tp+$ta+$tl;
$rate = $td>0 ? round($tp/$td*100).'%' : '0%';
$th   = $hcnt>0 ? sprintf("%02d:%02d",floor($tsec/3600),floor(($tsec%3600)/60)) : 'N/A';
$ah   = $hcnt>0 ? sprintf("%02d:%02d",floor(($tsec/$hcnt)/3600),floor((($tsec/$hcnt)%3600)/60)) : 'N/A';

// ════════════════════════════════════════════════════════
// BUILD WORKSHEET XML
// ════════════════════════════════════════════════════════

// Style indexes (match cellXfs in styles.xml below):
// 0=default  1=title  2=meta-label  3=meta-val
// 4=col-hdr  5=data   6=present     7=absent
// 8=late     9=sum-hdr 10=sum-val   11=alt-row

$NC  = 12;  // number of columns
$xml = '';
$ROW = 1;

// Row 1 — Title
$xml .= fillRow($ROW++, 'SMART ATTENDANCE SYSTEM — Export Report', 1, 1, $NC);

// Rows 2-7 — Meta
$meta = [
    ['Generated On',  date("d M Y, h:i A")],
    ['Group',         $group_name],
    ['Course / Dept', $course ?: 'All'],
    ['Member ID',     $student_id ?: 'All'],
    ['Date Range',    ($from_date?:'All').' to '.($to_date?:'All')],
    ['Total Records', (string)count($rows)],
];
foreach($meta as $m){
    $cells = [[1,$m[0],2,false],[2,$m[1],3,false]];
    for($c=3;$c<=$NC;$c++) $cells[]=[$c,'',3,false];
    $xml .= mkRow($ROW++, $cells);
}

// Row 8 — Blank
$cells=[];
for($c=1;$c<=$NC;$c++) $cells[]=[$c,'',0,false];
$xml .= mkRow($ROW++, $cells);

// Row 9 — Column headers
$hdrs=['#','Member ID','Name','Department','Course','Year',
       'Date','Day','Status','In Time','Out Time','Working Hours'];
$cells=[];
foreach($hdrs as $i=>$h) $cells[]=[$i+1,$h,4,false];
$xml .= mkRow($ROW++, $cells);

$dataStartRow = $ROW;

// Data rows
foreach($rows as $i=>$r){
    $s=$r['status'];
    $st = $s==='Present'?6 : ($s==='Absent'?7 : ($s==='Late'?8 : ($i%2===0?11:5)));
    $wh = (!empty($r['working_hours']) && $r['working_hours']!=='00:00') ? $r['working_hours'] : 'N/A';
    $dt = !empty($r['date']) ? date("d M Y", strtotime($r['date'])) : '';
    $cells=[
        [1,  $i+1,                         $st, true],
        [2,  $r['student_id']  ?? '',       $st, false],
        [3,  $r['name']        ?? '',       $st, false],
        [4,  $r['department']  ?? '',       $st, false],
        [5,  $r['course']      ?? '',       $st, false],
        [6,  $r['year']        ?? '',       $st, false],
        [7,  $dt,                           $st, false],
        [8,  $r['dn']          ?? '',       $st, false],
        [9,  $s,                            $st, false],
        [10, $r['in_time']  ?? 'Not recorded', $st, false],
        [11, $r['out_time'] ?? 'Not recorded', $st, false],
        [12, $wh,                           $st, false],
    ];
    $xml .= mkRow($ROW++, $cells);
}

// Blank row
$cells=[];
for($c=1;$c<=$NC;$c++) $cells[]=[$c,'',0,false];
$xml .= mkRow($ROW++, $cells);

// Summary header
$xml .= fillRow($ROW++, 'ATTENDANCE SUMMARY', 9, 9, $NC);

// Summary data
$sumRows=[
    ['Total Present',         (string)$tp],
    ['Total Absent',          (string)$ta],
    ['Total Late',            (string)$tl],
    ['Total Days',            (string)$td],
    ['Total Working Hours',   $th],
    ['Avg Working Hours/Day', $ah],
    ['Attendance Rate',       $rate],
];
foreach($sumRows as $s){
    $cells=[[1,$s[0],2,false],[2,$s[1],10,false]];
    for($c=3;$c<=$NC;$c++) $cells[]=[$c,'',3,false];
    $xml .= mkRow($ROW++, $cells);
}

// ════════════════════════════════════════════════════════
// BUILD sharedStrings.xml
// ════════════════════════════════════════════════════════

$ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
       . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
       . ' count="'.count($_strings).'" uniqueCount="'.count($_strings).'">'."\n";
foreach($_strings as $s) $ssXml .= '<si><t xml:space="preserve">'.xe($s).'</t></si>'."\n";
$ssXml .= '</sst>';

// ════════════════════════════════════════════════════════
// BUILD styles.xml
// ════════════════════════════════════════════════════════

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="6">
 <font><sz val="11"/><name val="Arial"/></font>
 <font><sz val="13"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
 <font><sz val="10"/><b/><color rgb="FF1E3A5F"/><name val="Arial"/></font>
 <font><sz val="10"/><color rgb="FF1E3A5F"/><name val="Arial"/></font>
 <font><sz val="10"/><b/><color rgb="FFFFFFFF"/><name val="Arial"/></font>
 <font><sz val="10"/><b/><color rgb="FF1D4ED8"/><name val="Arial"/></font>
</fonts>
<fills count="10">
 <fill><patternFill patternType="none"/></fill>
 <fill><patternFill patternType="gray125"/></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/></patternFill></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FFEEF2FF"/></patternFill></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FF2D6A4F"/></patternFill></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/></patternFill></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FFFFE4E6"/></patternFill></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>
 <fill><patternFill patternType="solid"><fgColor rgb="FFF0F4FF"/></patternFill></fill>
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
 <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
 <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
 <xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>
 <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
 <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center"/></xf>
 <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
 <xf numFmtId="0" fontId="0" fillId="6" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
 <xf numFmtId="0" fontId="0" fillId="7" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
 <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center" vertical="center"/></xf>
 <xf numFmtId="0" fontId="5" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
 <xf numFmtId="0" fontId="0" fillId="9" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>
</cellXfs>
</styleSheet>';

// ════════════════════════════════════════════════════════
// COLUMN WIDTHS & SHEET XML
// ════════════════════════════════════════════════════════

$cw = [5,14,26,22,18,10,14,12,10,12,12,15];
$colsXml = '';
foreach($cw as $i=>$w)
    $colsXml .= '<col min="'.($i+1).'" max="'.($i+1).'" width="'.$w.'" customWidth="1"/>';

$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetViews><sheetView workbookViewId="0">
 <pane ySplit="'.$dataStartRow.'" topLeftCell="A'.($dataStartRow+1).'" activePane="bottomLeft" state="frozen"/>
</sheetView></sheetViews>
<sheetFormatPr defaultRowHeight="18" customHeight="1"/>
<cols>'.$colsXml.'</cols>
<sheetData>'.$xml.'</sheetData>
</worksheet>';

// ════════════════════════════════════════════════════════
// ASSEMBLE .xlsx
// ════════════════════════════════════════════════════════

$files = [
    '[Content_Types].xml' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
 <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
 <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
 <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>',

    '_rels/.rels' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>',

    'xl/workbook.xml' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
 <sheets><sheet name="Attendance" sheetId="1" r:id="rId1"/></sheets>
</workbook>',

    'xl/_rels/workbook.xml.rels' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
 <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
 <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>',

    'xl/styles.xml'              => $stylesXml,
    'xl/sharedStrings.xml'       => $ssXml,
    'xl/worksheets/sheet1.xml'   => $sheetXml,
];

$xlsxData = buildXlsx($files);

// ════════════════════════════════════════════════════════
// SEND TO BROWSER
// ════════════════════════════════════════════════════════

$fparts = ['attendance', $group_name];
if($course)     $fparts[] = $course;
if($student_id) $fparts[] = $student_id;
if($from_date)  $fparts[] = $from_date;
if($to_date)    $fparts[] = 'to_'.$to_date;
$filename = strtolower(str_replace([' ','/','\\'],'_',implode('_',$fparts))).'.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Content-Length: '.strlen($xlsxData));
header('Pragma: no-cache');
header('Expires: 0');
echo $xlsxData;
exit();
?>