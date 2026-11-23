<?php
require_once 'config/conexion.php'; require_role('Administrator');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $name=trim($_POST['username']??'');$email=trim($_POST['email']??'');$password=$_POST['password']??'';$role=(int)($_POST['role_id']??4);
 if($name && filter_var($email,FILTER_VALIDATE_EMAIL) && strlen($password)>=6 && $role>=1 && $role<=4){
  try{$s=$pdo->prepare("INSERT INTO users(username,email,password,role_id) VALUES(?,?,?,?)");$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);$msg='Usuario creado correctamente.';}
  catch(PDOException $e){$msg='No se pudo crear. Verifica que el correo no esté registrado.';}
 }else{$msg='Completa los campos. La contraseña debe tener al menos 6 caracteres.';}
}
$users=$pdo->query("SELECT u.id,u.username,u.email,r.name role FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id")->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="css/estilos.css" rel="stylesheet"></head><body><div class="container py-4"><a href="dashboard.php">← Panel</a><h1 class="my-3">Usuarios</h1><?php if($msg):?><div class="alert alert-info"><?=h($msg)?></div><?php endif;?><div class="card p-3 mb-4"><h5>Crear usuario</h5><form method="post" class="row g-2"><div class="col-md-3"><input class="form-control" name="username" placeholder="Nombre" required></div><div class="col-md-3"><input class="form-control" type="email" name="email" placeholder="Correo" required></div><div class="col-md-2"><input class="form-control" type="password" name="password" placeholder="Contraseña" required></div><div class="col-md-2"><select class="form-select" name="role_id"><option value="1">Administrador</option><option value="2">Chef</option><option value="3">Camarero</option><option value="4" selected>Cliente</option></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Crear</button></div></form></div><table class="table table-striped"><thead><tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?=$u['id']?></td><td><?=h($u['username'])?></td><td><?=h($u['email'])?></td><td><?=h($u['role'])?></td></tr><?php endforeach;?></tbody></table></div><script src="js/app.js"></script></body></html>
