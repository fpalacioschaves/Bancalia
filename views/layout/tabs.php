<?php
$active = $activeTab ?? 'catalogo';
function sel($tab, $active){ return $tab === $active ? 'true' : 'false'; }
?>
<nav class="mb-5 grid grid-cols-2 md:grid-cols-5 gap-2">
  <button class="tablink" data-tab="resumen"     aria-selected="<?= sel('resumen', $active) ?>">Resumen</button>
  <button class="tablink" data-tab="usuarios"    aria-selected="<?= sel('usuarios', $active) ?>">Usuarios/Roles</button>
  <button class="tablink" data-tab="actividades" aria-selected="<?= sel('actividades', $active) ?>">Actividades</button>
  <button class="tablink" data-tab="catalogo"    aria-selected="<?= sel('catalogo', $active) ?>">Catálogos</button>
  <button class="tablink" data-tab="informes"    aria-selected="<?= sel('informes', $active) ?>">Informes</button>
</nav>
