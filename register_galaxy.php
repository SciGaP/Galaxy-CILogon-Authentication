<?php
  session_start();
  //setrawcookie("GalaxyUser", $_SESSION['GalaxyUser'], time()-10);
  session_unset();
  session_destroy();
  header('Location: http://ncgas.org/questions.php');
?>
