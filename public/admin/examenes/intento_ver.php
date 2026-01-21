<?php
// /public/admin/examenes/intento_ver.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$pdo = pdo();
$u = current_user();

$intento_id = isset($_GET['intento_id']) ? (int) $_GET['intento_id'] : 0;
if ($intento_id <= 0) {
  http_response_code(400);
  echo "Intento no válido.";
  exit;
}

$examenService = new ExamenService(pdo());

// 1) Cargar intento + examen asociado
$intento = $examenService->getAttempt($intento_id);

if (!$intento) {
  http_response_code(404);
  echo "Intento no encontrado.";
  exit;
}

$mensaje = null;

// 2) Si se envían notas por actividad, guardarlas y recalcular nota total
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_calificacion'])) {
  $notasActividad = $_POST['nota_actividad'] ?? [];
  $total = $examenService->updateAnswerGrades($intento_id, $notasActividad);

  // Recargar datos actualizados
  $intento = $examenService->getAttempt($intento_id);
  $mensaje = "Calificaciones guardadas. Nota total del examen: " . number_format($total, 2, ',', '.');
}

// 3) Cargar actividades del examen (con detalles completos)
$actividades = $examenService->getActivitiesFull((int) $intento['examen_id']);

// 4) Cargar respuestas del intento
$respuestasBrutas = $examenService->getAnswers($intento_id);

$respuestasPorActividad = [];
$puntuacionesPorActividad = [];
$corregidasPorActividad = [];

