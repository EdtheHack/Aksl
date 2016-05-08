<?php
//Create a randomised id for an object
//TODO fix the prepare statement
function gen_id ($table, $idRow, $db_con) {
    $id = rand(0, 999999999);
    $stmt = $db_con->prepare("SELECT * FROM $table WHERE $idRow = '$id';");
    $stmt->execute();
    //If ID already exists, create a new one
    if ($stmt->rowCount() > 0) {
        gen_id($table, $idRow, $db_con);
    }
    return $id;
}
?>