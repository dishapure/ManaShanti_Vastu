<?php
session_start(); // Start the session

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page or home
header("Location: index.html");
exit();
?>
