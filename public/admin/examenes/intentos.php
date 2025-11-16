<?php
// /public/admin/examenes/intentos.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u   = current_user();
$pdo = pdo();

$examen_id = isset($_GET['examen_id']) ? (int)$_GET['examen_id'] : 0;
if ($examen_id <= 0) {
    http_response_code(400);
    echo "Examen no válido.";
    exit;
}

// Cargar datos del examen
$st = $pdo->prepare("SELECT * FROM examenes WHERE id = ?");
$st->execute([$examen_id]);
$examen = $st->fetch(PDO::FETCH_ASSOC);

if (!$examen) {
    http_response_code(404);
    echo "Examen no encontrado.";
    exit;
}

// Cargar intentos del examen
$st2 = $pdo->prepare("
    SELECT *
    FROM examen_intentos
    WHERE examen_id = ?
    ORDER BY id DESC
");
$st2->execute([$examen_id]);
$intentos = $st2->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../../partials/header.php';
?>

<h1 class="text-xl font-semibold tracking-tight mb-2">Intentos del examen</h1>
<p class="text-sm text-slate-500 mb-6">
  Examen: <span class="font-semibold"><?= htmlspecialchars($examen['titulo']) ?></span>
</p>

<a
  href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
  class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 mb-4"
>
  &larr; Volver a exámenes
</a>

<?php if (!$intentos): ?>
  <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-slate-500 text-sm">
    Todavía no hay ningún intento registrado para este examen.
  </div>
<?php else: ?>
  <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">ID intento</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Alumno</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Fecha</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nota</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($intentos as $it): ?>
          <?php
            // Intentamos usar created_at, si no existe probamos con inicio, y si no, vacío
            $fecha     = $it['created_at'] ?? ($it['inicio'] ?? '');
            $nota      = $it['nota'] ?? null;
            $corregido = isset($it['corregido']) ? (int)$it['corregido'] : 0;
          ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm text-slate-700">
              #<?= (int)$it['id'] ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-800">
              <?= htmlspecialchars($it['nombre_alumno'] ?? '—') ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              <?= htmlspecialchars($it['email_alumno'] ?? '—') ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              <?= $fecha ? htmlspecialchars($fecha) : '—' ?>
            </td>
            <td class="px-4 py-3 text-sm">
              <?php if ($nota === null): ?>
                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[12px] font-medium text-amber-700 ring-1 ring-amber-200">
                  Pendiente
                </span>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                  <?= htmlspecialchars(number_format((float)$nota, 2, ',', '.')) ?>
                  <?php if ($corregido): ?>
                    &nbsp;✔
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end">
                <a
                  href="<?= PUBLIC_URL ?>/admin/examenes/intento_ver.php?intento_id=<?= (int)$it['id'] ?>"
                  class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                >
                  Ver / Calificar
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
