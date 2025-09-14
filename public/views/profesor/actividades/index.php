<?php // /Bancalia/public/views/profesor/actividades/index.php ?>
<style>
  .headbar{display:flex;align-items:center;gap:12px;margin:0 0 12px}
  .headbar h2{margin:0;flex:1}
  .filters{display:flex;gap:8px;align-items:center}
  .filters input,.filters select{height:38px}
  .table-wrap{overflow:auto}
  .muted{color:var(--muted)}
</style>

<div class="page">
  <div class="headbar">
    <h2>Mis actividades y compartidas</h2>
    <div class="filters">
      <input id="q" type="search" placeholder="Buscar…" />
      <select id="estado">
        <option value="">Todo estado</option>
        <option value="borrador">Borrador</option>
        <option value="publicada">Publicada</option>
      </select>
      <select id="visibilidad">
        <option value="">Toda visibilidad</option>
        <option value="privada">Privada</option>
        <option value="compartida">Compartida</option>
        <option value="publicada">Publicada</option>
      </select>
      <button id="btnBuscar" class="btn ghost">Buscar</button>
      <a class="btn" href="/Bancalia/public/profesor/actividades/nueva">Nueva</a>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table id="tbl">
        <thead>
          <tr>
            <th>Título</th>
            <th>Tipo</th>
            <th>Asignatura</th>
            <th>Tema</th>
            <th>Estado</th>
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
(function(){
  const API = window.API_BASE || '/Bancalia/api';
  const qs = (s,r)=> (r||document).querySelector(s);
  const ce = (t)=> document.createElement(t);
  const show = (el,msg,type)=>{ if(!el)return; el.textContent=msg; el.className='alert '+(type||''); el.hidden=!msg; };

  const q         = qs('#q');
  const estadoSel = qs('#estado');
  const visibSel  = qs('#visibilidad');
  const btnBuscar = qs('#btnBuscar');
  const tbody     = qs('#tbl tbody');
  const alertBox  = qs('#alert');

  async function jget(url){
    const r=await fetch(url,{headers:{'Accept':'application/json'}});
    const t=await r.text(); let j={}; try{ j=JSON.parse(t) }catch{}
    if(!r.ok) throw new Error((j&&j.error)||t||('HTTP '+r.status));
    return j;
  }

  function rowActions(it){
    const wrap = ce('div'); wrap.style.textAlign='right';
    if (it.es_mia){
      const e = ce('a'); e.className='btn ghost'; e.textContent='Editar';
      e.href = '/Bancalia/public/profesor/actividades/editar/'+it.id;
      const b = ce('a'); b.className='btn'; b.textContent='Borrar';
      b.href = '/Bancalia/public/profesor/actividades/borrar/'+it.id;
      wrap.appendChild(e); wrap.appendChild(b);
    }else{
      const v = ce('span'); v.className='muted'; v.textContent='Compartida';
      wrap.appendChild(v);
    }
    return wrap;
  }

  function paint(items){
    tbody.innerHTML='';
    if (!items.length){
      const tr=ce('tr'); const td=ce('td'); td.colSpan=7; td.textContent='Sin resultados'; td.className='muted';
      tr.appendChild(td); tbody.appendChild(tr); return;
    }
    items.forEach(it=>{
      const tr=ce('tr');
      tr.innerHTML = `
        <td>${it.titulo}</td>
        <td>${it.tipo}</td>
        <td>${it.asignaturas||''}</td>
        <td>${it.temas||''}</td>
        <td>${it.estado}</td>
        <td>${it.visibilidad}</td>
      `;
      const tdAc=ce('td'); tdAc.style.textAlign='right'; tdAc.appendChild(rowActions(it)); tr.appendChild(tdAc);
      tbody.appendChild(tr);
    });
  }

  async function load(page=1){
    show(alertBox,'',null);
    const p = new URLSearchParams();
    if (q.value.trim()!=='') p.set('q', q.value.trim());
    if (estadoSel.value!=='') p.set('estado', estadoSel.value);
    if (visibSel.value!=='') p.set('visibilidad', visibSel.value);
    p.set('page', page); p.set('per_page','20');

    try{
      const data = await jget(API + '/profesor/actividades?' + p.toString());
      paint(data.items||[]);
    }catch(e){
      console.error(e); show(alertBox, e.message || 'No se pudo cargar', 'err');
      tbody.innerHTML='<tr><td colspan="7" class="muted">Error al cargar</td></tr>';
    }
  }

  btnBuscar.addEventListener('click', ()=> load(1));
  q.addEventListener('keydown', (ev)=>{ if(ev.key==='Enter'){ ev.preventDefault(); load(1);} });
  estadoSel.addEventListener('change', ()=> load(1));
  visibSel.addEventListener('change', ()=> load(1));

  load(1);
})();
</script>
