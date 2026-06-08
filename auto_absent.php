<?php
/**
 * auto_absent.php
 * Called by cron-job.org every night at 23:59 IST
 * Works with TiDB + Render
 * Marks absent for ALL groups (students, teachers, employees, custom)
 * Includes in_time, out_time, working_hours columns
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

$today = date("Y-m-d");
$time  = "23:59:00";

// ── CHECK LOCK ──────────────────────────────────────────
$check = mysqli_query($conn,
    "SELECT * FROM auto_absent_log WHERE date='$today'"
);

if($check && mysqli_num_rows($check) > 0){
    echo "Already ran today ($today). No action taken.";
    exit();
}
// ────────────────────────────────────────────────────────

// ── LOAD ALL GROUPS ─────────────────────────────────────
$groups = mysqli_query($conn, "SELECT * FROM groups_registry ORDER BY id ASC");

if(!$groups){
    echo "Error loading groups: " . mysqli_error($conn);
    exit();
}
// ────────────────────────────────────────────────────────

$total_marked = 0;
$log          = [];

while($group = mysqli_fetch_assoc($groups)){

    $member_table = $group['table_name'];
    $att_table    = $group['attendance_table'];
    $group_name   = $group['group_name'];

    // Check if this attendance table has in_time/out_time columns
    $col_check = mysqli_query($conn,
        "SHOW COLUMNS FROM `$att_table` LIKE 'in_time'"
    );
    $has_inout = ($col_check && mysqli_num_rows($col_check) > 0);

    // Build insert based on whether columns exist
    if($has_inout){
        $result = mysqli_query($conn, "
            INSERT INTO `$att_table`
                (student_id, course, status, date, time, in_time, out_time, working_hours)
            SELECT
                m.student_id,
                m.course,
                'Absent',
                '$today',
                '$time',
                NULL,
                NULL,
                '00:00'
            FROM `$member_table` m
            WHERE m.student_id NOT IN (
                SELECT a.student_id
                FROM `$att_table` a
                WHERE a.date = '$today'
            )
        ");
    } else {
        $result = mysqli_query($conn, "
            INSERT INTO `$att_table`
                (student_id, course, status, date, time)
            SELECT
                m.student_id,
                m.course,
                'Absent',
                '$today',
                '$time'
            FROM `$member_table` m
            WHERE m.student_id NOT IN (
                SELECT a.student_id
                FROM `$att_table` a
                WHERE a.date = '$today'
            )
        ");
    }

    if(!$result){
        $log[] = "[$group_name] ERROR: " . mysqli_error($conn);
    } else {
        $count = mysqli_affected_rows($conn);
        $total_marked += $count;
        $log[] = "[$group_name] $count member(s) marked Absent.";
    }
}

// ── WRITE LOCK ──────────────────────────────────────────
mysqli_query($conn,
    "INSERT INTO auto_absent_log (date) VALUES ('$today')"
);
// ────────────────────────────────────────────────────────

// ── OUTPUT ──────────────────────────────────────────────
echo "Auto-absent done for $today.\n";
echo "Total marked: $total_marked\n\n";
foreach($log as $line){
    echo $line . "\n";
}
?>