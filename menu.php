
<?php
require_once __DIR__ . '/config/conexion.php';
require_login();

$rol = $_SESSION['user']['role'] ?? '';
$esAdmin = ($rol === 'Administrator');
$esCliente = ($rol === 'Client');

$mensajeError = '';

// Inicializar carrito y token de seguridad del cliente
if ($esCliente && !isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if ($esCliente && !isset($_SESSION['csrf_carrito'])) {
    $_SESSION['csrf_carrito'] = bin2hex(random_bytes(32));
}

// AGREGAR PLATO (SOLO ADMINISTRADOR)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['accion'] ?? '') === 'agregar' &&
    $esAdmin
) {
    $nombre = trim($_POST['name'] ?? '');
    $descripcion = trim($_POST['description'] ?? '');
    $precio = (float)($_POST['price'] ?? 0);
    $imagen = trim($_POST['image'] ?? 'default.jpg');

    if ($imagen === '') {
        $imagen = 'default.jpg';
    }

    if ($nombre !== '' && $precio > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO dishes (name, description, price, image)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $nombre,
                $descripcion,
                $precio,
                $imagen
            ]);

            header('Location: menu.php?mensaje=agregado');
            exit;

        } catch (PDOException $e) {
            $mensajeError = 'No se pudo agregar el plato. Verifica los datos.';
        }
    } else {
        $mensajeError = 'Escribe un nombre y un precio válido.';
    }
}

// ELIMINAR PLATO (SOLO ADMINISTRADOR)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['accion'] ?? '') === 'eliminar' &&
    $esAdmin
) {
    $id = (int)($_POST['dish_id'] ?? 0);

    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM dishes WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                header('Location: menu.php?mensaje=eliminado');
                exit;
            } else {
                $mensajeError = 'No se encontró el plato que intentas eliminar.';
            }

        } catch (PDOException $e) {
            $mensajeError = 'No se pudo eliminar el plato. Puede estar relacionado con un pedido.';
        }
    } else {
        $mensajeError = 'El identificador del plato no es válido.';
    }
}

// CONSULTAR PLATOS
$stmt = $pdo->query("SELECT * FROM dishes ORDER BY id DESC");
$platos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$imagenes = [
    'Hamburguesa clásica' => 'hamburguesa.jpg',
    'Pizza personal' => 'pizza.jpg',
    'Ensalada César' => 'ensalada.jpg'
];

// CONTAR UNIDADES DEL CARRITO
$totalCarrito = 0;

