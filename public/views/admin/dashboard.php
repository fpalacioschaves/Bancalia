<?php $path = __FILE__; ?>
<h1>Administración</h1>
<p class="muted">Elige un bloque para gestionarlo.</p>

<!-- 3 columnas fijas, usando tu .card y sin tocar el CSS global -->
<section style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:16px 0">
  <a class="card" href="/Bancalia/public/admin/gestor/grados" style="text-decoration:none;color:inherit">
    <div style="font-size:22px;line-height:1;opacity:.9">🎓</div>
    <h3 style="margin:.4rem 0 .2rem">Grados</h3>
    <p class="muted" style="margin:0">Altas y edición de titulaciones.</p>
  </a>

  <a class="card" href="/Bancalia/public/admin/gestor/asignaturas" style="text-decoration:none;color:inherit">
    <div style="font-size:22px;line-height:1;opacity:.9">📚</div>
    <h3 style="margin:.4rem 0 .2rem">Asignaturas</h3>
    <p class="muted" style="margin:0">Catálogo por grado.</p>
  </a>

  <a class="card" href="/Bancalia/public/admin/gestor/temas" style="text-decoration:none;color:inherit">
    <div style="font-size:22px;line-height:1;opacity:.9">🧩</div>
    <h3 style="margin:.4rem 0 .2rem">Temas</h3>
    <p class="muted" style="margin:0">Unidades y bloques de contenido.</p>
  </a>

  <a class="card" href="/Bancalia/public/admin/gestor/etiquetas" style="text-decoration:none;color:inherit">
    <div style="font-size:22px;line-height:1;opacity:.9">🏷️</div>
    <h3 style="margin:.4rem 0 .2rem">Etiquetas</h3>
    <p class="muted" style="margin:0">Organiza y filtra actividades.</p>
  </a>

  <a class="card" href="/Bancalia/public/admin/gestor/actividades" style="text-decoration:none;color:inherit">
    <div style="font-size:22px;line-height:1;opacity:.9">✅</div>
    <h3 style="margin:.4rem 0 .2rem">Actividades</h3>
    <p class="muted" style="margin:0">VF, elección múltiple y desarrollo.</p>
  </a>

  <a class="card" href="/Bancalia/public/admin/gestor/usuarios" style="text-decoration:none;color:inherit">
    <div style="font-size:22px;line-height:1;opacity:.9">👤</div>
    <h3 style="margin:.4rem 0 .2rem">Usuarios</h3>
    <p class="muted" style="margin:0">Altas, roles y estado.</p>
  </a>
</section>
