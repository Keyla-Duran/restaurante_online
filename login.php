<?php
require_once 'config/conexion.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT u.id,u.username,u.email,u.password,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: dashboard.php'); exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Iniciar sesión</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="css/estilos.css" rel="stylesheet"></head>
<body class="login-bg"><div class="container"><div class="row justify-content-center align-items-center min-vh-100"><div class="col-md-5 col-lg-4"><div class="card shadow-lg border-0 p-4"><h2 class="text-center mb-1">Restaurante Online</h2><p class="text-center text-muted mb-4">Inicia sesión para continuar</p>
<?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<form method="post"><label class="form-label">Correo electrónico</label><input class="form-control mb-3" type="email" name="email" required><label class="form-label">Contraseña</label><input class="form-control mb-4" type="password" name="password" required><button class="btn btn-primary w-100">Ingresar</button></form>
<hr><small class="text-muted">Demo: admin@restaurante.com / password</small></div></div></div></div><script src="js/app.js"></script></body></html>
