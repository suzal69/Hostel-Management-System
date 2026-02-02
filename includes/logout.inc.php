<?php

  // Start session only if none is active
  if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  } else {
    if (session_id() === '') {
      session_start();
    }
  }

  session_unset();
  session_destroy();
  header("Location: ../index.php");

?>
