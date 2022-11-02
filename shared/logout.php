<?php
    session_start();
    if(isset($_POST['logout']))
    {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    if(empty($_SESSION['csrf-token'])) $_SESSION['csrf-token'] = bin2hex(random_bytes(32));
?>