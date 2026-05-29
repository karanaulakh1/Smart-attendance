<?php

include("../database.php");

$query = mysqli_query($conn,

"SELECT * FROM students
WHERE enroll_request='1'
LIMIT 1");

if(mysqli_num_rows($query)>0){

    $row = mysqli_fetch_assoc($query);

    echo $row['student_id'];

}else{

    echo "0";
}
?>