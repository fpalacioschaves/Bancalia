<?php
// /public/admin/examenes/preview_print.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

// =======================
//   ID DEL EXAMEN
// =======================
$examenId = (int) ($_GET['id'] ?? 0);
if ($examenId <= 0) {
  http_response_code(400);
  exit('ID de examen inválido.');
}

$examenService = new ExamenService(pdo());

// =======================
//   CARGAR EXAMEN
// =======================
$examen = $examenService->findFull($examenId);

if (!$examen) {
  http_response_code(404);
  exit('Examen no encontrado.');
}

// =======================
//   CARGAR ACTIVIDADES
// =======================
$actividades = $examenService->getActivitiesFull($examenId);

require_once __DIR__ . '/../../../partials/header.php';
?>

<style>
  /* Ajustes básicos para impresión */
  @media print {

    header,
    nav,
    .no-print {
      display: none !important;
    }

    body {
      background: #ffffff !important;
    }

    main {
      max-width: 100% !important;
      padding: 0 1.5cm !important;
    }
  }
</style>

<div class="no-print mb-4 flex items-center justify-between">
  <h1 class="text-xl font-semibold tracking-tight">Versión imprimible del examen</h1>
  <div class="flex gap-2">
    <a href="<?= PUBLIC_URL ?>/admin/examenes/preview.php?id=<?= (int) $examen['id'] ?>"
      class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
      Volver a vista previa
    </a>
    <button type="button" onclick="window.print()"
      class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
      Imprimir / PDF
    </button>
  </div>
</div>

<!-- CABECERA DEL EXAMEN (modo alumno) -->
<div class="mb-6 rounded-xl border border-slate-300 bg-white px-6 py-4 shadow-sm">
  <div class="flex flex-col gap-2 text-sm text-slate-800">
    <div class="flex flex-wrap gap-4">
      <div class="flex-1 min-w-[180px]">
        <span class="font-semibold">Asignatura:</span>
        <span><?= h($examen['asignatura_nombre'] ?? '________________') ?></span>
      </div>
      <div class="flex-1 min-w-[120px]">
        <span class="font-semibold">Curso:</span>
        <span><?= h($examen['curso_nombre'] ?? '________________') ?></span>
      </div>
    </div>

    <div class="flex flex-wrap gap-4">
      <div class="flex-1 min-w-[220px]">
        <span class="font-semibold">Título del examen:</span>
        <span><?= h($examen['titulo']) ?></span>
      </div>
      <div class="flex-1 min-w-[120px]">
        <span class="font-semibold">Fecha:</span>
        <span><?= !empty($examen['fecha']) ? h($examen['fecha']) : '____ / ____ / ______' ?></span>
      </div>
      <div class="flex-1 min-w-[100px]">
        <span class="font-semibold">Duración:</span>
        <span>
          <?php
          $dur = $examen['duracion_minutos'] ?? null;
          echo $dur !== null && $dur !== '' ? ((int) $dur . ' min') : '________';
          ?>
        </span>
      </div>
    </div>

    <div class="mt-2 flex flex-wrap gap-4">
      <div class="flex-1 min-w-[260px]">
        <span class="font-semibold">Alumno/a:</span>
        <span>__________________________________________</span>
      </div>
      <div class="flex-1 min-w-[180px]">
        <span class="font-semibold">Profesor/a:</span>
        <span><?= h(trim(($examen['profesor_apellidos'] ?? '') . ' ' . ($examen['profesor_nombre'] ?? ''))) ?: '________________' ?></span>
      </div>
    </div>
  </div>
</div>

<!-- INSTRUCCIONES GENERALES (opcionales) -->
<?php if (!empty($examen['descripcion'])): ?>
  <div class="mb-6 rounded-xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-700 mb-2">Instrucciones</h2>
    <p class="text-sm text-slate-800 whitespace-pre-line">
      <?= h($examen['descripcion']) ?>
    </p>
  </div>
<?php endif; ?>

