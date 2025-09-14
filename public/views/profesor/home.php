<?php $u = $user ?? null; ?>
<style>
  .dash-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:8px 0 16px;}
  .cards-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
  @media (max-width:920px){.cards-grid{grid-template-columns:1fr 1fr}}
  @media (max-width:600px){.cards-grid{grid-template-columns:1fr}}
  .card.tile{padding:18px;display:flex;gap:14px;align-items:flex-start;text-decoration:none;color:var(--text);}
  .tile .ic{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:var(--surface-2);border:1px solid var(--border);font-size:18px;}
  .tile h3{margin:0 0 6px;font-size:1.05rem;}
  .tile p{margin:0;color:var(--muted);font-size:.95rem;}
</style>

<div class="page">
  <div class="dash-head">
    <h2 style="margin:0">Panel del profesor</h2>
    <div class="userpill">
      <span class="avatar"><?= strtoupper(substr((string)($u['nombre'] ?? 'P'),0,1)) ?></span>
      <span class="uname"><?= htmlspecialchars((string)($u['nombre'] ?? 'Profesor')) ?></span>
      <span class="urole">profesor</span>
    </div>
  </div>

  <div class="cards-grid">
    <a class="card tile" href="/Bancalia/public/profesor/perfil">
      <div class="ic">👤</div>
      <div><h3>Mi perfil</h3><p>Editar datos y materias impartidas.</p></div>
    </a>
    <a class="card tile" href="/Bancalia/public/profesor/actividades">
      <div class="ic">🧩</div>
      <div><h3>Actividades</h3><p>Crea y gestiona tus actividades.</p></div>
    </a>
    <a class="card tile" href="/Bancalia/public/profesor/examenes">
      <div class="ic">📝</div>
      <div><h3>Exámenes</h3><p>Compón exámenes con tus actividades.</p></div>
    </a>
    <a class="card tile" href="/Bancalia/public/profesor/asignaciones">
      <div class="ic">🎯</div>
      <div><h3>Asignaciones</h3><p>Asigna actividades/exámenes a tus grupos.</p></div>
    </a>
    <a class="card tile" href="/Bancalia/public/profesor/entregas">
      <div class="ic">📥</div>
      <div><h3>Entregas</h3><p>Corrige, puntúa y da feedback.</p></div>
    </a>
    <a class="card tile" href="/Bancalia/public/profesor/enlaces">
      <div class="ic">🔗</div>
      <div><h3>Enlaces</h3><p>Genera QR/códigos y controla vigencia.</p></div>
    </a>
  </div>
</div>
