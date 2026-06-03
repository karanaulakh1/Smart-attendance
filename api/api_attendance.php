<?php
/**
 * mark_attendance.php
 * ESP32 endpoint — IN/OUT tracking for all groups
 *
 * 1st scan = IN  (status = Present, in_time = now)
 * 2nd scan = OUT (out_time = now, working_hours = out - in)
 * 3rd+ scan = ignored
 *
 * URL: https://yoursite.onrender.com/mark_attendance.php?fingerprint_id=1&key=smartattend2026
 */

include("../database.php");
date_default_timezone_set("Asia/Kolkata");

// ── SECRET KEY ──────────────────────────────────────────────────────────
$secret = "smartattend2026";
if(!isset($_GET['key']) || $_GET['key'] !== $secret){
    http_response_code(403);
    echo "Forbidden";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

if(!isset($_GET['fingerprint_id'])){
    echo "No Fingerprint ID";
    exit();
}

$fingerprint_id = $_GET['fingerprint_id'];
$current_time   = date("H:i");
$date           = date("Y-m-d");
$time_now       = date("h:i A");
$time_24        = date("H:i:s");
$now_dt         = date("Y-m-d H:i:s");

// ── UPDATE ESP32 LAST PING ──────────────────────────────────────────────
mysqli_query($conn,
    "UPDATE esp32_status SET last_ping='$now_dt' WHERE id=1"
);
// ───────────────────────────────────────────────────────────────────────

// ── ATTENDANCE WINDOW ───────────────────────────────────────────────────
$start_time = "09:15";
$end_time   = "18:30"; // extended to allow OUT scans in evening
$late_after = "10:30";

if($current_time < $start_time){
    echo "Attendance Not Started";
    exit();
}

// ── AUTO-ABSENT after window ─────────────────────────────────────────────
if($current_time > $end_time){

    $already_ran = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM auto_absent_log WHERE date='$date'"
    ));

    if(!$already_ran){
        $groups = mysqli_query($conn, "SELECT * FROM groups_registry");
        while($group = mysqli_fetch_assoc($groups)){
            $mt = $group['table_name'];
            $at = $group['attendance_table'];
            $members = mysqli_query($conn,
                "SELECT student_id, course FROM `$mt`
                 WHERE student_id NOT IN (
                     SELECT student_id FROM `$at` WHERE date='$date'
                 )"
            );
            while($m = mysqli_fetch_assoc($members)){
                mysqli_query($conn,
                    "INSERT INTO `$at`
                     (student_id, course, status, date, time, in_time, out_time, working_hours)
                     VALUES ('{$m['student_id']}','{$m['course']}','Absent','$date','$time_now',NULL,NULL,'00:00')"
                );
            }
        }
        mysqli_query($conn,
            "INSERT INTO auto_absent_log (date) VALUES ('$date')"
        );
    }

    echo "Attendance Closed";
    exit();
}
// ────────────────────────────────────────────────────────────────────────

// ── FIND MEMBER ACROSS ALL GROUPS ────────────────────────────────────────
$groups = mysqli_query($conn, "SELECT * FROM groups_registry");
$found_member = null;
$found_att    = null;
$found_group  = null;

while($group = mysqli_fetch_assoc($groups)){
    $mt = $group['table_name'];
    $at = $group['attendance_table'];

    $member = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM `$mt` WHERE fingerprint_id='$fingerprint_id'"
    ));

    if($member){
        $found_member = $member;
        $found_att    = $at;
        $found_group  = $group['group_name'];
        break;
    }
}

if(!$found_member){
    echo "Student Not Found";
    exit();
}
// ────────────────────────────────────────────────────────────────────────

$student_id = $found_member['student_id'];
$course     = $found_member['course'];

// ── CHECK EXISTING RECORD FOR TODAY ──────────────────────────────────────
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM `$found_att`
     WHERE student_id='$student_id' AND date='$date'"
));
// ────────────────────────────────────────────────────────────────────────

// ── NO RECORD → FIRST SCAN = IN ─────────────────────────────────────────
if(!$existing){
    $status = ($current_time <= $late_after) ? "Present" : "Late";

    mysqli_query($conn,
        "INSERT INTO `$found_att`
         (student_id, course, status, date, time, in_time, out_time, working_hours)
         VALUES ('$student_id','$course','$status','$date','$time_now','$time_now',NULL,NULL)"
    );

    echo ($status === "Late") ? "Marked Late — IN: $time_now" : "IN: $time_now";
    exit();
}
// ────────────────────────────────────────────────────────────────────────

// ── HAS IN BUT NO OUT → SECOND SCAN = OUT ───────────────────────────────
if($existing['in_time'] && !$existing['out_time']){

    // Calculate working hours
    $in_obj  = DateTime::createFromFormat("h:i A", $existing['in_time']);
    $out_obj = new DateTime();
    $diff    = $in_obj->diff($out_obj);
    $working = sprintf("%02d:%02d", $diff->h + ($diff->days * 24), $diff->i);

    $record_id = $existing['id'];
    mysqli_query($conn,
        "UPDATE `$found_att`
         SET out_time='$time_now', working_hours='$working'
         WHERE id='$record_id'"
    );

    echo "OUT: $time_now — Hours: $working";
    exit();
}
// ────────────────────────────────────────────────────────────────────────

// ── ALREADY HAS BOTH IN AND OUT ──────────────────────────────────────────
echo "Already Marked — IN: {$existing['in_time']} OUT: {$existing['out_time']}";
// ────────────────────────────────────────────────────────────────────────
?>