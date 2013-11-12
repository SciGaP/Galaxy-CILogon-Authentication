<?php
  session_start();
  if(isset($_SESSION['GalaxyUser'])) {
    setrawcookie("GalaxyUser", $_SESSION['GalaxyUser']);
    virtual('/galaxy/');
  } else {
    header('Location: http://ncgas.org');
  }
?>
