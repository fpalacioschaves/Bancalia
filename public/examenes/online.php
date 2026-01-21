<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$examenService = new ExamenService(pdo());

$examen_id = intval($_GET['examen_id'] ?? 0);
if ($examen_id <= 0) {
    echo "<h1>Error</h1><p>ID de examen no válido.</p>";
    exit;
}

$examen = $examenService->findFull($examen_id);
if (!$examen) {
    echo "<h1>Error</h1><p>Examen no encontrado.</p>";
    exit;
}

// Cargar actividades completas
$actividadesFull = $examenService->getActivitiesFull($examen_id);
$examen['actividades'] = $actividadesFull;

// Tipo de contenedor: examen formal o práctica
$tipoRaw = $examen['tipo'] ?? 'examen';
$isPractica = ($tipoRaw === 'practica');
$labelTitulo = $isPractica ? 'Hoja de actividades' : 'Examen';
$labelCorto = $isPractica ? 'práctica' : 'examen'; // para textos cortos

// Validación básica de disponibilidad
$hoy = date('Y-m-d');
$ahora = date('H:i:s');

if ($examen['estado'] !== 'publicado') {
    echo "<h1>$labelTitulo no disponible</h1><p>Este $labelCorto no está publicado.</p>";
    exit;
}

if ($examen['fecha'] !== null) {
    if ($examen['fecha'] > $hoy) {
        echo "<h1>Aún no disponible</h1><p>Este $labelCorto todavía no ha comenzado.</p>";
        exit;
    }
    if ($examen['fecha'] < $hoy) {
        echo "<h1>$labelTitulo cerrado</h1><p>La fecha de este $labelCorto ya ha pasado.</p>";
        exit;
    }
}

if ($examen['hora'] !== null && $examen['fecha'] === $hoy) {
    if ($examen['hora'] > $ahora) {
        echo "<h1>Aún no disponible</h1><p>Este $labelCorto comenzará a las {$examen['hora']}.</p>";
        exit;
    }
}

/********************************************************************
 * PASO 1: LOGIN DEL ALUMNO
 ********************************************************************/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';

if (!isset($_SESSION['examen_intento'])) {

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['nombre'])
        && isset($_POST['email'])
    ) {

        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nombre === '' || $email === '') {
            $error = "Debes rellenar todos los campos.";
        } else {
            $intento_id = $examenService->createAttempt($examen_id, $nombre, $email);
            $_SESSION['examen_intento'] = $intento_id;
            header("Location: online.php?examen_id=" . $examen_id . ($isEmbed ? '&embed=1' : ''));
            exit;
        }
    }

    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <title>Acceso a la <?= h($labelTitulo) ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-100 <?= $isEmbed ? 'p-2' : '' ?>">

        <div class="<?= $isEmbed ? 'w-full' : 'min-h-screen flex items-center justify-center px-4 mt-10' ?>">

            <div class="w-full <?= $isEmbed ? '' : 'max-w-md shadow-xl' ?> bg-white rounded-2xl p-8">

                <h1 class="text-3xl font-bold text-gray-900 mb-2 text-center">
                    Acceso a la <?= h($labelTitulo) ?>
                </h1>
                <p class="text-sm text-gray-600 mb-6 text-center">
                    <?= h($examen['titulo']) ?>
                </p>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="online.php?examen_id=<?= $examen_id ?><?= $isEmbed ? '&embed=1' : '' ?>"
                    class="space-y-6">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre completo
                        </label>
                        <input type="text" name="nombre" class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm 
                               focus:ring-indigo-500 focus:border-indigo-500" placeholder="Introduce tu nombre"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm 
                               focus:ring-indigo-500 focus:border-indigo-500" placeholder="Introduce tu email"
                            required>
                    </div>

                    <div>
                        <button class="w-full bg-indigo-600 hover:bg-indigo-500 text-white 
                               text-center py-3 rounded-lg font-semibold tracking-wide 
                               shadow-md transition">
                            Comenzar <?= h($labelCorto) ?>
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </body>

    </html>
    <?php
    exit;
}

/********************************************************************
 * PASO 2: MOSTRAR EXAMEN / GUARDAR RESPUESTAS
 ********************************************************************/

$intento_id = $_SESSION['examen_intento'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fin_examen'])) {

    $respuestas = [];

    foreach ($examen['actividades'] as $a) {
        $actividad_id = $a['id']; // Cambio: ahora usamos 'id' del array de actividad full

        // Recogemos cualquier campo que empiece por "resp_{actividad_id}"
        $coincidentes = [];
        foreach ($_POST as $k => $v) {
            if (strpos($k, "resp_{$actividad_id}") === 0) {
                $coincidentes[$k] = $v;
            }
        }

        $respuestas[$actividad_id] = $coincidentes ?: null;
    }

    $examenService->saveAnswers($intento_id, $respuestas);

    unset($_SESSION['examen_intento']);

    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <title><?= h($labelTitulo) ?> enviada</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-gray-100 <?= $isEmbed ? 'p-2' : '' ?>">
        <div class="<?= $isEmbed ? 'w-full' : 'max-w-xl mx-auto mt-20 shadow-lg' ?> bg-white p-8 rounded-lg text-center">
            <h1 class="text-2xl font-bold mb-6"><?= h($labelTitulo) ?> enviada correctamente</h1>
            <p class="text-gray-700">Gracias por completar esta <?= h($labelCorto) ?>.</p>
        </div>
    </body>

    </html>
    <?php
    exit;
}

