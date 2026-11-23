
<?php
require_once 'config/conexion.php';
require_login();

$counts = $pdo->query("SELECT status, COUNT(*) total FROM orders GROUP BY status")->fetchAll();
$total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Bosques & Brasas | Panel principal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">

    <style>
        body {
            background: #F8F6F0 !important;
            color: #333A32;
        }

        /* NAVEGACIÓN */

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
            font-size: 23px;
        }

        .navbar-brand img {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }

        /* BIENVENIDA */

        .hero {
            min-height: 320px;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 40px;
            background:
                linear-gradient(
                    90deg,
                    rgba(25, 49, 36, .92),
                    rgba(25, 49, 36, .40)
                ),
                url('img/restaurante.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            padding: 45px;
            box-shadow: 0 12px 30px rgba(0,0,0,.12);
        }

        .hero-content {
            max-width: 600px;
            color: white;
        }

        .hero h1 {
            color: #F3EBDD !important;
            font-size: 2.8rem;
            margin-bottom: 15px;
        }

        .hero p {
            color: #F3EBDD !important;
            font-size: 1.1rem;
            line-height: 1.7;
        }

        .hero-tag {
            display: inline-block;
            color: #F3EBDD;
            background: rgba(181,139,91,.35);
            border: 1px solid #B58B5B;
            padding: 7px 15px;
            border-radius: 30px;
            margin-bottom: 18px;
            font-size: 13px;
            letter-spacing: 1px;
        }

        /* TÍTULOS */

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

        /* TARJETAS */

        .dashboard-card {
            background: #FFFFFF;
            border: 1px solid #E8E3D8;
            border-radius: 16px;
            padding: 27px;
            height: 100%;
            text-decoration: none;
            display: block;
            transition: .3s ease;
            box-shadow: 0 5px 18px rgba(45,57,40,.06);
        }

        .dashboard-card:hover {
            transform: translateY(-7px);
            border-color: #B58B5B;
            box-shadow: 0 12px 28px rgba(45,57,40,.12);
        }

        .card-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            background: #E5EAD9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            margin-bottom: 20px;
        }

        .dashboard-card h4 {
            color: #243D30;
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            color: #85877D;
            margin: 0;
            line-height: 1.6;
        }

        /* RESUMEN */

        .summary {
            background: #FFFFFF;
            border: 1px solid #E8E3D8;
            border-radius: 16px;
            padding: 27px;
            margin-top: 35px;
            box-shadow: 0 5px 18px rgba(45,57,40,.06);
        }

        .total-box {
            background: #F3EBDD;
            border-left: 5px solid #795438;
            padding: 18px;
            border-radius: 10px;
            margin-top: 18px;
        }

        .total-number {
            color: #243D30;
            font-size: 30px;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            background: #365642;
            color: white;
            padding: 8px 14px;
            border-radius: 30px;
            margin: 8px 5px 0 0;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            padding: 30px 10px;
            color: #85877D;
            font-size: 13px;
        }

        @media(max-width: 768px) {
            .hero {
                padding: 28px;
                min-height: 280px;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .navbar-brand {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>

    <!-- BARRA DE NAVEGACIÓN -->

    <nav class="navbar navbar-dark">
        <div class="container">

            <a class="navbar-brand" href="dashboard.php">
                <img src="img/logo.png" alt="Logo Bosques & Brasas">
                <span>Bosques & Brasas</span>
            </a>

            <div class="text-white">
                <?= h($_SESSION['user']['username']) ?>
                ·
                <?= h($_SESSION['user']['role']) ?>

                <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">
                    Salir
                </a>
            </div>

        </div>
    </nav>

    <main class="container py-4">

        <!-- BIENVENIDA -->

        <section class="hero">
            <div class="hero-content">

                <span class="hero-tag">RESTAURANTE</span>

                <h1>Bosques & Brasas</h1>

                <p>
                    Sabores que nacen de la naturaleza,
                    preparados con pasión y servidos con tradición.
                </p>

            </div>
        </section>

        <!-- OPCIONES DEL SISTEMA -->

        <h2 class="section-title">Panel de administración</h2>

        <p class="section-subtitle">
            Gestiona las actividades de nuestro restaurante desde aquí.
        </p>

        <div class="row g-4">

            <?php if (role_is('Administrator')): ?>

                <div class="col-md-4">
                    <a href="menu.php" class="dashboard-card">
                        <div class="card-icon">🍲</div>
                        <h4>Menú y platos</h4>
                        <p>Administra los platos, precios y descripciones del restaurante.</p>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="usuarios.php" class="dashboard-card">
                        <div class="card-icon">👥</div>
                        <h4>Usuarios</h4>
                        <p>Gestiona las cuentas y asigna los roles del sistema.</p>
                    </a>
                </div>

            <?php endif; ?>

            <?php if (role_is('Client')): ?>

                <div class="col-md-4">
                    <a href="menu.php" class="dashboard-card">
                        <div class="card-icon">📖</div>
                        <h4>Ver menú</h4>
                        <p>Explora nuestros platos y realiza tus pedidos.</p>
                    </a>
                </div>

            <?php endif; ?>

            <?php if (role_is('Chef', 'Waiter', 'Administrator')): ?>

                <div class="col-md-4">
                    <a href="pedidos.php" class="dashboard-card">
                        <div class="card-icon">🧾</div>
                        <h4>Pedidos</h4>
                        <p>Consulta y gestiona los pedidos del restaurante.</p>
                    </a>
                </div>

            <?php endif; ?>

            <?php if (role_is('Client')): ?>

                <div class="col-md-4">
                    <a href="pedidos.php" class="dashboard-card">
                        <div class="card-icon">🕒</div>
                        <h4>Mis pedidos</h4>
                        <p>Consulta el estado y el historial de tus pedidos.</p>
                    </a>
                </div>

            <?php endif; ?>

        </div>

        <!-- RESUMEN DE PEDIDOS -->

        <section class="summary">

            <h3 class="section-title">Resumen de pedidos</h3>

            <p class="section-subtitle">
                Estado general de los pedidos registrados.
            </p>

            <div class="total-box">
                <div>Total de pedidos registrados</div>
                <div class="total-number">
                    <?= h($total) ?>
                </div>
            </div>

            <div class="mt-3">

                <?php foreach ($counts as $c): ?>

                    <span class="status-badge">
                        <?= h($c['status']) ?>:
                        <?= h($c['total']) ?>
                    </span>

                <?php endforeach; ?>

            </div>

        </section>

    </main>

    <footer class="footer">
        © <?= date('Y') ?> Bosques & Brasas · Todos los derechos reservados.
    </footer>

    <script src="js/app.js"></script>

</body>
</html>
