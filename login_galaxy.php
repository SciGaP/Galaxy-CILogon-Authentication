<?php
  session_start();
  setrawcookie("GalaxyUser", $_SESSION['GalaxyUser']);
  virtual('/galaxy/');
?>
