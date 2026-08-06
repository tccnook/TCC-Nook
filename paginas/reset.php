<?php

//arquivo temporário

session_start();

session_destroy();

header("location:cadastro.php");
exit();

?>