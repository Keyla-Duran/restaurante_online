<?php
$host = 'localhost';
$db   = 'restaurante_online';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión con la base de datos. Verifica XAMPP y database.sql.");
}
if (session_status() === PHP_SESSION_NONE) session_start();

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function logged() { return isset($_SESSION['user']); }
function require_login() {
    if (!logged()) { header('Location: login.php'); exit; }
}
function role_is(...$roles) {
    return logged() && in_array($_SESSION['user']['role'], $roles, true);
}
function require_role(...$roles) {
    require_login();
    if (!role_is(...$roles)) { http_response_code(403); exit('No tienes permiso para acceder a esta sección.'); }
}
