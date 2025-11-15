<?php
// /public/admin/examenes/intento_ver.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$pdo = pdo();
$u   = current_user();

$intento_id = isset($_GET['intento_id']) ? (int)$_GET['intento_id'] : 0;
if ($intento_id <= 0) {
    http_response_code(400);
    echo "Intento no válido.";
    exit;
}

// 1) Cargar intento + examen asociado
$st = $pdo->prepare("
    SELECT 
        ei.*,
        e.id   AS examen_id,
        e.titulo AS examen_titulo,
        e.descripcion AS examen_descripcion
    FROM examen_intentos ei
    JOIN examenes e ON e.id = ei.examen_id
    WHERE ei.id = ?
");
$st->execute([$intento_id]);
$intento = $st->fetch(PDO::FETCH_ASSOC);

if (!$intento) {
    http_response_code(404);
    echo "Intento no encontrado.";
    exit;
}

// 2) Cargar actividades del examen
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
$st2->execute([(int)$intento['examen_id']]);
$actividades = $st2->fetchAll(PDO::FETCH_ASSOC);

// Índice por actividad_id para acceso rápido
$actividadesPorId = [];
foreach ($actividades as $act) {
    $actividadesPorId[$act['actividad_id']] = $act;
}

// 3) Cargar respuestas del intento
$st3 = $pdo->prepare("
    SELECT actividad_id, respuesta_json
    FROM examen_respuestas
    WHERE intento_id = ?
");
$st3->execute([$intento_id]);
$respuestasBrutas = $st3->fetchAll(PDO::FETCH_ASSOC);

$respuestasPorActividad = [];
foreach ($respuestasBrutas as $r) {
    $actividadId = (int)$r['actividad_id'];
    $arr = json_decode($r['respuesta_json'] ?? '[]', true);
    if (!is_array($arr)) {
        $arr = [];
    }
    $respuestasPorActividad[$actividadId] = $arr;
}

// 4) Para algunos tipos necesitamos más datos (ej. emparejar, opción múltiple)
$paresPorActividad = [];
$opcionesPorId     = [];

// Pre-cargar pares de emparejar si hay actividades de ese tipo
$hayEmparejar = false;
$hayOM        = false;

foreach ($actividades as $a) {
    if ($a['tipo'] === 'emparejar') {
        $hayEmparejar = true;
    }
    if ($a['tipo'] === 'opcion_multiple') {
        $hayOM = true;
    }
}

if ($hayEmparejar) {
    $st4 = $pdo->prepare("
        SELECT actividad_id, id, izquierda_html, derecha_html
        FROM actividades_emp_pares
        WHERE actividad_id = ?
        ORDER BY orden_izq ASC, id ASC
    ");
    foreach ($actividades as $a) {
        if ($a['tipo'] !== 'emparejar') continue;
        $aid = (int)$a['actividad_id'];
        $st4->execute([$aid]);
        $paresPorActividad[$aid] = $st4->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($hayOM) {
    // mapearemos por id de opción, no por actividad
    $idsActividadOM = [];
    foreach ($actividades as $a) {
        if ($a['tipo'] === 'opcion_multiple') {
            $idsActividadOM[] = (int)$a['actividad_id'];
        }
    }
    if ($idsActividadOM) {
        $in = implode(',', array_fill(0, count($idsActividadOM), '?'));
        $sqlOM = "
            SELECT id, actividad_id, opcion_html
            FROM actividades_om_opciones
            WHERE actividad_id IN ($in)
        ";
        $st5 = $pdo->prepare($sqlOM);
        $st5->execute($idsActividadOM);
        $rowsOM = $st5->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsOM as $row) {
            $opcionesPorId[(int)$row['id']] = $row;
        }
    }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="max-w-5xl mx-auto">

  <div class="mb-4 flex items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-semibold tracking-tight">Respuestas del intento</h1>
      <p class="text-sm text-slate-500 mt-1">
        Examen: <span class="font-semibold"><?= htmlspecialchars($intento['examen_titulo']) ?></span><br>
        Alumno: <span class="font-semibold"><?= htmlspecialchars($intento['nombre_alumno'] ?? '—') ?></span>
        &lt;<?= htmlspecialchars($intento['email_alumno'] ?? '—') ?>&gt;
      </p>
    </div>
    <div class="flex gap-2">
      <a
        href="<?= PUBLIC_URL ?>/admin/examenes/intentos.php?examen_id=<?= (int)$intento['examen_id'] ?>"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
      >
        &larr; Volver a intentos
      </a>
    </div>
  </div>

  <?php if (empty($actividades)): ?>
    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-slate-500 text-sm">
      Este examen no tiene actividades asociadas.
    </div>
  <?php else: ?>

    <div class="space-y-6">

      <?php foreach ($actividades as $idx => $a): ?>
        <?php
          $actividadId = (int)$a['actividad_id'];
          $tipo        = $a['tipo'];
          $titulo      = $a['titulo'] ?? '';
          $descripcion = $a['descripcion'] ?? '';
          $res         = $respuestasPorActividad[$actividadId] ?? [];
        ?>

        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">
                Pregunta <?= $idx + 1 ?> · <?= htmlspecialchars($tipo) ?>
              </div>
              <h2 class="text-base font-semibold text-slate-900">
                <?= htmlspecialchars($titulo) ?>
              </h2>
              <?php if ($descripcion): ?>
                <p class="mt-1 text-sm text-slate-600">
                  <?= nl2br(htmlspecialchars($descripcion)) ?>
                </p>
              <?php endif; ?>
            </div>
          </div>

          <div class="mt-3 border-t border-slate-100 pt-3">
            <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
              Respuesta del alumno
            </h3>

            <?php if (!$res): ?>
              <p class="text-sm text-slate-500"><em>Sin respuesta.</em></p>

            <?php else: ?>

              <?php if ($tipo === 'verdadero_falso'): ?>

                <?php
                  // Esperamos algo como ['resp_<id>' => 'verdadero'/'falso']
                  $valor = reset($res);
                  $texto = ($valor === 'verdadero') ? 'Verdadero' : (($valor === 'falso') ? 'Falso' : $valor);
                ?>
                <p class="text-sm text-slate-800">
                  <?= htmlspecialchars((string)$texto) ?>
                </p>

              <?php elseif ($tipo === 'opcion_multiple'): ?>

                <?php
                  $valor = reset($res); // debería ser el id de la opción
                  $opcionId = (int)$valor;
                  $textoOpcion = null;
                  if ($opcionId && isset($opcionesPorId[$opcionId])) {
                      $textoOpcion = $opcionesPorId[$opcionId]['opcion_html'];
                  }
                ?>
                <?php if ($textoOpcion !== null): ?>
                  <div class="text-sm text-slate-800">
                    <?= $textoOpcion ?>
                  </div>
                <?php else: ?>
                  <p class="text-sm text-slate-500"><em>Marcó opción ID <?= htmlspecialchars((string)$opcionId) ?> (no encontrada en BD).</em></p>
                <?php endif; ?>

              <?php elseif ($tipo === 'respuesta_corta'): ?>

                <?php
                  $valor = reset($res);
                ?>
                <div class="whitespace-pre-wrap rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800">
                  <?= htmlspecialchars((string)$valor) ?>
                </div>

              <?php elseif ($tipo === 'rellenar_huecos'): ?>

                <?php
                  // Esperamos claves tipo resp_<id>_1, resp_<id>_2, ...
                  $huecos = [];
                  foreach ($res as $k => $v) {
                      if (preg_match('/^resp_' . $actividadId . '_(\d+)$/', (string)$k, $m)) {
                          $idxHueco = (int)$m[1];
                          $huecos[$idxHueco] = $v;
                      }
                  }
                  ksort($huecos);
                ?>

                <?php if (!$huecos): ?>
                  <p class="text-sm text-slate-500"><em>No se han detectado huecos respondidos.</em></p>
                <?php else: ?>
                  <ul class="text-sm text-slate-800 space-y-1">
                    <?php foreach ($huecos as $n => $val): ?>
                      <li>
                        <span class="font-medium">Hueco <?= (int)$n ?>:</span>
                        <?= htmlspecialchars((string)$val) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

              <?php elseif ($tipo === 'emparejar'): ?>

                <?php
                  // Claves esperadas: resp_<actividadId>_<parId> => derecha elegida
                  $paresActividad = $paresPorActividad[$actividadId] ?? [];
                  if (!$paresActividad) {
                      echo '<p class="text-sm text-slate-500"><em>No hay pares configurados en BD.</em></p>';
                  } else {
                      echo '<div class="space-y-1 text-sm text-slate-800">';
                      foreach ($paresActividad as $p) {
                          $parId = (int)$p['id'];
                          $kResp = "resp_{$actividadId}_{$parId}";
                          $derechaElegida = $res[$kResp] ?? '';
                          ?>
                          <div class="flex flex-wrap items-start gap-2">
                            <div class="font-medium">
                              <?= $p['izquierda_html'] ?>
                            </div>
                            <div>→</div>
                            <div class="text-slate-800">
                              <?= $derechaElegida !== '' ? htmlspecialchars((string)$derechaElegida) : '<span class="text-slate-400"><em>Sin respuesta</em></span>' ?>
                            </div>
                          </div>
                          <?php
                      }
                      echo '</div>';
                  }
                ?>

              <?php elseif ($tipo === 'tarea'): ?>

                <?php
                  $texto  = $res["resp_{$actividadId}_texto"]  ?? null;
                  $enlace = $res["resp_{$actividadId}_enlace"] ?? null;
                ?>

                <?php if ($texto !== null && $texto !== ''): ?>
                  <div class="mb-3">
                    <div class="text-xs font-semibold text-slate-600 mb-1">Texto enviado</div>
                    <div class="whitespace-pre-wrap rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800">
                      <?= htmlspecialchars((string)$texto) ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if ($enlace !== null && $enlace !== ''): ?>
                  <div class="mb-1">
                    <div class="text-xs font-semibold text-slate-600 mb-1">Enlace enviado</div>
                    <a href="<?= htmlspecialchars((string)$enlace) ?>" target="_blank" class="text-sm text-indigo-600 hover:underline">
                      <?= htmlspecialchars((string)$enlace) ?>
                    </a>
                  </div>
                <?php endif; ?>

                <?php if (($texto === null || $texto === '') && ($enlace === null || $enlace === '')): ?>
                  <p class="text-sm text-slate-500"><em>Sin contenido en la tarea.</em></p>
                <?php endif; ?>

              <?php else: ?>

                <!-- Tipo desconocido o no contemplado -->
                <p class="text-sm text-slate-500">
                  <em>Tipo de actividad no contemplado para mostrar las respuestas (<?= htmlspecialchars($tipo) ?>).</em>
                </p>

              <?php endif; // switch tipo ?>

            <?php endif; // si/no respuesta ?>

          </div>
        </div>
      <?php endforeach; ?>

    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
