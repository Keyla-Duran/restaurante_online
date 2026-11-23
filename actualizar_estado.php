<?php
require_once 'config/conexion.php'; require_role('Administrator','Chef','Waiter');
if($_SERVER['REQUEST_METHOD']==='POST'){
 $id=(int)($_POST['id']??0);$status=$_POST['status']??'';
 $allowed=role_is('Waiter')?['Served']:['Pending','Preparing','Ready to serve','Served'];
 if(in_array($status,$allowed,true)){
  $s=$pdo->prepare("UPDATE orders SET status=? WHERE id=?");$s->execute([$status,$id]);
 }
}
header('Location: pedidos.php');exit;
