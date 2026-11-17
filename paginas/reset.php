<?php

//arquivo temporário

session_start();

session_destroy();

header("location:login.php");
exit();

?>