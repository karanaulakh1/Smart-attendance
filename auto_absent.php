<?php
/**
 * auto_absent.php
 * Called by cron-job.org every night at 23:59 IST
 * URL: https://yoursite.onrender.com/auto_absent.php?key=smartattend2026
 *
 * Add ?force=1 to bypass the lock and re-run (for testing)
 * e.g. ?key=smartattend2026&force=1
 */

// ── SECRET KEY ──────────────────────────────────────────
$secret = "smartattend2026";

if(!isset($_GET['key']) || $_GET['key'] !== $secret){
    http_response_code(403);
    echo "Forbidden";
    exit();
}
// ────────────────────────────────────────────────────────

include("database.php");
date_default_timezone_set("Asia/Kolkata");

$today      = date("Y-m-d");
$time_now   = date("H:i:s");
$force      = isset($_GET['force']) && $_GET['force'] == '1';
$log        = [];
$errors     = [];

// ── CHECK LOCK (skip if ?force=1) ───────────────────────
if(!$force){
    $check = $conn->query("SELECT id FROM auto_absent_log WHERE date='$today'");
    if($check && $check->num_rows > 0){
        echo "Already ran today ($today). Use ?force=1 to re-run.\n";
        exit();
    }
}
// ────────────────────────────────────────────────────────

// ── CHECK groups_registry EXISTS ────────────────────────
$tbl_check = $conn->query("SHOW TABLES LIKE 'groups_registry'");
if(!$tbl_check || $tbl_check->num_rows === 0){
    die("ERROR: groups_registry table not found. Run the SQL setup first.\n");
}
// ────────────────────────────────────────────────────────

// ── LOAD ALL GROUPS ──────────────────────────────────────
$groups_result = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
if(!$groups_result){
    die("ERROR loading groups: " . $conn->error . "\n");
}

$groups = [];
while($g = $groups_result->fetch_assoc()){
    $groups[] = $g;
}

if(count($groups) === 0){
    die("ERROR: No groups found in groups_registry.\n");
}

$log[] = "Found " . count($groups) . " group(s): " .
         implode(", ", array_column($groups, 'group_name'));
// ────────────────────────────────────────────────────────

$total_marked = 0;

foreach($groups as $group){

    $mt         = $group['table_name'];
    $at         = $group['attendance_table'];
    $gname      = $group['group_name'];

    // ── CHECK TABLES EXIST ───────────────────────────────
    $mt_check = $conn->query("SHOW TABLES LIKE '$mt'");
    $at_check = $conn->query("SHOW TABLES LIKE '$at'");

    if(!$mt_check || $mt_check->num_rows === 0){
        $errors[] = "[$gname] SKIP — member table '$mt' not found";
        continue;
    }
    if(!$at_check || $at_check->num_rows === 0){
        $errors[] = "[$gname] SKIP — attendance table '$at' not found";
        continue;
    }

    // ── COUNT TOTAL MEMBERS ──────────────────────────────
    $total_q = $conn->query("SELECT COUNT(*) as c FROM `$mt`");
    $total_members = $total_q ? (int)$total_q->fetch_assoc()['c'] : 0;

    if($total_members === 0){
        $log[] = "[$gname] No members — skipped";
        continue;
    }

    // ── CHECK IN/OUT COLUMNS ─────────────────────────────
    $col_check = $conn->query("SHOW COLUMNS FROM `$at` LIKE 'in_time'");
    $has_inout = ($col_check && $col_check->num_rows > 0);

    // ── GET MEMBERS WHO ALREADY HAVE A RECORD TODAY ──────
    // Using LEFT JOIN instead of NOT IN to be TiDB-safe
    if($has_inout){
        $sql = "
            INSERT INTO `$at`
                (student_id, course, status, date, time, in_time, out_time, working_hours)
            SELECT
                m.student_id,
                m.course,
                'Absent',
                '$today',
                '$time_now',
                NULL,
                NULL,
                '00:00'
            FROM `$mt` m
            LEFT JOIN `$at` a
                ON a.student_id = m.student_id
                AND a.date = '$today'
            WHERE a.id IS NULL
        ";
    } else {
        $sql = "
            INSERT INTO `$at`
                (student_id, course, status, date, time)
            SELECT
                m.student_id,
                m.course,
                'Absent',
                '$today',
                '$time_now'
            FROM `$mt` m
            LEFT JOIN `$at` a
                ON a.student_id = m.student_id
                AND a.date = '$today'
            WHERE a.id IS NULL
        ";
    }

    $result = $conn->query($sql);

    if(!$result){
        $errors[] = "[$gname] SQL ERROR: " . $conn->error;
    } else {
        $marked = $conn->affected_rows;
        $total_marked += $marked;
        $already = $total_members - $marked;
        $log[] = "[$gname] $marked absent" .
                 ($already > 0 ? ", $already already had record" : "") .
                 " (total members: $total_members)" .
                 ($has_inout ? " [IN/OUT]" : "");
    }
}
// ────────────────────────────────────────────────────────

// ── WRITE LOCK ───────────────────────────────────────────
// Delete old lock first if force=1
if($force){
    $conn->query("DELETE FROM auto_absent_log WHERE date='$today'");
}
$conn->query("INSERT INTO auto_absent_log (date) VALUES ('$today')");
// ────────────────────────────────────────────────────────

// ── OUTPUT ───────────────────────────────────────────────
echo "=== AUTO ABSENT REPORT ===\n";
echo "Date    : $today\n";
echo "Time    : $time_now\n";
echo "Forced  : " . ($force ? "YES" : "NO") . "\n";
echo "Marked  : $total_marked member(s) absent\n";
echo "\n--- GROUPS ---\n";
foreach($log as $line)    echo $line . "\n";
if(count($errors) > 0){
    echo "\n--- ERRORS ---\n";
    foreach($errors as $e) echo $e . "\n";
}
echo "\nDone.\n";
?>