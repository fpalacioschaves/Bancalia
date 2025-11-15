<?php
// /public/admin/examenes/preview.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

// =======================
//   ID DEL EXAMEN
// =======================
$examenId = (int)($_GET['id'] ?? 0);
if ($examenId <= 0) {
    http_response_code(400);
    exit('ID de examen inválido.');
}

// =======================
//   CARGAR EXAMEN
// =======================
$stEx = pdo()->prepare('
    SELECT e.*,
           p.nombre     AS profesor_nombre,
           p.apellidos  AS profesor_apellidos,
           a.nombre     AS asignatura_nombre,
           c.nombre     AS curso_nombre,
           f.nombre     AS familia_nombre
    FROM examenes e
    LEFT JOIN profesores p              ON p.id = e.profesor_id
    LEFT JOIN asignaturas a            ON a.id = e.asignatura_id
    LEFT JOIN cursos c                 ON c.id = e.curso_id
    LEFT JOIN familias_profesionales f ON f.id = e.familia_id
    WHERE e.id = :id
    LIMIT 1
');
$stEx->execute([':id' => $examenId]);
$examen = $stEx->fetch(PDO::FETCH_ASSOC);

if (!$examen) {
    http_response_code(404);
    exit('Examen no encontrado.');
}

// =======================
//   CARGAR ACTIVIDADES
// =======================
$stActs = pdo()->prepare('
    SELECT
        ea.orden,
        ea.puntuacion,
        a.id,
        a.titulo,
        a.tipo,
        a.descripcion,
        a.dificultad,
        a.estado
    FROM examenes_actividades ea
    JOIN actividades a ON a.id = ea.actividad_id
    WHERE ea.examen_id = :id
    ORDER BY ea.orden ASC, a.id ASC
');
$stActs->execute([':id' => $examenId]);
$actividades = $stActs->fetchAll(PDO::FETCH_ASSOC);

// =======================
//   STATEMENTS AUXILIARES
// =======================

// Opción múltiple: opciones
$stmOMOpciones = pdo()->prepare('
    SELECT opcion_html, es_correcta, orden
    FROM actividades_om_opciones
    WHERE actividad_id = :id
    ORDER BY orden ASC, id ASC
');

// Rellenar huecos: enunciado con {{huecos}}
$stmRH = pdo()->prepare('
    SELECT enunciado_html
    FROM actividades_rh
    WHERE actividad_id = :id
    LIMIT 1
');

// Emparejar: instrucciones + pares
$stmEMP = pdo()->prepare('
    SELECT instrucciones_html
    FROM actividades_emp
    WHERE actividad_id = :id
    LIMIT 1
');

$stmEMPPares = pdo()->prepare('
    SELECT izquierda_html, derecha_html
    FROM actividades_emp_pares
    WHERE actividad_id = :id AND activo = 1
    ORDER BY orden_izq ASC, id ASC
');

// Tarea: instrucciones largas
$stmTarea = pdo()->prepare('
    SELECT instrucciones
    FROM actividades_tarea
    WHERE actividad_id = :id
    LIMIT 1
');

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Vista previa del examen</h1>
    <p class="mt-1 text-sm text-slate-600">
      Examen: <strong><?= htmlspecialchars($examen['titulo']) ?></strong>
    </p>
    <p class="mt-1 text-xs text-slate-500 space-x-1">
      <span>
        Profesor:
        <?= htmlspecialchars(trim(($examen['profesor_apellidos'] ?? '') . ' ' . ($examen['profesor_nombre'] ?? ''))) ?: '—' ?>
      </span>
      <?php if (!empty($examen['familia_nombre'])): ?>
        · <span>Familia: <?= htmlspecialchars($examen['familia_nombre']) ?></span>
      <?php endif; ?>
      <?php if (!empty($examen['curso_nombre'])): ?>
        · <span>Curso: <?= htmlspecialchars($examen['curso_nombre']) ?></span>
      <?php endif; ?>
      <?php if (!empty($examen['asignatura_nombre'])): ?>
        · <span>Asignatura: <?= htmlspecialchars($examen['asignatura_nombre']) ?></span>
      <?php endif; ?>
    </p>
  </div>

  <div class="flex gap-2">
    <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
       class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
      Volver a exámenes
    </a>
    <a href="<?= PUBLIC_URL ?>/admin/examenes/edit.php?id=<?= (int)$examen['id'] ?>"
       class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
      Editar examen
    </a>

    <a href="<?= PUBLIC_URL ?>/admin/examenes/preview_print.php?id=<?= (int)$examen['id'] ?>"
      class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
      Versión imprimible
    </a>
  </div>
</div>

<!-- Datos generales -->
<div class="mb-6 grid gap-4 sm:grid-cols-2">
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-700 mb-3">Detalles del examen</h2>
    <dl class="space-y-2 text-sm text-slate-700">
      <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Estado</dt>
        <dd>
          <?php if (($examen['estado'] ?? '') === 'publicado' || ($examen['estado'] ?? '') === 'publicada'): ?>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700">
              Publicado
            </span>
          <?php else: ?>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium text-slate-700">
              Borrador
            </span>
          <?php endif; ?>
        </dd>
      </div>

      <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Fecha</dt>
        <dd><?= !empty($examen['fecha']) ? htmlspecialchars($examen['fecha']) : '—' ?></dd>
      </div>

      <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Hora</dt>
        <dd><?= !empty($examen['hora']) ? htmlspecialchars(substr($examen['hora'], 0, 5)) : '—' ?></dd>
      </div>

      <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Duración</dt>
        <dd>
          <?php
          $dur = $examen['duracion_minutos'] ?? null;
          echo $dur !== null && $dur !== '' ? ((int)$dur . ' min') : 'No especificada';
          ?>
        </dd>
      </div>
    </dl>
  </div>

  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-700 mb-3">Descripción</h2>
    <p class="text-sm text-slate-700 whitespace-pre-line">
      <?= !empty($examen['descripcion'])
           ? htmlspecialchars($examen['descripcion'])
           : 'Sin descripción.' ?>
    </p>
  </div>
</div>

<!-- Preguntas del examen -->
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
  <div class="mb-4 flex items-center justify-between">
    <h2 class="text-sm font-semibold text-slate-700">
      Actividades del examen
      <?php if ($actividades): ?>
        <span class="ml-2 text-xs font-normal text-slate-500">(<?= count($actividades) ?> actividades)</span>
      <?php endif; ?>
    </h2>
    <a href="<?= PUBLIC_URL ?>/admin/examenes/actividades.php?id=<?= (int)$examen['id'] ?>"
       class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
      Gestionar actividades
    </a>
  </div>

  <?php if (!$actividades): ?>
    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
      Este examen todavía no tiene actividades asociadas.
    </div>
  <?php else: ?>
    <ol class="space-y-4">
      <?php foreach ($actividades as $a): ?>
        <?php
          $tipo   = $a['tipo'];
          $orden  = (int)$a['orden'];
          $puntos = $a['puntuacion'] ?? null;
        ?>
        <li class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-semibold text-slate-500">
                Pregunta <?= $orden ?><?= $puntos !== null ? ' · ' . htmlspecialchars((string)$puntos) . ' ptos' : '' ?>
              </p>
              <h3 class="text-sm font-semibold text-slate-900">
                <?= htmlspecialchars($a['titulo']) ?>
              </h3>
            </div>
            <div class="flex flex-col items-end gap-1 text-right">
              <span class="inline-flex items-center rounded-full bg-slate-900 px-2 py-0.5 text-[11px] font-medium text-white">
                <?= htmlspecialchars($tipo) ?>
              </span>
              <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                Dificultad: <?= htmlspecialchars($a['dificultad']) ?>
              </span>
              <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                <?= htmlspecialchars($a['estado'] === 'publicada' ? 'Publicada' : 'Borrador') ?>
              </span>
            </div>
          </div>

          <?php if (!empty($a['descripcion'])): ?>
            <div class="mt-2 text-sm text-slate-800 whitespace-pre-line">
              <?= htmlspecialchars($a['descripcion']) ?>
            </div>
          <?php endif; ?>

          <div class="mt-3 text-sm text-slate-800 space-y-2">
            <?php if ($tipo === 'opcion_multiple'): ?>
              <?php
                $stmOMOpciones->execute([':id' => $a['id']]);
                $ops = $stmOMOpciones->fetchAll(PDO::FETCH_ASSOC);
              ?>
              <?php if ($ops): ?>
                <ul class="mt-1 space-y-1">
                  <?php foreach ($ops as $op): ?>
                    <li class="flex items-start gap-2">
                      <span class="mt-1 inline-block h-3 w-3 rounded-full border border-slate-400"></span>
                      <span><?= $op['opcion_html'] ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1 inline-block">
                  (Esta actividad de opción múltiple no tiene opciones configuradas todavía.)
                </p>
              <?php endif; ?>

            <?php elseif ($tipo === 'verdadero_falso'): ?>
              <div class="mt-1 space-y-1">
                <label class="flex items-center gap-2 text-sm">
                  <span class="inline-block h-3 w-3 rounded-full border border-slate-400"></span>
                  <span>Verdadero</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                  <span class="inline-block h-3 w-3 rounded-full border border-slate-400"></span>
                  <span>Falso</span>
                </label>
              </div>

            <?php elseif ($tipo === 'respuesta_corta'): ?>
              <div class="mt-2">
                <span class="text-xs text-slate-500 block mb-1">Respuesta del alumno:</span>
                <div class="h-8 rounded-md border border-slate-300 bg-white"></div>
              </div>

            <?php elseif ($tipo === 'rellenar_huecos'): ?>
              <?php
                $stmRH->execute([':id' => $a['id']]);
                $rh = $stmRH->fetchColumn();
              ?>
              <div class="mt-2 text-sm text-slate-800">
                <?= $rh ?: '<span class="text-xs text-amber-700">(Enunciado no configurado)</span>' ?>
              </div>

            <?php elseif ($tipo === 'emparejar'): ?>
              <?php
                $stmEMP->execute([':id' => $a['id']]);
                $enunEmp = $stmEMP->fetchColumn();

                $stmEMPPares->execute([':id' => $a['id']]);
                $pares = $stmEMPPares->fetchAll(PDO::FETCH_ASSOC);
              ?>
              <?php if ($enunEmp): ?>
                <div class="mb-2 text-sm text-slate-800">
                  <?= $enunEmp ?>
                </div>
              <?php endif; ?>

              <?php if ($pares): ?>
                <div class="grid grid-cols-2 gap-6">
                  <div>
                    <h4 class="text-xs text-slate-500 mb-1">Columna A</h4>
                    <ul class="space-y-1">
                      <?php foreach ($pares as $p): ?>
                        <li class="rounded border border-slate-300 bg-white px-2 py-1">
                          <?= $p['izquierda_html'] ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <div>
                    <h4 class="text-xs text-slate-500 mb-1">Columna B</h4>
                    <ul class="space-y-1">
                      <?php foreach ($pares as $p): ?>
                        <li class="rounded border border-slate-300 bg-white px-2 py-1">
                          <?= $p['derecha_html'] ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              <?php else: ?>
                <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1 inline-block">
                  (Esta actividad de emparejar no tiene pares configurados.)
                </p>
              <?php endif; ?>

            <?php elseif ($tipo === 'tarea'): ?>
              <?php
                $stmTarea->execute([':id' => $a['id']]);
                $txTarea = $stmTarea->fetchColumn();
              ?>
              <div class="mt-2 text-sm text-slate-800 whitespace-pre-line">
                <?= $txTarea ? htmlspecialchars($txTarea) : '(Instrucciones de la tarea no configuradas.)' ?>
              </div>

            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
