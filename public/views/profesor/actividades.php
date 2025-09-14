<?php // /Bancalia/public/views/profesor/actividades.php ?>
<style>
  .headbar{display:flex;align-items:center;gap:12px;margin:0 0 12px}
  .headbar h2{margin:0;flex:1}

  /* Buscador como admin */
  .searchbar{display:flex;align-items:center;gap:10px}
  .searchbar .input{
    height:40px; width:360px;
    background:#0f141f; color:var(--text);
    border:1px solid var(--border); border-radius:10px;
    padding:8px 12px;
  }
  .table-wrap{overflow:auto}
  .muted{color:var(--muted)}
</style>

<div class="page">
  <div class="headbar">
    <h2>Mis actividades y compartidas</h2>

    <form id="searchForm" class="searchbar" onsubmit="return false;">
      <input id="q" class="input" type="search" placeholder="Buscar actividades..." />
      <button id="btnBuscar" class="btn ghost">Buscar</button>
      <a class="btn" href="/Bancalia/public/profesor/actividades/nueva">Nueva</a>
    </form>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table id="tbl">
        <thead>
          <tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>Grado</th>
            <th>Asignatura</th>
            <th>Tema</th>
            <th>Visibilidad</th>
            <th style="text-align:right">Acciones</th>
          </tr>
        </thead>
        <tbody><tr><td colspan="7" class="muted">Cargando…</td></tr></tbody>
      </table>
    </div>
    <div id="alert" class="alert" hidden></div>
  </div>
</div>

<script>
// Requiere: /Bancalia/public/assets/js/bancalia.list.js (cargado en layout)
(function(){
  const qs = (s,r)=> (r||document).querySelector(s);
  const ce = (t)=> document.createElement(t);

  const tbl   = qs('#tbl');
  const form  = qs('#searchForm');
  const q     = qs('#q');
  const alert = qs('#alert');

  function renderRow(it){
    const tr = ce('tr');

    const tdT = ce('td'); tdT.textContent = it.titulo; tr.appendChild(tdT);
    const tdTy= ce('td'); tdTy.textContent= it.tipo;   tr.appendChild(tdTy);
    const tdG = ce('td'); tdG.textContent = it.grados||''; tr.appendChild(tdG);
    const tdA = ce('td'); tdA.textContent = it.asignaturas||''; tr.appendChild(tdA);
    const tdTe= ce('td'); tdTe.textContent= it.temas||''; tr.appendChild(tdTe);
    const tdV = ce('td'); tdV.textContent = it.visibilidad; tr.appendChild(tdV);

    const tdAc = ce('td'); tdAc.style.textAlign='right';
    if (it.es_mia){
      const e = ce('a'); e.className='btn ghost'; e.textContent='Editar';
      e.href = '/Bancalia/public/profesor/actividades/editar/'+it.id;
      const b = ce('a'); b.className='btn'; b.textContent='Borrar';
      b.href = '/Bancalia/public/profesor/actividades/borrar/'+it.id;
      tdAc.appendChild(e); tdAc.appendChild(b);
    } else {
      const v = ce('a'); v.className='btn ghost'; v.textContent='Ver';
      v.href = '/Bancalia/public/actividades/'+it.id;
      tdAc.appendChild(v);
    }
    tr.appendChild(tdAc);
    return tr;
  }

  const list = new BancaliaList({
    table: tbl,
    form: form,
    alert: alert,
    endpoint: '/profesor/actividades',
    buildQuery: ()=> {
      const qv = q.value.trim();
      return qv ? { q: qv } : {};
    },
    renderRow
  });

  // disparadores
  qs('#btnBuscar').addEventListener('click', ()=> list.load(1));
  form.addEventListener('submit', ()=> list.load(1));
  q.addEventListener('keydown', (ev)=>{ if(ev.key==='Enter'){ ev.preventDefault(); list.load(1);} });

  list.load(1);
})();
</script>
