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
        e.id        AS examen_id,
        e.titulo    AS examen_titulo,
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

$mensaje = null;

// 2) Si se envían notas por actividad, guardarlas y recalcular nota total
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_calificacion'])) {

    $notasActividad = $_POST['nota_actividad'] ?? [];

    foreach ($notasActividad as $actIdStr => $notaStr) {
        $actividadId = (int)$actIdStr;
        $notaStr     = trim((string)$notaStr);

        if ($actividadId <= 0) {
            continue;
        }

        if ($notaStr === '') {
            // Si el campo se deja vacío, consideramos puntuación null y sin corregir
            $puntuacion = null;
            $corregida  = 0;
        } else {
            $puntuacion = str_replace(',', '.', $notaStr);
            $puntuacion = (float)$puntuacion;
            $puntuacion = round($puntuacion, 2);
            $corregida  = 1;
        }

        $stUp = $pdo->prepare("
            UPDATE examen_respuestas
            SET puntuacion = :puntuacion,
                corregida  = :corregida
            WHERE intento_id = :intento_id
              AND actividad_id = :actividad_id
        ");
        $stUp->execute([
            ':puntuacion'   => $puntuacion,
            ':corregida'    => $corregida,
            ':intento_id'   => $intento_id,
            ':actividad_id' => $actividadId,
        ]);
    }

    // Recalcular nota total del examen como suma de las puntuaciones
    $stSum = $pdo->prepare("
        SELECT SUM(COALESCE(puntuacion,0)) AS total
        FROM examen_respuestas
        WHERE intento_id = ?
    ");
    $stSum->execute([$intento_id]);
    $total = (float)($stSum->fetchColumn() ?? 0);

    $stUpInt = $pdo->prepare("
        UPDATE examen_intentos
        SET nota = :nota,
            corregido = 1
        WHERE id = :id
    ");
    $stUpInt->execute([
        ':nota' => $total,
        ':id'   => $intento_id,
    ]);

    $intento['nota']      = $total;
    $intento['corregido'] = 1;

    $mensaje = "Calificaciones guardadas. Nota total del examen: " . number_format($total, 2, ',', '.');
}

// 3) Cargar actividades del examen
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

// Índice por actividad_id para acceso rápido (por si lo necesitas más adelante)
$actividadesPorId = [];
foreach ($actividades as $act) {
    $actividadesPorId[$act['actividad_id']] = $act;
}

// 4) Cargar respuestas del intento (incluyendo puntuación y corregida)
$st3 = $pdo->prepare("
    SELECT actividad_id, respuesta_json, puntuacion, corregida
    FROM examen_respuestas
    WHERE intento_id = ?
");
$st3->execute([$intento_id]);
$respuestasBrutas = $st3->fetchAll(PDO::FETCH_ASSOC);

$respuestasPorActividad    = [];
$puntuacionesPorActividad  = [];
$corregidasPorActividad    = [];

foreach ($respuestasBrutas as $r) {
    $actividadId = (int)$r['actividad_id'];
    $arr = json_decode($r['respuesta_json'] ?? '[]', true);
    if (!is_array($arr)) {
        $arr = [];
    }
    $respuestasPorActividad[$actividadId]   = $arr;
    $puntuacionesPorActividad[$actividadId] = $r['puntuacion'];
    $corregidasPorActividad[$actividadId]   = $r['corregida'];
}

// 5) Cargar datos auxiliares según tipo (para mostrar soluciones correctas)
$paresPorActividad        = [];
$opcionesPorId            = [];
$opcionesPorActividad     = [];
$vfPorActividad           = [];
$rhPorActividad           = [];
$rcPorActividad           = [];

$hayEmparejar = false;
$hayOM        = false;
$hayVF        = false;
$hayRH        = false;
$hayRC        = false;

$idsActividadOM = [];
$idsActividadVF = [];
$idsActividadRH = [];
$idsActividadRC = [];

foreach ($actividades as $a) {
    $aid  = (int)$a['actividad_id'];
    $tipo = $a['tipo'];

    if ($tipo === 'emparejar') {
        $hayEmparejar = true;
    }
    if ($tipo === 'opcion_multiple') {
        $hayOM = true;
        $idsActividadOM[] = $aid;
    }
    if ($tipo === 'verdadero_falso') {
        $hayVF = true;
        $idsActividadVF[] = $aid;
    }
    if ($tipo === 'rellenar_huecos') {
        $hayRH = true;
        $idsActividadRH[] = $aid;
    }
    if ($tipo === 'respuesta_corta') {
        $hayRC = true;
        $idsActividadRC[] = $aid;
    }
}

// Emparejar → pares izquierda/derecha
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

// Opción múltiple → opciones + cuáles son correctas
if ($hayOM && $idsActividadOM) {
    $in    = implode(',', array_fill(0, count($idsActividadOM), '?'));
    $sqlOM = "
        SELECT id, actividad_id, opcion_html, es_correcta
        FROM actividades_om_opciones
        WHERE actividad_id IN ($in)
    ";
    $st5 = $pdo->prepare($sqlOM);
    $st5->execute($idsActividadOM);
    $rowsOM = $st5->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsOM as $row) {
        $opcionesPorId[(int)$row['id']] = $row;
        $aid = (int)$row['actividad_id'];
        if (!isset($opcionesPorActividad[$aid])) {
            $opcionesPorActividad[$aid] = [];
        }
        $opcionesPorActividad[$aid][] = $row;
    }
}

// Verdadero/Falso → respuesta_correcta
if ($hayVF && $idsActividadVF) {
    $in   = implode(',', array_fill(0, count($idsActividadVF), '?'));
    $sqlV = "
        SELECT actividad_id, respuesta_correcta
        FROM actividades_vf
        WHERE actividad_id IN ($in)
    ";
    $stV = $pdo->prepare($sqlV);
    $stV->execute($idsActividadVF);
    $rowsVF = $stV->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsVF as $row) {
        $vfPorActividad[(int)$row['actividad_id']] = $row;
    }
}

// Rellenar huecos → soluciones en huecos_json
if ($hayRH && $idsActividadRH) {
    $in   = implode(',', array_fill(0, count($idsActividadRH), '?'));
    $sqlH = "
        SELECT actividad_id, huecos_json
        FROM actividades_rh
        WHERE actividad_id IN ($in)
    ";
    $stH = $pdo->prepare($sqlH);
    $stH->execute($idsActividadRH);
    $rowsRH = $stH->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsRH as $row) {
        $rhPorActividad[(int)$row['actividad_id']] = $row;
    }
}

