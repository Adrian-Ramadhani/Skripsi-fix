<?php
// Include config file
require_once "config.php";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare a delete statement
    $sql = "DELETE FROM data_absen";
    
    if (mysqli_query($link, $sql)) {
        // If the deletion was successful, redirect to the data_absen-index.php page
        header("location: data_absen-index.php");
        exit();
    } else {
        echo "ERROR: Could not execute $sql. " . mysqli_error($link);
    }

    // Close connection
    mysqli_close($link);
} else {
    // If the request method is not POST, redirect to the data_absen-index.php page
    header("location: data_absen-index.php");
    exit();
}
?>
