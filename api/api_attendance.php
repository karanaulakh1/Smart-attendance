<?php
/**
 * mark_attendance.php
 * ESP32 endpoint — handles fingerprint scan for all groups
 * Also updates ESP32 last_ping on every request
 *
 * URL: https://yoursite.onrender.com/mark_attendance.php?fingerprint_id=1&key=smartattend2026
 */

include("database.php");

date_default_timezone_set("Asia/Kolkata");

// ── SECRET KEY ─────────────────────────────────────────────────────────
$secret = "smartattend2026";
if(!isset($_GET['key']) || $_GET['key'] !== $secret){
    http_response_code(403);
    echo "Forbidden";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

if(!isset($_GET['fingerprint_id'])){
    echo "No fingerprint ID provided";
    exit();
}

$fingerprint_id = $_GET['fingerprint_id'];
$current_time   = date("H:i");
$date           = date("Y-m-d");
$time           = date("h:i A");
$now_dt         = date("Y-m-d H:i:s");

// ── UPDATE ESP32 LAST PING (heartbeat on every scan) ───────────────────
mysqli_query($conn,
    "UPDATE esp32_status SET last_ping='$now_dt' WHERE id=1"
);
// ───────────────────────────────────────────────────────────────────────

// ── ATTENDANCE WINDOW ──────────────────────────────────────────────────
$start_time = "09:15";
$end_time   = "10:10";
$late_after = "09:30"; // scans after this time = Late

if($current_time < $start_time){
    echo "Attendance Not Started";
    exit();
}

if($current_time > $end_time){

    // ── AUTO-ABSENT for ALL GROUPS when window closes ──────────────────
    $already_ran = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM auto_absent_log WHERE date='$date'"
    ));

    if(!$already_ran){

        // Load all groups from registry
        $groups = mysqli_query($conn, "SELECT * FROM groups_registry");

        while($group = mysqli_fetch_assoc($groups)){
            $member_table = $group['table_name'];
            $att_table    = $group['attendance_table'];

            // Get all members with no attendance today for this group
            $members = mysqli_query($conn,
                "SELECT student_id, course FROM `$member_table`
                 WHERE student_id NOT IN (
                     SELECT student_id FROM `$att_table` WHERE date='$date'
                 )"
            );

            while($m = mysqli_fetch_assoc($members)){
                $sid    = $m['student_id'];
                $course = $m['course'];
                mysqli_query($conn,
                    "INSERT INTO `$att_table` (student_id, course, status, date, time)
                     VALUES ('$sid','$course','Absent','$date','$time')"
                );
            }
        }

        // Write lock
        mysqli_query($conn,
            "INSERT INTO auto_absent_log (date) VALUES ('$date')"
        );
    }

    echo "Attendance Closed";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

// ── WINDOW IS OPEN — FIND MEMBER ACROSS ALL GROUPS ─────────────────────
$groups = mysqli_query($conn, "SELECT * FROM groups_registry");
$found_member  = null;
$found_table   = null;
$found_att     = null;
$found_group   = null;

while($group = mysqli_fetch_assoc($groups)){
    $mt = $group['table_name'];
    $at = $group['attendance_table'];

    $member = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM `$mt` WHERE fingerprint_id='$fingerprint_id'"
    ));

    if($member){
        $found_member = $member;
        $found_table  = $mt;
        $found_att    = $at;
        $found_group  = $group['group_name'];
        break;
    }
}

if(!$found_member){
    echo "Student Not Found";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

// ── CHECK ALREADY MARKED ───────────────────────────────────────────────
$student_id = $found_member['student_id'];
$course     = $found_member['course'];

$check = mysqli_query($conn,
    "SELECT id FROM `$found_att`
     WHERE student_id='$student_id' AND date='$date'"
);

if(mysqli_num_rows($check) > 0){
    echo "Already Marked";
    exit();
}
// ───────────────────────────────────────────────────────────────────────

// ── INSERT ATTENDANCE ──────────────────────────────────────────────────
$status = ($current_time <= $late_after) ? "Present" : "Late";

mysqli_query($conn,
    "INSERT INTO `$found_att` (student_id, course, status, date, time)
     VALUES ('$student_id','$course','$status','$date','$time')"
);

if($status === "Late"){
    echo "Marked Late";
} else {
    echo "Attendance Marked";
}
// ───────────────────────────────────────────────────────────────────────
?>