// Respuesta corta → criterios de corrección (palabras clave / regex)
if ($hayRC && $idsActividadRC) {
    $in   = implode(',', array_fill(0, count($idsActividadRC), '?'));
    $sqlR = "
        SELECT actividad_id, modo, palabras_clave_json, coincidencia_minima
        FROM actividades_rc
        WHERE actividad_id IN ($in)
    ";
    $stR = $pdo->prepare($sqlR);
    $stR->execute($idsActividadRC);
    $rowsRC = $stR->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsRC as $row) {
        $rcPorActividad[(int)$row['actividad_id']] = $row;
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
    <div class="flex flex-col items-end gap-2">
      <a
        href="<?= PUBLIC_URL ?>/admin/examenes/intentos.php?examen_id=<?= (int)$intento['examen_id'] ?>"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
      >
        &larr; Volver a intentos
      </a>
      <div class="text-xs text-slate-600">
        Nota actual:
        <?php if ($intento['nota'] !== null): ?>
          <span class="font-semibold">
            <?= htmlspecialchars(number_format((float)$intento['nota'], 2, ',', '.')) ?>
          </span>
        <?php else: ?>
          <span class="font-semibold text-slate-400">—</span>
        <?php endif; ?>
        <?php if (!empty($intento['corregido'])): ?>
          <span class="ml-2 inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
            Corregido
          </span>
        <?php else: ?>
          <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200">
            Pendiente
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($mensaje): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
      <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($actividades)): ?>
    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-slate-500 text-sm">
      Este examen no tiene actividades asociadas.
    </div>
  <?php else: ?>

    <form method="post" class="space-y-6">
      <!-- Bloque superior de guardado -->
      <div class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
        <span class="text-sm font-medium text-slate-700">
          Ajusta las notas por actividad y guarda para recalcular la nota total del examen.
        </span>
        <button
          type="submit"
          name="guardar_calificacion"
          class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
        >
          Guardar calificaciones
        </button>
      </div>

      <?php foreach ($actividades as $idx => $a): ?>
        <?php
          $actividadId = (int)$a['actividad_id'];
          $tipo        = $a['tipo'];
          $titulo      = $a['titulo'] ?? '';
          $descripcion = $a['descripcion'] ?? '';
          $res         = $respuestasPorActividad[$actividadId] ?? [];
          $puntuacion  = $puntuacionesPorActividad[$actividadId] ?? null;
          $corregida   = $corregidasPorActividad[$actividadId] ?? null;
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

            <?php if ($corregida !== null): ?>
              <div class="text-right">
                <?php if ($puntuacion !== null): ?>
                  <div class="text-xs text-slate-600 mb-1">
                    Puntuación actual:
                    <span class="font-semibold">
                      <?= htmlspecialchars(number_format((float)$puntuacion, 2, ',', '.')) ?>
                    </span>
                  </div>
                <?php endif; ?>

                <?php if ((float)($puntuacion ?? 0) > 0): ?>
                  <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    Correcta / con puntos
                  </span>
                <?php elseif ($puntuacion !== null): ?>
                  <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-rose-200">
                    Incorrecta / 0 puntos
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- RESPUESTA DEL ALUMNO -->
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
                  // En online.php guardamos algo como ['texto' => ..., 'enlace' => ...]
                  $texto  = $res['texto']  ?? null;
                  $enlace = $res['enlace'] ?? null;
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

          <!-- SOLUCIÓN CORRECTA / CRITERIOS -->
          <div class="mt-4 border-t border-slate-100 pt-3">
            <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
              Solución correcta / criterios
            </h3>

            <?php if ($tipo === 'verdadero_falso'): ?>

              <?php if (isset($vfPorActividad[$actividadId])): ?>
                <?php
                  $corr = $vfPorActividad[$actividadId]['respuesta_correcta'] ?? null;
                  $textoCorr = ($corr === 'verdadero') ? 'Verdadero' : (($corr === 'falso') ? 'Falso' : null);
                ?>
                <?php if ($textoCorr !== null): ?>
                  <p class="text-sm text-slate-800">
                    Respuesta correcta: <span class="font-semibold"><?= htmlspecialchars($textoCorr) ?></span>
                  </p>
                <?php else: ?>
                  <p class="text-sm text-slate-500"><em>No hay respuesta correcta definida en la BD.</em></p>
                <?php endif; ?>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay respuesta correcta definida en la BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'opcion_multiple'): ?>

              <?php
                $corrs = [];
                if (isset($opcionesPorActividad[$actividadId])) {
                    foreach ($opcionesPorActividad[$actividadId] as $op) {
                        if (!empty($op['es_correcta'])) {
                            $corrs[] = $op['opcion_html'];
                        }
                    }
                }
              ?>

              <?php if ($corrs): ?>
                <div class="text-sm text-slate-800 space-y-1">
                  <div class="font-semibold mb-1">Opciones correctas:</div>
                  <ul class="list-disc pl-5 space-y-1">
                    <?php foreach ($corrs as $txt): ?>
                      <li><?= $txt ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay opciones marcadas como correctas en la BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'rellenar_huecos'): ?>

              <?php if (isset($rhPorActividad[$actividadId])): ?>
                <?php
                  $hjson = $rhPorActividad[$actividadId]['huecos_json'] ?? '[]';
                  $sol   = json_decode($hjson, true);
                  if (!is_array($sol)) {
                      $sol = [];
                  }
                ?>
                <?php if ($sol): ?>
                  <ul class="text-sm text-slate-800 space-y-1">
                    <?php foreach ($sol as $n => $textoSol): ?>
                      <li>
                        <span class="font-medium">Hueco <?= (int)($n + 1) ?>:</span>
                        <?= htmlspecialchars((string)$textoSol) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="text-sm text-slate-500"><em>No hay soluciones de huecos definidas en la BD.</em></p>
                <?php endif; ?>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay soluciones de huecos definidas en la BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'emparejar'): ?>

              <?php
                $paresActividad = $paresPorActividad[$actividadId] ?? [];
              ?>
              <?php if ($paresActividad): ?>
                <div class="space-y-1 text-sm text-slate-800">
                  <div class="font-semibold mb-1">Pares correctos:</div>
                  <?php foreach ($paresActividad as $p): ?>
                    <div class="flex flex-wrap items-start gap-2">
                      <div class="font-medium">
                        <?= $p['izquierda_html'] ?>
                      </div>
                      <div>→</div>
                      <div class="text-slate-800">
                        <?= $p['derecha_html'] ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay pares configurados en BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'respuesta_corta'): ?>

              <?php if (isset($rcPorActividad[$actividadId])): ?>
                <?php
                  $cfg   = $rcPorActividad[$actividadId];
                  $modo  = $cfg['modo'] ?? 'palabras_clave';
                  $pcRaw = $cfg['palabras_clave_json'] ?? '[]';
                  $claves = json_decode($pcRaw, true);
                  if (!is_array($claves)) {
                      $claves = [];
                  }
                  $minCoin = $cfg['coincidencia_minima'] ?? null;
                ?>
                <div class="text-sm text-slate-800 space-y-1">
                  <div>
                    <span class="font-semibold">Modo de corrección:</span>
                    <?= htmlspecialchars($modo) ?>
                  </div>
                  <?php if ($claves): ?>
                    <div>
                      <span class="font-semibold">Palabras clave esperadas:</span>
                      <ul class="list-disc pl-5">
                        <?php foreach ($claves as $c): ?>
                          <li><?= htmlspecialchars((string)$c) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                  <?php if ($minCoin !== null): ?>
                    <div class="text-xs text-slate-600">
                      Coincidencia mínima requerida: <?= (int)$minCoin ?> palabra(s) clave.
                    </div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay criterios de corrección configurados en BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'tarea'): ?>

              <p class="text-sm text-slate-500">
                <em>Esta tarea no tiene una respuesta única correcta. Se corrige según el criterio del profesor.</em>
              </p>

            <?php else: ?>

              <p class="text-sm text-slate-500">
                <em>No hay solución configurada para este tipo de actividad (<?= htmlspecialchars($tipo) ?>).</em>
              </p>

            <?php endif; ?>
          </div>

          <!-- Bloque de nota manual/ajustable por actividad -->
          <div class="mt-4 border-t border-slate-100 pt-3">
            <label class="text-xs font-medium text-slate-700">
              Nota para esta actividad:
              <input
                type="text"
                name="nota_actividad[<?= (int)$actividadId ?>]"
                value="<?= $puntuacion !== null ? htmlspecialchars((string)$puntuacion) : '' ?>"
                class="ml-2 w-20 rounded-md border border-slate-300 px-2 py-1 text-xs"
                placeholder="0"
              >
            </label>
            <p class="mt-1 text-[11px] text-slate-400">
              Si dejas el campo vacío, contará como sin puntuar (null). El total del examen es la suma de todas las puntuaciones.
            </p>
          </div>

        </div>
      <?php endforeach; ?>

      <div>
        <button
          type="submit"
          name="guardar_calificacion"
          class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
        >
          Guardar calificaciones y recalcular nota
        </button>
      </div>

    </form>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
