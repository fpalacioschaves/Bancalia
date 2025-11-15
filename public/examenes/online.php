<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bancalia/config.php';

/********************************************************************
 * FUNCIONES AUXILIARES
 ********************************************************************/

function cargarExamen(PDO $pdo, int $examen_id): ?array {
    $st = $pdo->prepare("
        SELECT *
        FROM examenes
        WHERE id = ?
    ");
    $st->execute([$examen_id]);
    $examen = $st->fetch(PDO::FETCH_ASSOC);
    if (!$examen) {
        return null;
    }

    $st2 = $pdo->prepare("
        SELECT 
            ea.actividad_id, 
            ea.orden,
            a.tipo,
            a.titulo,
            a.descripcion
        FROM examenes_actividades ea
        JOIN actividades a ON a.id = ea.actividad_id
        WHERE ea.examen_id = ?
        ORDER BY ea.orden ASC
    ");
    $st2->execute([$examen_id]);
    $examen['actividades'] = $st2->fetchAll(PDO::FETCH_ASSOC);

    return $examen;
}

function cargarActividad(PDO $pdo, int $actividad_id): ?array {
    $st = $pdo->prepare("SELECT * FROM actividades WHERE id = ?");
    $st->execute([$actividad_id]);
    $base = $st->fetch(PDO::FETCH_ASSOC);
    if (!$base) {
        return null;
    }

    $tipo = $base['tipo'];

    switch ($tipo) {
        case 'verdadero_falso':
            $st2 = $pdo->prepare("
                SELECT *
                FROM actividades_vf
                WHERE actividad_id = ?
            ");
            $st2->execute([$actividad_id]);
            $base['vf'] = $st2->fetch(PDO::FETCH_ASSOC);
            break;

        case 'opcion_multiple':
            $st1 = $pdo->prepare("
                SELECT *
                FROM actividades_om
                WHERE actividad_id = ?
            ");
            $st1->execute([$actividad_id]);
            $base['om'] = $st1->fetch(PDO::FETCH_ASSOC);

            $st2 = $pdo->prepare("
                SELECT *
                FROM actividades_om_opciones
                WHERE actividad_id = ?
                ORDER BY orden ASC, id ASC
            ");
            $st2->execute([$actividad_id]);
            $base['opciones'] = $st2->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'respuesta_corta':
            $st2 = $pdo->prepare("
                SELECT *
                FROM actividades_rc
                WHERE actividad_id = ?
            ");
            $st2->execute([$actividad_id]);
            $base['rc'] = $st2->fetch(PDO::FETCH_ASSOC);
            break;

        case 'rellenar_huecos':
            $st2 = $pdo->prepare("
                SELECT *
                FROM actividades_rh
                WHERE actividad_id = ?
            ");
            $st2->execute([$actividad_id]);
            $base['rh'] = $st2->fetch(PDO::FETCH_ASSOC);
            break;

        case 'emparejar':
            $st2 = $pdo->prepare("
                SELECT *
                FROM actividades_emp_pares
                WHERE actividad_id = ?
                ORDER BY orden_izq ASC, id ASC
            ");
            $st2->execute([$actividad_id]);
            $base['pares'] = $st2->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'tarea':
            $st2 = $pdo->prepare("
                SELECT *
                FROM actividades_tarea
                WHERE actividad_id = ?
            ");
            $st2->execute([$actividad_id]);
            $base['tarea'] = $st2->fetch(PDO::FETCH_ASSOC);
            break;
    }

    return $base;
}

function crearIntento(PDO $pdo, int $examen_id, string $nombre, string $email): int {
    // Tu tabla tiene: examen_id, nombre_alumno, email_alumno, token
    $token = bin2hex(random_bytes(16));

    $st = $pdo->prepare("
        INSERT INTO examen_intentos (examen_id, nombre_alumno, email_alumno, token)
        VALUES (?, ?, ?, ?)
    ");
    $st->execute([$examen_id, $nombre, $email, $token]);

    return (int)$pdo->lastInsertId();
}

function guardarRespuestas(PDO $pdo, int $intento_id, array $respuestas): void {
    $st = $pdo->prepare("
        INSERT INTO examen_respuestas (intento_id, actividad_id, respuesta_json)
        VALUES (?, ?, ?)
    ");

    foreach ($respuestas as $actividad_id => $resp) {
        $json = json_encode($resp, JSON_UNESCAPED_UNICODE);
        $st->execute([$intento_id, $actividad_id, $json]);
    }
}

/********************************************************************
 * LÓGICA PRINCIPAL
 ********************************************************************/

$pdo = pdo();

$examen_id = intval($_GET['examen_id'] ?? 0);
if ($examen_id <= 0) {
    echo "<h1>Error</h1><p>ID de examen no válido.</p>";
    exit;
}

$examen = cargarExamen($pdo, $examen_id);
if (!$examen) {
    echo "<h1>Error</h1><p>Examen no encontrado.</p>";
    exit;
}

// Validación básica de disponibilidad
$hoy   = date('Y-m-d');
$ahora = date('H:i:s');

if ($examen['estado'] !== 'publicado') {
    echo "<h1>Examen no disponible</h1><p>El examen no está publicado.</p>";
    exit;
}

if ($examen['fecha'] !== null) {
    if ($examen['fecha'] > $hoy) {
        echo "<h1>Aún no disponible</h1><p>El examen todavía no ha comenzado.</p>";
        exit;
    }
    if ($examen['fecha'] < $hoy) {
        echo "<h1>Examen cerrado</h1><p>La fecha del examen ya ha pasado.</p>";
        exit;
    }
}

if ($examen['hora'] !== null && $examen['fecha'] === $hoy) {
    if ($examen['hora'] > $ahora) {
        echo "<h1>Aún no disponible</h1><p>El examen comenzará a las {$examen['hora']}.</p>";
        exit;
    }
}

/********************************************************************
 * PASO 1: LOGIN DEL ALUMNO
 ********************************************************************/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['examen_intento'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['nombre'])
        && isset($_POST['email'])) {

        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if ($nombre === '' || $email === '') {
            $error = "Debes rellenar todos los campos.";
        } else {
            $intento_id = crearIntento($pdo, $examen_id, $nombre, $email);
            $_SESSION['examen_intento'] = $intento_id;
            header("Location: online.php?examen_id=" . $examen_id);
            exit;
        }
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Acceso al Examen</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center px-4">

        <div class="w-full max-w-md bg-white shadow-xl rounded-2xl p-8">

            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">
                Acceso al examen
            </h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre completo
                    </label>
                    <input 
                        type="text" 
                        name="nombre"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm 
                               focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Introduce tu nombre"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <input 
                        type="email" 
                        name="email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm 
                               focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Introduce tu email"
                        required
                    >
                </div>

                <div>
                    <button
                        class="w-full bg-indigo-600 hover:bg-indigo-500 text-white 
                               text-center py-3 rounded-lg font-semibold tracking-wide 
                               shadow-md transition"
                    >
                        Comenzar examen
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
        $actividad_id = $a['actividad_id'];

        // Recogemos cualquier campo que empiece por "resp_{actividad_id}"
        $coincidentes = [];
        foreach ($_POST as $k => $v) {
            if (strpos($k, "resp_{$actividad_id}") === 0) {
                $coincidentes[$k] = $v;
            }
        }

        $respuestas[$actividad_id] = $coincidentes ?: null;
    }

    guardarRespuestas($pdo, $intento_id, $respuestas);

    unset($_SESSION['examen_intento']);

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Examen enviado</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="max-w-xl mx-auto mt-20 bg-white p-8 shadow-lg rounded-lg text-center">
            <h1 class="text-2xl font-bold mb-6">Examen enviado correctamente</h1>
            <p class="text-gray-700">Gracias por completar el examen.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($examen['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-3xl mx-auto mt-10 bg-white p-10 shadow-lg rounded-lg">

<h1 class="text-3xl font-bold mb-10"><?= htmlspecialchars($examen['titulo']) ?></h1>

<form method="post">

<?php foreach ($examen['actividades'] as $index => $a): 
    $actividad = cargarActividad($pdo, $a['actividad_id']);
    if (!$actividad) continue;
?>
    <div class="mb-10 pb-10 border-b">

        <h2 class="text-xl font-semibold mb-2">Pregunta <?= $index + 1 ?></h2>

        <div class="mb-4 text-lg leading-relaxed">
            <?= nl2br(htmlspecialchars($actividad['titulo'])) ?>
        </div>

        <?php if (!empty($actividad['descripcion'])): ?>
            <div class="mb-4 text-gray-600 text-sm">
                <?= nl2br(htmlspecialchars($actividad['descripcion'])) ?>
            </div>
        <?php endif; ?>


        <?php if ($actividad['tipo'] === 'verdadero_falso'): ?>

            <label class="block mb-3">
                <input type="radio" name="resp_<?= $a['actividad_id'] ?>" value="verdadero" class="mr-2">
                Verdadero
            </label>
            <label class="block mb-3">
                <input type="radio" name="resp_<?= $a['actividad_id'] ?>" value="falso" class="mr-2">
                Falso
            </label>

        <?php elseif ($actividad['tipo'] === 'opcion_multiple'): ?>

            <?php if (!empty($actividad['opciones'])): ?>
                <?php foreach ($actividad['opciones'] as $op): ?>
                    <label class="block mb-3">
                        <input
                            type="radio"
                            name="resp_<?= $a['actividad_id'] ?>"
                            value="<?= (int)$op['id'] ?>"
                            class="mr-2"
                        >
                        <!-- usamos opcion_html, que es el campo real -->
                        <?= $op['opcion_html'] ?>
                    </label>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-500"><em>Esta pregunta no tiene opciones configuradas.</em></p>
            <?php endif; ?>

        <?php elseif ($actividad['tipo'] === 'respuesta_corta'): ?>

            <textarea
                name="resp_<?= $a['actividad_id'] ?>"
                class="w-full border p-3 rounded"
                rows="4"
            ></textarea>

        <?php elseif ($actividad['tipo'] === 'rellenar_huecos'): ?>

            <?php
                $texto   = $actividad['rh']['enunciado_html'] ?? '';
                $huecos  = json_decode($actividad['rh']['huecos_json'] ?? '[]', true);
                $num     = is_array($huecos) ? count($huecos) : 0;

                for ($i = 1; $i <= $num; $i++) {
                    $input = "<input class='border p-2 rounded w-40 inline-block mx-1' ".
                             "name='resp_{$a['actividad_id']}_{$i}'>";
                    $texto = str_replace('{{' . $i . '}}', $input, $texto);
                }
            ?>

            <div class="leading-relaxed text-lg mb-4">
                <?= $texto ?>
            </div>

        <?php elseif ($actividad['tipo'] === 'emparejar'): ?>

            <?php if (!empty($actividad['pares'])): ?>
                <?php
                    // Creamos un array de "derechas" para los selects
                    $derechas = [];
                    foreach ($actividad['pares'] as $p) {
                        $derechas[] = $p['derecha_html'];
                    }
                    $derechas = array_unique($derechas);
                ?>
                <?php foreach ($actividad['pares'] as $p): ?>
                    <div class="flex items-center gap-4 mb-3">
                        <span class="font-semibold"><?= $p['izquierda_html'] ?></span>
                        →
                        <select
                            name="resp_<?= $a['actividad_id'] ?>_<?= $p['id'] ?>"
                            class="border p-2 rounded"
                        >
                            <option value="">—</option>
                            <?php foreach ($derechas as $der): ?>
                                <option value="<?= htmlspecialchars($der) ?>">
                                    <?= htmlspecialchars($der) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-500"><em>Esta actividad de emparejar no tiene pares configurados.</em></p>
            <?php endif; ?>

        <?php elseif ($actividad['tipo'] === 'tarea'): ?>

            <?php if (!empty($actividad['tarea']['instrucciones'])): ?>
                <div class="mb-3 text-gray-700">
                    <?= nl2br(htmlspecialchars($actividad['tarea']['instrucciones'])) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($actividad['tarea']['perm_texto'])): ?>
                <label class="block mb-3">
                    Redacción:
                    <textarea
                        class="w-full border rounded p-3 mt-2"
                        rows="6"
                        name="resp_<?= $a['actividad_id'] ?>_texto"
                    ></textarea>
                </label>
            <?php endif; ?>

            <?php if (!empty($actividad['tarea']['perm_enlace'])): ?>
                <label class="block mb-3">
                    Enlace:
                    <input
                        class="w-full border p-2 rounded mt-2"
                        name="resp_<?= $a['actividad_id'] ?>_enlace"
                    >
                </label>
            <?php endif; ?>

            <?php if (empty($actividad['tarea']['perm_texto']) && empty($actividad['tarea']['perm_enlace'])): ?>
                <p class="text-sm text-gray-500">
                    <em>Esta tarea no tiene campos habilitados (texto/enlace).</em>
                </p>
            <?php endif; ?>

        <?php endif; ?>

    </div>

<?php endforeach; ?>

<input type="hidden" name="fin_examen" value="1">

<button class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-lg text-lg font-semibold">
    Enviar examen
</button>

</form>

</div>

</body>
</html>
