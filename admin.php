<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit();
}

require 'config.php';

// Actualizar el estado de la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitud_id'])) {
    $solicitud_id = $_POST['solicitud_id'];
    $atendida = $_POST['atendida'] ? 1 : 0;

    $sql = "UPDATE solicitudes SET atendida = :atendida WHERE id = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':atendida', $atendida);
    $stmt->bindParam(':id', $solicitud_id);
    $stmt->execute();
}

// Filtros de búsqueda
$filtroServicio = isset($_GET['servicio']) ? $_GET['servicio'] : '';
$filtroAtendida = isset($_GET['atendida']) ? $_GET['atendida'] : '';
$filtroFecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Configuración de la paginación
$perPage = 10; // Número de elementos por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual
$offset = ($page - 1) * $perPage; // Índice de inicio para la página actual

// Construir la consulta SQL con los filtros y paginación
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM solicitudes WHERE 1=1";
$params = [];
if ($filtroServicio) {
    $sql .= " AND servicio = :servicio";
    $params[':servicio'] = $filtroServicio;
}
if ($filtroAtendida !== '') {
    $sql .= " AND atendida = :atendida";
    $params[':atendida'] = (int)$filtroAtendida;
}
if ($filtroFecha) {
    $sql .= " AND DATE(fecha) = :fecha";
    $params[':fecha'] = $filtroFecha;
}
$sql .= " ORDER BY fecha DESC LIMIT :offset, :perPage";
$params[':offset'] = $offset;
$params[':perPage'] = $perPage;

$stmt = $conexion->prepare($sql);
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$currentSolicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el número total de solicitudes sin limitación
$totalSolicitudes = $conexion->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($totalSolicitudes / $perPage);

// Verificar si hay solicitudes pendientes
$hayPendientes = false;
foreach ($currentSolicitudes as $solicitud) {
    if (!$solicitud['atendida']) {
        $hayPendientes = true;
        break;
    }
}
?>
<!doctype html>
<html lang="es" class="h-100" data-bs-theme="auto">

<head>
    <meta charset="utf-8">
    <title>Central</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">



    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        .b-example-divider {
            width: 100%;
            height: 3rem;
            background-color: rgba(0, 0, 0, .1);
            border: solid rgba(0, 0, 0, .15);
            border-width: 1px 0;
            box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
        }

        .b-example-vr {
            flex-shrink: 0;
            width: 1.5rem;
            height: 100vh;
        }

        .bi {
            vertical-align: -.125em;
            fill: currentColor;
        }

        .nav-scroller {
            position: relative;
            z-index: 2;
            height: 2.75rem;
            overflow-y: hidden;
        }

        .nav-scroller .nav {
            display: flex;
            flex-wrap: nowrap;
            padding-bottom: 1rem;
            margin-top: -1px;
            overflow-x: auto;
            text-align: center;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .btn-bd-primary {
            --bd-violet-bg: #712cf9;
            --bd-violet-rgb: 112.520718, 44.062154, 249.437846;

            --bs-btn-font-weight: 600;
            --bs-btn-color: var(--bs-white);
            --bs-btn-bg: var(--bd-violet-bg);
            --bs-btn-border-color: var(--bd-violet-bg);
            --bs-btn-hover-color: var(--bs-white);
            --bs-btn-hover-bg: #6528e0;
            --bs-btn-hover-border-color: #6528e0;
            --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
            --bs-btn-active-color: var(--bs-btn-hover-color);
            --bs-btn-active-bg: #5a23c8;
            --bs-btn-active-border-color: #5a23c8;
        }

        .bd-mode-toggle {
            z-index: 1500;
        }

        .bd-mode-toggle .dropdown-menu .active .bi {
            display: block !important;
        }
    </style>

</head>

<body class="d-flex h-100 text-center text-bg-dark">

    <div class="container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <header class="mb-5">
            <div>
                <h3 class="float-md-start mb-0">EmergConnect</h3>
                <nav class="nav nav-masthead justify-content-center float-md-end">
                    <a class="nav-link fw-bold py-1 px-0 active text-white" href="logout.php">Cerrar sesion</a>
                </nav>
            </div>
        </header>

        <div class="container-fluid ">
            <h1 class="mb-5">Bienvenido a la central de EmergConnect</h1>

            <?php if ($hayPendientes) : ?>
                <div class="alert alert-warning" role="alert">
                    Hay solicitudes pendientes.
                </div>
            <?php endif; ?>

            <h2 class="mb-4">Solicitudes Recibidas</h2>
            <form method="GET" class="form-inline mb-3">
                <div class="form-group mr-2">
                    <label for="servicio" class="mr-2">Servicio:</label>
                    <select name="servicio" id="servicio" class="form-control">
                        <option value="">Todos</option>
                        <option value="Policía" <?php echo $filtroServicio === 'Policía' ? 'selected' : ''; ?>>Policía</option>
                        <option value="Ambulancia" <?php echo $filtroServicio === 'Ambulancia' ? 'selected' : ''; ?>>Ambulancia</option>
                        <option value="Bomberos" <?php echo $filtroServicio === 'Bomberos' ? 'selected' : ''; ?>>Bomberos</option>
                    </select>
                </div>
                <div class="form-group mr-2">
                    <label for="atendida" class="mr-2">Atendida:</label>
                    <select name="atendida" id="atendida" class="form-control">
                        <option value="">Todas</option>
                        <option value="1" <?php echo $filtroAtendida === '1' ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo $filtroAtendida === '0' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <div class="form-group mr-2">
                    <label for="fecha" class="mr-2">Fecha:</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?php echo htmlspecialchars($filtroFecha); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Latitud</th>
                            <th>Longitud</th>
                            <th>Fecha</th>
                            <th>Atendida</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentSolicitudes as $solicitud) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($solicitud['servicio']); ?></td>
                                <td><?php echo htmlspecialchars($solicitud['latitud']); ?></td>
                                <td><?php echo htmlspecialchars($solicitud['longitud']); ?></td>
                                <td><?php echo htmlspecialchars($solicitud['fecha']); ?></td>
                                <td>
                                    <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" <?php echo $solicitud['atendida'] ? 'checked' : ''; ?> onchange="updateStatus(<?php echo $solicitud['id']; ?>, this.checked)">
                                    </div>
                                </td>
                                <td>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $solicitud['latitud']; ?>,<?php echo $solicitud['longitud']; ?>" target="_blank" class="btn btn-info">Ver en Maps</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-end">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>


        <footer class="mt-auto text-white-50">
            <p>Desarrollado para, <a class="text-white">Telecomunicaciones</a>.</p>
            <script>
                function updateStatus(solicitudId, atendida) {
                    $.post('admin.php', {
                        solicitud_id: solicitudId,
                        atendida: atendida
                    }, function() {
                        location.reload();
                    });
                }
            </script>
        </footer>
    </div>


</body>

</html>