$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= h($examen['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 <?= $isEmbed ? 'p-0' : '' ?>">

    <div class="<?= $isEmbed ? 'max-w-full' : 'max-w-3xl mx-auto mt-10' ?> bg-white p-10 shadow-lg rounded-lg">

        <?php if (!$isEmbed): ?>
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-bold mb-1"><?= h($examen['titulo']) ?></h1>
                    <p class="text-sm text-gray-500">
                        <?= h($labelTitulo) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="online.php?examen_id=<?= $examen_id ?><?= $isEmbed ? '&embed=1' : '' ?>">

            <?php foreach ($examen['actividades'] as $index => $actividad): ?>
                <div class="mb-10 pb-10 border-b">

                    <h2 class="text-xl font-semibold mb-2">Pregunta <?= $index + 1 ?></h2>

                    <div class="mb-4 text-lg leading-relaxed">
                        <?= nl2br(h($actividad['titulo'])) ?>
                    </div>

                    <?php if (!empty($actividad['descripcion'])): ?>
                        <div class="mb-4 text-gray-600 text-sm">
                            <?= nl2br(h($actividad['descripcion'])) ?>
                        </div>
                    <?php endif; ?>


                    <?php if ($actividad['tipo'] === 'verdadero_falso'): ?>

                        <label class="block mb-3">
                            <input type="radio" name="resp_<?= $actividad['id'] ?>" value="verdadero" class="mr-2">
                            Verdadero
                        </label>
                        <label class="block mb-3">
                            <input type="radio" name="resp_<?= $actividad['id'] ?>" value="falso" class="mr-2">
                            Falso
                        </label>

                    <?php elseif ($actividad['tipo'] === 'opcion_multiple'): ?>

                        <?php if (!empty($actividad['om_options'])): ?>
                            <?php foreach ($actividad['om_options'] as $op): ?>
                                <label class="block mb-3">
                                    <input type="radio" name="resp_<?= $actividad['id'] ?>" value="<?= (int) $op['id'] ?>" class="mr-2">
                                    <!-- usamos opcion_html, que es el campo real -->
                                    <?= $op['opcion_html'] ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500"><em>Esta pregunta no tiene opciones configuradas.</em></p>
                        <?php endif; ?>

                    <?php elseif ($actividad['tipo'] === 'respuesta_corta'): ?>

                        <textarea name="resp_<?= $actividad['id'] ?>" class="w-full border p-3 rounded" rows="4"></textarea>

                    <?php elseif ($actividad['tipo'] === 'rellenar_huecos'): ?>

                        <?php
                        $texto = $actividad['enunciado_html'] ?? '';
                        $huecos = json_decode($actividad['huecos_json'] ?? '[]', true);
                        $num = is_array($huecos) ? count($huecos) : 0;

                        for ($i = 1; $i <= $num; $i++) {
                            $input = "<input class='border p-2 rounded w-40 inline-block mx-1' " .
                                "name='resp_{$actividad['id']}_{$i}'>";
                            $texto = str_replace('{{' . $i . '}}', $input, $texto);
                        }
                        ?>

                        <div class="leading-relaxed text-lg mb-4">
                            <?= $texto ?>
                        </div>

                    <?php elseif ($actividad['tipo'] === 'emparejar'): ?>

                        <?php
                        $pares = json_decode($actividad['pares_json'] ?? '[]', true);
                        if (!empty($pares)): ?>
                            <?php
                            // Creamos un array de "derechas" para los selects
                            $derechas = [];
                            foreach ($pares as $p) {
                                $derechas[] = $p[1];
                            }
                            $derechas = array_unique($derechas);
                            shuffle($derechas); // Aleatorizar para el alumno
                            ?>
                            <?php foreach ($pares as $idx => $p): ?>
                                <div class="flex items-center gap-4 mb-3">
                                    <span class="font-semibold"><?= $p[0] ?></span>
                                    →
                                    <select name="resp_<?= $actividad['id'] ?>_<?= $idx ?>" class="border p-2 rounded">
                                        <option value="">—</option>
                                        <?php foreach ($derechas as $der): ?>
                                            <option value="<?= h($der) ?>">
                                                <?= h($der) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500"><em>Esta actividad de emparejar no tiene pares configurados.</em></p>
                        <?php endif; ?>

                    <?php elseif ($actividad['tipo'] === 'tarea'): ?>

                        <?php if (!empty($actividad['instrucciones'])): ?>
                            <div class="mb-3 text-gray-700">
                                <?= nl2br(h($actividad['instrucciones'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($actividad['perm_texto'])): ?>
                            <label class="block mb-3">
                                Redacción:
                                <textarea class="w-full border rounded p-3 mt-2" rows="6"
                                    name="resp_<?= $actividad['id'] ?>_texto"></textarea>
                            </label>
                        <?php endif; ?>

                        <?php if (!empty($actividad['perm_enlace'])): ?>
                            <label class="block mb-3">
                                Enlace:
                                <input class="w-full border p-2 rounded mt-2" name="resp_<?= $actividad['id'] ?>_enlace">
                            </label>
                        <?php endif; ?>

                        <?php if (empty($actividad['perm_texto']) && empty($actividad['perm_enlace'])): ?>
                            <p class="text-sm text-gray-500">
                                <em>Esta tarea no tiene campos habilitados (texto/enlace).</em>
                            </p>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

            <input type="hidden" name="fin_examen" value="1">

            <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-lg text-lg font-semibold">
                Enviar <?= h($labelCorto) ?>
            </button>

        </form>

    </div>

</body>

</html>