<?php
require_once 'config/conexion.php';
header('Location: '.(logged() ? 'dashboard.php' : 'login.php'));
exit;
