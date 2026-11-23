
<?php
require_once __DIR__ . '/config/conexion.php';
require_role('Client');

// INICIALIZAR CARRITO
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// INICIALIZAR TOKEN DE SEGURIDAD
if (!isset($_SESSION['csrf_carrito'])) {
    $_SESSION['csrf_carrito'] = bin2hex(random_bytes(32));
}

$error = '';

// PROCESAR ACCIONES DEL CARRITO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_carrito'], $token)) {
        die('La sesión del formulario expiró. Regresa al menú e inténtalo nuevamente.');
    }

    $accion = $_POST['accion'] ?? '';

    // AGREGAR PLATO
    if ($accion === 'agregar') {

        $dishId = (int)($_POST['dish_id'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 1);

        if ($dishId > 0 && $cantidad >= 1 && $cantidad <= 99) {

            $stmt = $pdo->prepare("SELECT id FROM dishes WHERE id = ?");
            $stmt->execute([$dishId]);

            if ($stmt->fetch()) {
                $actual = (int)($_SESSION['carrito'][$dishId] ?? 0);
                $_SESSION['carrito'][$dishId] = min(99, $actual + $cantidad);
            }
        }

        header('Location: carrito.php');
        exit;
    }

    // ACTUALIZAR CANTIDADES
    if ($accion === 'actualizar') {

        $cantidades = $_POST['cantidades'] ?? [];

        foreach ($cantidades as $id => $cantidad) {

            $id = (int)$id;
            $cantidad = (int)$cantidad;

            if ($cantidad <= 0) {
                unset($_SESSION['carrito'][$id]);
            } elseif ($cantidad <= 99 && isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id] = $cantidad;
            }
        }

        header('Location: carrito.php');
        exit;
    }

    // ELIMINAR PLATO
    if ($accion === 'eliminar') {

        $dishId = (int)($_POST['dish_id'] ?? 0);

        unset($_SESSION['carrito'][$dishId]);

        header('Location: carrito.php');
        exit;
    }

    // CONFIRMAR PEDIDO
    if ($accion === 'confirmar') {

        if (empty($_SESSION['carrito'])) {

            $error = 'Tu carrito está vacío. Agrega algún plato antes de confirmar.';

        } else {

            try {

                $pdo->beginTransaction();

                $userId = (int)($_SESSION['user']['id'] ?? 0);

                if ($userId <= 0) {
                    throw new Exception('No se pudo identificar al usuario.');
                }

                // OBTENER LOS PLATOS Y SUS PRECIOS ACTUALES
                $ids = array_map('intval', array_keys($_SESSION['carrito']));
                $marcadores = implode(',', array_fill(0, count($ids), '?'));

                $stmt = $pdo->prepare("
                    SELECT id, name, price
                    FROM dishes
                    WHERE id IN ($marcadores)
                ");

                $stmt->execute($ids);
                $platosBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($platosBD) !== count($ids)) {
                    throw new Exception('Uno de los platos ya no está disponible.');
                }

                // CREAR EL PEDIDO
                $stmtPedido = $pdo->prepare("
                    INSERT INTO orders (user_id, dish_id, quantity, status)
                    VALUES (?, NULL, NULL, 'Pending')
                ");

                $stmtPedido->execute([$userId]);

                $orderId = (int)$pdo->lastInsertId();

                // GUARDAR LOS PLATOS DEL PEDIDO
                $stmtDetalle = $pdo->prepare("
                    INSERT INTO order_details
                    (order_id, dish_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($platosBD as $plato) {

                    $dishId = (int)$plato['id'];
                    $cantidad = (int)($_SESSION['carrito'][$dishId] ?? 0);

                    if ($cantidad < 1 || $cantidad > 99) {
                        throw new Exception('Hay una cantidad inválida en el carrito.');
                    }

                    $stmtDetalle->execute([
                        $orderId,
                        $dishId,
                        $cantidad,
                        $plato['price']
                    ]);
                }

                // CONFIRMAR TRANSACCIÓN
                $pdo->commit();

                // VACIAR CARRITO
                $_SESSION['carrito'] = [];

                header('Location: pedidos.php?mensaje=pedido_creado');
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'No se pudo registrar el pedido: ' . $e->getMessage();
            }
        }
    }
}

// CONSULTAR LOS PLATOS DEL CARRITO
$items = [];
$total = 0;

if (!empty($_SESSION['carrito'])) {

    $ids = array_map('intval', array_keys($_SESSION['carrito']));
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT id, name, description, price, image
        FROM dishes
        WHERE id IN ($marcadores)
        ORDER BY name
    ");

    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {

        $item['quantity'] = (int)($_SESSION['carrito'][$item['id']] ?? 0);

        $item['subtotal'] =
            (float)$item['price'] * $item['quantity'];

        $total += $item['subtotal'];
    }

    unset($item);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Mi carrito | Bosques & Brasas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">

    <style>
        body {
            background: #F8F6F0;
            color: #333A32;
        }

        .navbar {
            background: #243D30;
        }

        .navbar-brand {
            color: #F3EBDD !important;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
        }

        .cart-container {
            background: white;
            border: 1px solid #E8E3D8;
            border-radius: 17px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(45,57,40,.06);
        }

        h1, h3 {
            color: #243D30;
            font-family: 'Playfair Display', serif;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 18px 0;
            border-bottom: 1px solid #E8E3D8;
        }

        .cart-item img {
            width: 105px;
            height: 90px;
            object-fit: cover;
            border-radius: 10px;
        }

        .item-info {
            flex: 1;
        }

        .item-info h5 {
            color: #243D30;
            margin-bottom: 5px;
        }

        .item-price {
            color: #795438;
            font-weight: bold;
        }

        .quantity-input {
            width: 75px;
            border: 1px solid #E8E3D8;
            border-radius: 8px;
            padding: 8px;
        }

        .btn-main {
            background: #243D30;
            color: white;
            border: none;
            border-radius: 9px;
            padding: 11px 18px;
        }

        .btn-main:hover {
            background: #71845B;
            color: white;
        }

        .btn-remove {
            background: #795438;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .btn-remove:hover {
            background: #513923;
            color: white;
        }

        .total {
            color: #795438;
            font-size: 1.6rem;
            font-weight: bold;
        }

        .empty-cart {
            text-align: center;
            padding: 50px 20px;
        }

        @media(max-width: 600px) {
            .cart-item {
                align-items: flex-start;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark">
    <div class="container py-2">

        <a href="menu.php" class="navbar-brand">
            Bosques & Brasas
        </a>

        <a href="logout.php" class="btn btn-outline-light btn-sm">
            Salir
        </a>

    </div>
</nav>

<main class="container py-4">

    <a href="menu.php" class="text-decoration-none">
        ← Seguir eligiendo platos
    </a>

    <h1 class="my-4">Mi carrito</h1>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>

    <?php endif; ?>

    <section class="cart-container">

        <?php if (count($items) > 0): ?>

            <form method="POST" action="carrito.php" id="form-actualizar">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION['csrf_carrito']) ?>"
                >

                <input type="hidden" name="accion" value="actualizar">

                <?php foreach ($items as $item): ?>

                    <article class="cart-item">

                        <img
                            src="img/<?= h($item['image'] ?: 'default.jpg') ?>"
                            alt="<?= h($item['name']) ?>"
                            onerror="this.onerror=null;this.src='img/default.jpg';"
                        >

                        <div class="item-info">

                            <h5><?= h($item['name']) ?></h5>

                            <div class="text-muted small">
                                Precio unitario:
                                $<?= number_format((float)$item['price'], 2) ?>
                            </div>

                            <div class="item-price mt-1">
                                Subtotal:
                                $<?= number_format($item['subtotal'], 2) ?>
                            </div>

                        </div>

                        <div>
                            <label class="form-label small">Cantidad</label>

                            <input
                                type="number"
                                class="quantity-input"
                                name="cantidades[<?= (int)$item['id'] ?>]"
                                value="<?= $item['quantity'] ?>"
                                min="1"
                                max="99"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            form="eliminar-<?= (int)$item['id'] ?>"
                            class="btn-remove"
                        >
                            Quitar
                        </button>

                    </article>

                <?php endforeach; ?>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">

                    <button type="submit" class="btn btn-outline-secondary">
                        Actualizar cantidades
                    </button>

                    <div class="total">
                        Total: $<?= number_format($total, 2) ?>
                    </div>

                </div>

            </form>

            <?php foreach ($items as $item): ?>

                <form
                    method="POST"
                    action="carrito.php"
                    id="eliminar-<?= (int)$item['id'] ?>"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h($_SESSION['csrf_carrito']) ?>"
                    >

                    <input type="hidden" name="accion" value="eliminar">

                    <input
                        type="hidden"
                        name="dish_id"
                        value="<?= (int)$item['id'] ?>"
                    >

                </form>

            <?php endforeach; ?>

            <form method="POST" action="carrito.php" class="mt-4">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION['csrf_carrito']) ?>"
                >

                <input type="hidden" name="accion" value="confirmar">

                <button
                    type="submit"
                    class="btn btn-main w-100"
                    onclick="return confirm('¿Deseas confirmar tu pedido?');"
                >
                    Confirmar pedido
                </button>

            </form>

        <?php else: ?>

            <div class="empty-cart">

                <h3>Tu carrito está vacío</h3>

                <p class="text-muted">
                    Todavía no has agregado platos a tu pedido.
                </p>

                <a href="menu.php" class="btn btn-main mt-3">
                    Ver menú
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

<script src="js/app.js"></script>

</body>
</html>
