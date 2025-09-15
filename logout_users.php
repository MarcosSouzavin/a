<?php
session_start();
session_regenerate_id();
header("Location: cliente.php"); 
exit();
?>