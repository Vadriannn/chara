<?php
require_once '../../auth.php';

if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Kasir') {
    header('location:../../index.php');
    exit;
}
?>