foreach ($respuestasBrutas as $r) {
  $actividadId = (int) $r['actividad_id'];
  $arr = json_decode($r['respuesta_json'] ?? '[]', true);
  if (!is_array($arr)) {
    $arr = [];
  }
  $respuestasPorActividad[$actividadId] = $arr;
  $puntuacionesPorActividad[$actividadId] = $r['puntuacion'];
  $corregidasPorActividad[$actividadId] = $r['corregida'];
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="max-w-5xl mx-auto">

  <div class="mb-4 flex items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-semibold tracking-tight">Respuestas del intento</h1>
      <p class="text-sm text-slate-500 mt-1">
        Examen: <span class="font-semibold"><?= h($intento['examen_titulo']) ?></span><br>
        Alumno: <span class="font-semibold"><?= h($intento['nombre_alumno'] ?? '—') ?></span>
        &lt;<?= h($intento['email_alumno'] ?? '—') ?>&gt;
      </p>
    </div>
    <div class="flex flex-col items-end gap-2">
      <a href="<?= PUBLIC_URL ?>/admin/examenes/intentos.php?examen_id=<?= (int) $intento['examen_id'] ?>"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
        &larr; Volver a intentos
      </a>
      <div class="text-xs text-slate-600">
        Nota actual:
        <?php if ($intento['nota'] !== null): ?>
          <span class="font-semibold">
            <?= h(number_format((float) $intento['nota'], 2, ',', '.')) ?>
          </span>
        <?php else: ?>
          <span class="font-semibold text-slate-400">—</span>
        <?php endif; ?>
        <?php if (!empty($intento['corregido'])): ?>
          <span
            class="ml-2 inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
            Corregido
          </span>
        <?php else: ?>
          <span
            class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200">
            Pendiente
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($mensaje): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
      <?= h($mensaje) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($actividades)): ?>
    <div
      class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-slate-500 text-sm">
      Este examen no tiene actividades asociadas.
    </div>
  <?php else: ?>

    <form method="post" class="space-y-6">
      <!-- Bloque superior de guardado -->
      <div class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
        <span class="text-sm font-medium text-slate-700">
          Ajusta las notas por actividad y guarda para recalcular la nota total del examen.
        </span>
        <button type="submit" name="guardar_calificacion"
          class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
          Guardar calificaciones
        </button>
      </div>

      <?php foreach ($actividades as $idx => $a): ?>
        <?php
        $actividadId = (int) $a['id'];
        $tipo = $a['tipo'];
        $titulo = $a['titulo'] ?? '';
        $descripcion = $a['descripcion'] ?? '';
        $res = $respuestasPorActividad[$actividadId] ?? [];
        $puntuacion = $puntuacionesPorActividad[$actividadId] ?? null;
        $corregida = $corregidasPorActividad[$actividadId] ?? null;
        ?>

        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">
                Pregunta <?= $idx + 1 ?> · <?= h($tipo) ?>
              </div>
              <h2 class="text-base font-semibold text-slate-900">
                <?= h($titulo) ?>
              </h2>
              <?php if ($descripcion): ?>
                <p class="mt-1 text-sm text-slate-600">
                  <?= nl2br(h($descripcion)) ?>
                </p>
              <?php endif; ?>
            </div>

            <?php if ($corregida !== null): ?>
              <div class="text-right">
                <?php if ($puntuacion !== null): ?>
                  <div class="text-xs text-slate-600 mb-1">
                    Puntuación actual:
                    <span class="font-semibold">
                      <?= h(number_format((float) $puntuacion, 2, ',', '.')) ?>
                    </span>
                  </div>
                <?php endif; ?>

                <?php if ((float) ($puntuacion ?? 0) > 0): ?>
                  <span
                    class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    Correcta / con puntos
                  </span>
                <?php elseif ($puntuacion !== null): ?>
                  <span
                    class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-rose-200">
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
                $valor = reset($res);
                $texto = ($valor === 'verdadero') ? 'Verdadero' : (($valor === 'falso') ? 'Falso' : $valor);
                ?>
                <p class="text-sm text-slate-800">
                  <?= h((string) $texto) ?>
                </p>

              <?php elseif ($tipo === 'opcion_multiple'): ?>

                <?php
                $valor = reset($res); // id de la opción
                $opcionId = (int) $valor;
                $textoOpcion = null;
                if ($opcionId && isset($a['om_options'])) {
                  foreach($a['om_options'] as $op) {
                    if ((int)$op['id'] === $opcionId) {
                       $textoOpcion = $op['opcion_html'];
                       break;
                    }
                  }
                }
                ?>
                <?php if ($textoOpcion !== null): ?>
                  <div class="text-sm text-slate-800">
                    <?= $textoOpcion ?>
                  </div>
                <?php else: ?>
                  <p class="text-sm text-slate-500"><em>Marcó opción ID <?= h((string) $opcionId) ?> (no encontrada en BD).</em>
                  </p>
                <?php endif; ?>

              <?php elseif ($tipo === 'respuesta_corta'): ?>

                <?php
                $valor = reset($res);
                ?>
                <div
                  class="whitespace-pre-wrap rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800">
                  <?= h((string) $valor) ?>
                </div>

              <?php elseif ($tipo === 'rellenar_huecos'): ?>

                <?php
                $huecos = [];
                foreach ($res as $k => $v) {
                  if (preg_match('/^resp_' . $actividadId . '_(\d+)$/', (string) $k, $m)) {
                    $idxHueco = (int) $m[1];
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
                        <span class="font-medium">Hueco <?= (int) $n ?>:</span>
                        <?= h((string) $val) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

              <?php elseif ($tipo === 'emparejar'): ?>

                <?php
                $paresActividad = json_decode($a['pares_json'] ?? '[]', true);
                if (!$paresActividad) {
                  echo '<p class="text-sm text-slate-500"><em>No hay pares configurados en BD.</em></p>';
                } else {
                  echo '<div class="space-y-1 text-sm text-slate-800">';
                  foreach ($paresActividad as $idxP => $p) {
                    $kResp = "resp_{$actividadId}_{$idxP}";
                    $derechaElegida = $res[$kResp] ?? '';
                    ?>
                    <div class="flex flex-wrap items-start gap-2">
                       <div class="font-medium">
                        <?= $p[0] ?>
                       </div>
                       <div>→</div>
                       <div class="text-slate-800">
                        <?= $derechaElegida !== '' ? h((string) $derechaElegida) : '<span class="text-slate-400"><em>Sin respuesta</em></span>' ?>
                       </div>
                    </div>
                    <?php
                  }
                  echo '</div>';
                }
                ?>

              <?php elseif ($tipo === 'tarea'): ?>

                <?php
                $texto = $res['texto'] ?? null;
                $enlace = $res['enlace'] ?? null;
                ?>

                <?php if ($texto !== null && $texto !== ''): ?>
                  <div class="mb-3">
                    <div class="text-xs font-semibold text-slate-600 mb-1">Texto enviado</div>
                    <div
                      class="whitespace-pre-wrap rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800">
                      <?= h((string) $texto) ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if ($enlace !== null && $enlace !== ''): ?>
                  <div class="mb-1">
                    <div class="text-xs font-semibold text-slate-600 mb-1">Enlace enviado</div>
                    <a href="<?= h((string) $enlace) ?>" target="_blank" class="text-sm text-indigo-600 hover:underline">
                      <?= h((string) $enlace) ?>
                    </a>
                  </div>
                <?php endif; ?>

                <?php if (($texto === null || $texto === '') && ($enlace === null || $enlace === '')): ?>
                  <p class="text-sm text-slate-500"><em>Sin contenido en la tarea.</em></p>
                <?php endif; ?>

              <?php else: ?>

                <!-- Tipo desconocido o no contemplado -->
                <p class="text-sm text-slate-500">
                  <em>Tipo de actividad no contemplado para mostrar las respuestas (<?= h($tipo) ?>).</em>
                </p>

              <?php endif; ?>

            <?php endif; ?>

          </div>

          <!-- SOLUCIÓN CORRECTA / CRITERIOS -->
          <div class="mt-4 border-t border-slate-100 pt-3">
            <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
              Solución correcta / criterios
            </h3>

            <?php if ($tipo === 'verdadero_falso'): ?>
              <?php
              $corr = $a['respuesta_correcta'] ?? null;
              $textoCorr = ($corr === 'verdadero') ? 'Verdadero' : (($corr === 'falso') ? 'Falso' : null);
              ?>
              <?php if ($textoCorr !== null): ?>
                <p class="text-sm text-slate-800">
                  Respuesta correcta: <span class="font-semibold"><?= h($textoCorr) ?></span>
                </p>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay respuesta correcta definida en la BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'opcion_multiple'): ?>
              <?php
              $corrs = [];
              if (isset($a['om_options'])) {
                foreach ($a['om_options'] as $op) {
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
              <?php
              $sol = json_decode($a['huecos_json'] ?? '[]', true);
              if (!is_array($sol)) $sol = [];
              ?>
              <?php if ($sol): ?>
                <ul class="text-sm text-slate-800 space-y-1">
                  <?php foreach ($sol as $n => $textoSol): ?>
                    <li>
                      <span class="font-medium">Hueco <?= (int) ($n + 1) ?>:</span>
                      <?= h((string) $textoSol) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay soluciones de huecos definidas en la BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'emparejar'): ?>

              <?php
              $paresActividad = json_decode($a['pares_json'] ?? '[]', true);
              ?>
              <?php if ($paresActividad): ?>
                <div class="space-y-1 text-sm text-slate-800">
                  <div class="font-semibold mb-1">Pares correctos:</div>
                  <?php foreach ($paresActividad as $p): ?>
                    <div class="flex flex-wrap items-start gap-2">
                       <div class="font-medium">
                        <?= $p[0] ?>
                       </div>
                       <div>→</div>
                       <div class="text-slate-800">
                        <?= $p[1] ?>
                       </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="text-sm text-slate-500"><em>No hay pares configurados en BD.</em></p>
              <?php endif; ?>

            <?php elseif ($tipo === 'respuesta_corta'): ?>
                <?php
                $modo = $a['modo'] ?? 'palabras_clave';
                $claves = json_decode($a['palabras_clave_json'] ?? '[]', true);
                if (!is_array($claves)) $claves = [];
                $minCoin = $a['coincidencia_minima'] ?? null;
                ?>
                <div class="text-sm text-slate-800 space-y-1">
                  <div>
                    <span class="font-semibold">Modo de corrección:</span>
                    <?= h($modo) ?>
                  </div>
                  <?php if ($claves): ?>
                    <div>
                      <span class="font-semibold">Palabras clave esperadas:</span>
                      <ul class="list-disc pl-5">
                        <?php foreach ($claves as $c): ?>
                          <li><?= h((string) $c) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                  <?php if ($minCoin !== null): ?>
                    <div class="text-xs text-slate-600">
                      Coincidencia mínima requerida: <?= (int) $minCoin ?> palabra(s) clave.
                    </div>
                  <?php endif; ?>
                </div>

            <?php elseif ($tipo === 'tarea'): ?>
              <p class="text-sm text-slate-500">
                <em>Esta tarea no tiene una respuesta única correcta. Se corrige según el criterio del profesor.</em>
              </p>

            <?php else: ?>
              <p class="text-sm text-slate-500">
                <em>No hay solución configurada para este tipo de actividad (<?= h($tipo) ?>).</em>
              </p>
            <?php endif; ?>
          </div>

          <!-- Bloque de nota manual/ajustable por actividad -->
          <div class="mt-4 border-t border-slate-100 pt-3">
            <label class="text-xs font-medium text-slate-700">
              Nota para esta actividad:
              <input type="text" name="nota_actividad[<?= (int) $actividadId ?>]"
                value="<?= $puntuacion !== null ? h((string) $puntuacion) : '' ?>"
                class="ml-2 w-20 rounded-md border border-slate-300 px-2 py-1 text-xs" placeholder="0">
            </label>
            <p class="mt-1 text-[11px] text-slate-400">
              Si dejas el campo vacío, contará como sin puntuar (null). El total del examen es la suma de todas las
              puntuaciones.
            </p>
          </div>

        </div>
      <?php endforeach; ?>

      <div>
        <button type="submit" name="guardar_calificacion"
          class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
          Guardar calificaciones y recalcular nota
        </button>
      </div>

    </form>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>