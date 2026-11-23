
<?php
require_once __DIR__ . '/config/conexion.php';
require_role('Administrator', 'Chef', 'Waiter', 'Client');

$esCliente = role_is('Client');

if ($esCliente) {
    $stmt = $pdo->prepare("
        SELECT o.id, o.status, o.created_at, u.username
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $pedidos = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("
        SELECT o.id, o.status, o.created_at, u.username
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        ORDER BY o.created_at DESC
    ");
    $pedidos = $stmt->fetchAll();
}

// Obtener los platos de cada pedido
foreach ($pedidos as &$pedido) {
    $stmtDetalles = $pdo->prepare("
        SELECT od.quantity, od.price, d.name
        FROM order_details od
        INNER JOIN dishes d ON d.id = od.dish_id
        WHERE od.order_id = ?
    ");

    $stmtDetalles->execute([$pedido['id']]);
    $pedido['detalles'] = $stmtDetalles->fetchAll();
}
unset($pedido);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gestión de pedidos | Bosques & Brasas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">

    <style>
        body {
            background: #F8F6F0;
        }

        .orders-container {
            background: white;
            border: 1px solid #E8E3D8;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(45,57,40,.06);
        }

        h1 {
            color: #243D30;
            font-family: 'Playfair Display', serif;
        }

        .order-card {
            border: 1px solid #E8E3D8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 18px;
            background: #fff;
        }

        .order-title {
            color: #243D30;
            font-weight: bold;
        }

        .dish-line {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 9px 0;
            border-bottom: 1px solid #eee9df;
        }

        .dish-line:last-child {
            border-bottom: none;
        }

        .order-total {
            color: #795438;
            font-weight: bold;
            text-align: right;
            margin-top: 12px;
        }

        .status {
            display: inline-block;
            background: #E5EAD9;
            color: #243D30;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: bold;
        }

        .btn-save {
            background: #243D30;
            color: white;
            border: none;
        }

        .btn-save:hover {
            background: #71845B;
            color: white;
        }

        .empty-orders {
            text-align: center;
            padding: 45px 20px;
            color: #85877D;
        }
    </style>
</head>

<body>

<div class="container py-4">

    <a href="dashboard.php" class="text-decoration-none">
        ← Panel
    </a>

    <h1 class="my-4">Gestión de pedidos</h1>

    <div class="orders-container">

        <?php if (count($pedidos) > 0): ?>

            <?php foreach ($pedidos as $pedido): ?>

                <?php
                    $total = 0;

                    foreach ($pedido['detalles'] as $detalle) {
                        $total += (float)$detalle['price'] * (int)$detalle['quantity'];
                    }
                ?>

                <article class="order-card">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">

                        <div>
                            <h5 class="order-title mb-1">
                                Pedido #<?= (int)$pedido['id'] ?>
                            </h5>

                            <div class="text-muted small">
                                Cliente: <?= h($pedido['username']) ?>
                            </div>

                            <div class="text-muted small">
                                Fecha: <?= h($pedido['created_at']) ?>
                            </div>
                        </div>

                        <span class="status">
                            <?= h($pedido['status']) ?>
                        </span>

                    </div>

                    <h6 class="mt-3">Platos del pedido</h6>

                    <?php if (count($pedido['detalles']) > 0): ?>

                        <?php foreach ($pedido['detalles'] as $detalle): ?>

                            <div class="dish-line">

                                <span>
                                    <?= h($detalle['name']) ?>
                                    × <?= (int)$detalle['quantity'] ?>
                                </span>

                                <span>
                                    $<?= number_format(
                                        (float)$detalle['price'] * (int)$detalle['quantity'],
                                        2
                                    ) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                        <div class="order-total">
                            Total: $<?= number_format($total, 2) ?>
                        </div>

                    <?php else: ?>

                        <p class="text-muted">
                            Este pedido todavía no tiene platos registrados.
                        </p>

                    <?php endif; ?>

                    <?php if (role_is('Administrator', 'Chef', 'Waiter')): ?>

                        <form
                            method="POST"
                            action="actualizar_estado.php"
                            class="d-flex flex-wrap gap-2 mt-3"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$pedido['id'] ?>"
                            >

                            <select name="status" class="form-select" style="max-width: 220px;">

                                <?php
                                $estados = role_is('Waiter')
                                    ? ['Served']
                                    : ['Pending', 'Preparing', 'Ready to serve', 'Served'];
                                ?>

                                <?php foreach ($estados as $estado): ?>

                                    <option
                                        value="<?= h($estado) ?>"
                                        <?= $pedido['status'] === $estado ? 'selected' : '' ?>
                                    >
                                        <?= h($estado) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button type="submit" class="btn btn-save">
                                Guardar estado
                            </button>

                        </form>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-orders">
                <h4>Todavía no hay pedidos.</h4>
                <p>Cuando los clientes realicen pedidos, aparecerán aquí.</p>
            </div>

        <?php endif; ?>

    </div>

</div>

<script src="js/app.js"></script>

</body>
</html>