if ($esCliente) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        $totalCarrito += (int)$cantidad;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Menú | Bosques & Brasas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">

    <style>
        body {
            background: #F8F6F0 !important;
            color: #333A32;
        }

        .navbar {
            background: #243D30 !important;
            padding: 12px 0;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #F3EBDD !important;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
        }

        .navbar-brand img {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }

        .menu-header {
            background:
                linear-gradient(90deg, rgba(25,49,36,.94), rgba(81,57,35,.70)),
                url('img/restaurante.jpg') center/cover no-repeat;
            padding: 55px 40px;
            border-radius: 20px;
            margin-bottom: 35px;
            color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,.10);
        }

        .menu-header h1 {
            color: #F3EBDD !important;
            font-family: 'Playfair Display', serif;
            font-size: 2.7rem;
            font-weight: bold;
        }

        .menu-header p {
            color: #F3EBDD !important;
            font-size: 1.05rem;
            margin: 0;
        }

        .menu-label {
            display: inline-block;
            background: rgba(181,139,91,.35);
            border: 1px solid #B58B5B;
            color: #F3EBDD;
            padding: 7px 15px;
            border-radius: 30px;
            margin-bottom: 15px;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .section-title {
            color: #243D30;
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .section-subtitle {
            color: #85877D;
            margin-bottom: 25px;
        }

        .admin-form {
            background: white;
            border: 1px solid #E8E3D8;
            border-radius: 17px;
            padding: 28px;
            margin-bottom: 40px;
            box-shadow: 0 5px 18px rgba(45,57,40,.06);
        }

        .admin-form h3 {
            color: #243D30;
            font-family: 'Playfair Display', serif;
            margin-bottom: 22px;
        }

        .form-label {
            color: #243D30;
            font-weight: 600;
        }

        .form-control {
            border: 1px solid #E8E3D8;
            border-radius: 9px;
            padding: 11px;
        }

        .form-control:focus {
            border-color: #71845B;
            box-shadow: 0 0 0 .2rem rgba(113,132,91,.15);
        }

        .btn-add {
            background: #71845B;
            color: white;
            border: none;
            padding: 11px 22px;
            border-radius: 9px;
            font-weight: 600;
        }

        .btn-add:hover {
            background: #365642;
            color: white;
        }

        .dish-card {
            background: white;
            border: 1px solid #E8E3D8;
            border-radius: 17px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 5px 18px rgba(45,57,40,.06);
            transition: .3s ease;
        }

        .dish-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 30px rgba(36,61,48,.13);
            border-color: #B58B5B;
        }

        .dish-image {
            width: 100%;
            height: 225px;
            overflow: hidden;
            background: #E5EAD9;
        }

        .dish-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }

        .dish-card:hover .dish-image img {
            transform: scale(1.05);
        }

        .dish-content {
            padding: 23px;
        }

        .dish-content h4 {
            color: #243D30;
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .dish-description {
            color: #85877D;
            line-height: 1.6;
            min-height: 45px;
        }

        .dish-price {
            color: #795438;
            font-size: 1.45rem;
            font-weight: bold;
        }

        .btn-order {
            background: #243D30;
            color: white;
            border: none;
            border-radius: 9px;
            padding: 10px 18px;
            font-weight: 600;
        }

        .btn-order:hover {
            background: #71845B;
            color: white;
        }

        .btn-delete {
            background: #795438;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 9px 14px;
            font-weight: 600;
        }

        .btn-delete:hover {
            background: #513923;
            color: white;
        }

        .cart-link {
            background: #71845B;
            color: white;
            border-radius: 9px;
            padding: 9px 14px;
            text-decoration: none;
            font-weight: 600;
        }

        .cart-link:hover {
            background: #B58B5B;
            color: white;
        }

        .quantity-select {
            width: 75px;
            border: 1px solid #E8E3D8;
            border-radius: 8px;
            padding: 9px;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .empty-menu {
            background: white;
            border: 1px solid #E8E3D8;
            padding: 45px;
            border-radius: 17px;
            text-align: center;
        }

        .footer {
            text-align: center;
            padding: 30px 10px;
            color: #85877D;
            font-size: 13px;
        }

        @media(max-width: 768px) {
            .menu-header {
                padding: 35px 25px;
            }

            .menu-header h1 {
                font-size: 2rem;
            }

            .navbar-brand {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark">
    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            <img src="img/logo.png" alt="Bosques & Brasas">
            <span>Bosques & Brasas</span>
        </a>

        <div class="d-flex align-items-center flex-wrap gap-2 text-white">

            <span>
                <?= h($_SESSION['user']['username'] ?? '') ?>
                ·
                <?= h($rol) ?>
            </span>

            <?php if ($esCliente): ?>
                <a href="carrito.php" class="cart-link">
                    Mi carrito (<?= $totalCarrito ?>)
                </a>
            <?php endif; ?>

            <a href="logout.php" class="btn btn-outline-light btn-sm">
                Salir
            </a>

        </div>
    </div>
</nav>

<main class="container py-4">

    <section class="menu-header">
        <span class="menu-label">NUESTRA COCINA</span>

        <h1>Nuestro menú</h1>

        <p>
            Sabores que nacen de la naturaleza,
            preparados con pasión y servidos con tradición.
        </p>
    </section>

    <?php if (($_GET['mensaje'] ?? '') === 'agregado'): ?>
        <div class="alert alert-success">
            ¡El plato se agregó correctamente!
        </div>
    <?php elseif (($_GET['mensaje'] ?? '') === 'eliminado'): ?>
        <div class="alert alert-success">
            El plato se eliminó correctamente.
        </div>
    <?php endif; ?>

    <?php if ($mensajeError !== ''): ?>
        <div class="alert alert-danger">
            <?= h($mensajeError) ?>
        </div>
    <?php endif; ?>

    <?php if ($esAdmin): ?>

        <section class="admin-form">

            <h3>＋ Agregar nuevo plato</h3>

            <form method="POST" action="menu.php">

                <input type="hidden" name="accion" value="agregar">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Nombre del plato</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            placeholder="Ej. Hamburguesa especial"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Precio ($)</label>
                        <input
                            type="number"
                            name="price"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            placeholder="Ej. 8.50"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nombre de la imagen</label>
                        <input
                            type="text"
                            name="image"
                            class="form-control"
                            placeholder="Ej. hamburguesa.jpg"
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea
                            name="description"
                            class="form-control"
                            rows="3"
                            placeholder="Describe los ingredientes del plato..."
                        ></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn-add">
                            + Agregar plato
                        </button>
                    </div>

                </div>
            </form>
        </section>

    <?php endif; ?>

    <h2 class="section-title">Nuestros platos</h2>

    <p class="section-subtitle">
        Descubre las opciones que tenemos preparadas para ti.
    </p>

    <div class="row g-4">

        <?php if (count($platos) > 0): ?>

            <?php foreach ($platos as $plato): ?>

                <?php
                    $nombre = $plato['name'];
                    $imagen = trim($plato['image'] ?? '');

                    if ($imagen === '') {
                        $imagen = $imagenes[$nombre] ?? 'default.jpg';
                    }
                ?>

                <div class="col-sm-6 col-lg-4">

                    <article class="dish-card">

                        <div class="dish-image">
                            <img
                                src="img/<?= h($imagen) ?>"
                                alt="<?= h($nombre) ?>"
                                onerror="this.onerror=null;this.src='img/default.jpg';"
                            >
                        </div>

                        <div class="dish-content">

                            <h4><?= h($nombre) ?></h4>

                            <p class="dish-description">
                                <?= h($plato['description'] ?? '') ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3">

                                <span class="dish-price">
                                    $<?= number_format((float)$plato['price'], 2) ?>
                                </span>

                            </div>

                            <?php if ($esCliente): ?>

                                <form method="POST" action="carrito.php" class="mt-3">

                                    <input type="hidden" name="accion" value="agregar">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($_SESSION['csrf_carrito']) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="dish_id"
                                        value="<?= (int)$plato['id'] ?>"
                                    >

                                    <div class="d-flex align-items-center gap-2">

                                        <input
                                            type="number"
                                            name="cantidad"
                                            class="quantity-select"
                                            value="1"
                                            min="1"
                                            max="99"
                                            required
                                        >

                                        <button type="submit" class="btn btn-order">
                                            Agregar al carrito
                                        </button>

                                    </div>
                                </form>

                            <?php endif; ?>

                            <?php if ($esAdmin): ?>

                                <form
                                    method="POST"
                                    action="menu.php"
                                    class="mt-3"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este plato?');"
                                >

                                    <input type="hidden" name="accion" value="eliminar">

                                    <input
                                        type="hidden"
                                        name="dish_id"
                                        value="<?= (int)$plato['id'] ?>"
                                    >

                                    <button type="submit" class="btn-delete">
                                        Eliminar plato
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>
                    </article>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="empty-menu">

                    <h4 class="section-title">Todavía no tenemos platos</h4>

                    <p>
                        Pronto agregaremos nuevas opciones a nuestro menú.
                    </p>

                </div>
            </div>

        <?php endif; ?>

    </div>

</main>

<footer class="footer">
    © <?= date('Y') ?> Bosques & Brasas · Todos los derechos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>

</body>
</html>
