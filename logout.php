<?php
/**
 * logout.php — Hapus session & redirect ke login
 * EvenTech Platform
 */

session_start();
session_unset();
session_destroy();

header('Location: index.php');
exit;
