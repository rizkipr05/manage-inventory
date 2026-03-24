<?php
session_start();
session_unset();
session_destroy();
header('Location: /manage-medical/auth/login.php');
exit;
