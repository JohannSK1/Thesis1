<?php
// logout.php
session_start();          // ensure the session is active
session_unset();          // clear all session variables
session_destroy();        // destroy the session

header("Location: index.php");   
exit();
?>