<!-- PREGUNTAS DEL EXAMEN (modo alumno) -->
<div class="rounded-xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
  <?php if (!$actividades): ?>
    <p class="text-sm text-slate-700">
      Este examen todavía no tiene actividades asociadas.
    </p>
  <?php else: ?>
    <ol class="space-y-6">
      <?php foreach ($actividades as $a): ?>
        <?php
        $tipo = $a['tipo'];
        $orden = (int) $a['rel_orden'];
        $puntos = $a['rel_puntuacion'] ?? null;
        ?>
        <li>
          <!-- Enunciado de la pregunta -->
          <p class="text-sm font-semibold text-slate-900">
            <?= $orden ?>.
            <?= h($a['titulo']) ?>
            <?php if ($puntos !== null): ?>
              <span class="text-xs font-normal text-slate-500">(<?= h((string) $puntos) ?> ptos)</span>
            <?php endif; ?>
          </p>

          <?php if (!empty($a['descripcion'])): ?>
            <p class="mt-1 text-sm text-slate-800 whitespace-pre-line">
              <?= h($a['descripcion']) ?>
            </p>
          <?php endif; ?>

          <!-- Representación según el tipo -->
          <div class="mt-2 text-sm text-slate-800 space-y-2">
            <?php if ($tipo === 'opcion_multiple'): ?>
              <?php $ops = $a['om_options'] ?? []; ?>
              <?php if ($ops): ?>
                <ul class="mt-1 space-y-1">
                  <?php foreach ($ops as $op): ?>
                    <li class="flex items-start gap-2">
                      <span class="mt-0.5 inline-block h-3 w-3 rounded-full border border-slate-700"></span>
                      <span><?= $op['opcion_html'] ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-xs text-amber-700">
                  (Esta actividad de opción múltiple no tiene opciones configuradas.)
                </p>
              <?php endif; ?>

            <?php elseif ($tipo === 'verdadero_falso'): ?>
              <div class="mt-1 space-y-1">
                <label class="flex items-center gap-2">
                  <span class="inline-block h-3 w-3 rounded-full border border-slate-700"></span>
                  <span>Verdadero</span>
                </label>
                <label class="flex items-center gap-2">
                  <span class="inline-block h-3 w-3 rounded-full border border-slate-700"></span>
                  <span>Falso</span>
                </label>
              </div>

            <?php elseif ($tipo === 'respuesta_corta'): ?>
              <div class="mt-2">
                <div class="h-8 border-b border-slate-700 w-full"></div>
              </div>

            <?php elseif ($tipo === 'rellenar_huecos'): ?>
              <div class="mt-1 text-sm text-slate-800">
                <?= $a['enunciado_html'] ?: '<span class="text-xs text-amber-700">(Enunciado no configurado)</span>' ?>
              </div>

            <?php elseif ($tipo === 'emparejar'): ?>
              <?php
              $pares = json_decode($a['pares_json'] ?? '[]', true);
              ?>
              <?php if ($pares): ?>
                <div class="grid grid-cols-2 gap-6 mt-1">
                  <div>
                    <h4 class="text-xs text-slate-500 mb-1">Columna A</h4>
                    <ul class="space-y-1">
                      <?php foreach ($pares as $p): ?>
                        <li class="rounded border border-slate-300 px-2 py-1">
                          <?= $p[0] ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <div>
                    <h4 class="text-xs text-slate-500 mb-1">Columna B</h4>
                    <ul class="space-y-1">
                      <?php foreach ($pares as $p): ?>
                        <li class="rounded border border-slate-300 px-2 py-1">
                          <?= $p[1] ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              <?php else: ?>
                <p class="text-xs text-amber-700">
                  (Esta actividad de emparejar no tiene pares configurados.)
                </p>
              <?php endif; ?>

            <?php elseif ($tipo === 'tarea'): ?>
              <?php if (!empty($a['instrucciones'])): ?>
                <p class="mt-1 text-sm text-slate-800 whitespace-pre-line">
                  <?= h($a['instrucciones']) ?>
                </p>
              <?php endif; ?>
              <div class="mt-3 h-32 border border-slate-300 rounded-md"></div>

            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>