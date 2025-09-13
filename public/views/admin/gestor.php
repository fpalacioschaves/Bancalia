<?php
/** public/views/admin/gestor.php */

$labels = [
  'roles'        => 'Roles',
  'grados'       => 'Grados',
  'asignaturas'  => 'Asignaturas',
  'temas'        => 'Temas',
  'etiquetas'    => 'Etiquetas',
  'actividades'  => 'Actividades',
  'usuarios'     => 'Usuarios',
];
$entity = $entity ?? 'grados';
$title  = $labels[$entity] ?? ucfirst($entity);
?>
<style>
  /* Toolbar */
  .admin-toolbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin:8px 0 12px; }
  .admin-title{ margin:0; }
  .admin-actions{ display:flex; align-items:center; gap:8px; min-width:280px; }
  .admin-actions .search{
    width: clamp(240px, 32vw, 380px);
    height: 40px; padding: 8px 12px; border-radius: 10px;
    border: 1px solid var(--border); background:#0f141f; color:var(--text);
  }
  .admin-actions .btn{ height: 40px; display:inline-flex; align-items:center; justify-content:center; padding:0 14px; line-height:1; }

  /* Tabla a ancho completo */
  .admin-table-card{ padding:0; overflow:auto; }
  .admin-table-card table{ width:100%; }

  /* Acciones a la derecha */
  .admin-table-card thead th:last-child,
  .admin-table-card tbody td:last-child{ text-align:right; }
  .admin-actions-cell{ display:flex; justify-content:flex-end; gap:6px; }

  /* Form modal */
  .form label { display:block; margin:8px 0; }
  .form input, .form select, .form textarea{
    width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--border);
    background:#0f141f; color:var(--text);
  }
</style>

<div class="admin-toolbar">
  <h2 class="admin-title">Gestor: <?= htmlspecialchars($title) ?></h2>
  <div class="admin-actions">
    <input id="q" class="search" type="search" placeholder="Buscar <?= strtolower($title) ?>..." />
    <button class="btn ghost" id="btnReload" title="Buscar">Buscar</button>
    <button class="btn primary" id="btnNew">Nuevo</button>
  </div>
</div>

<div class="card admin-table-card">
  <div id="tableWrap"></div>
</div>

<!-- Modal -->
<div id="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center">
  <div style="background:#14161a; border:1px solid #1b1f2a; border-radius:12px; padding:16px; width:min(700px, 92vw)">
    <h3 id="modalTitle">Editar</h3>
    <form id="frm" class="form"></form>
    <div style="display:flex; gap:8px; margin-top:10px; justify-content:flex-end">
      <button class="btn ghost" id="btnCancel">Cancelar</button>
      <button class="btn primary" id="btnSave">Guardar</button>
    </div>
    <div class="alert" id="modalAlert" hidden></div>
  </div>
</div>

<script>
(function(){
  const API = window.API_BASE || '/Bancalia/api';
  const entity = <?= json_encode($entity) ?>;
  const qs = (s,r)=> (r||document).querySelector(s);
  const ce = (t)=> document.createElement(t);
  const show = (el,msg,type)=>{ if(!el)return; el.textContent=msg; el.className='alert '+(type||''); el.hidden=!msg; };

  const tableWrap = qs('#tableWrap');
  const modal = qs('#modal'), frm = qs('#frm'), mAlert = qs('#modalAlert'), mTitle=qs('#modalTitle');

  let meta = null, rows = [];
  let gradosCache = null;
  let asignaturasCache = null;
  let usuariosCache = null;
  let rolesCache = null;

  async function jget(url){ const r = await fetch(url); if(!r.ok) throw new Error(await r.text()); return r.json(); }
  async function jpost(url,method,body){
    const r = await fetch(url,{method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
    const t = await r.text();
    try{ const j=JSON.parse(t); if(!r.ok) throw new Error(j.error||t); return j; }
    catch{ if(!r.ok) throw new Error(t); return JSON.parse(t); }
  }

  function renderTable(){
    const tbl = ce('table');
    const thead = tbl.createTHead();
    const tbody = tbl.createTBody();

    if (!rows.length){
      tbl.innerHTML = '<tr><td style="padding:16px">No hay datos.</td></tr>';
      tableWrap.innerHTML=''; tableWrap.appendChild(tbl); return;
    }

    const cols = Object.keys(rows[0]);
    const trh = ce('tr');
    cols.forEach(c=>{ const th=ce('th'); th.textContent=c; trh.appendChild(th); });
    const thAct=ce('th'); thAct.textContent='Acciones'; trh.appendChild(thAct);
    thead.appendChild(trh);

    rows.forEach(r=>{
      const tr=ce('tr');
      cols.forEach(c=>{ const td=ce('td'); td.textContent = (r[c] ?? ''); tr.appendChild(td); });

      const td=ce('td'); td.className='admin-actions-cell';
      const bE=ce('button'); bE.textContent='Editar'; bE.className='btn ghost'; bE.onclick=()=>openEdit(r);
      const bD=ce('button'); bD.textContent='Borrar'; bD.className='btn'; bD.onclick=()=>delRow(r);
      td.appendChild(bE); td.appendChild(bD); tr.appendChild(td);

      tbody.appendChild(tr);
    });

    tableWrap.innerHTML=''; tableWrap.appendChild(tbl);
  }

  function makeInput(name, value, required=false){
    const wrap = ce('label'); wrap.style.display='block'; wrap.style.margin='8px 0';
    wrap.textContent = name + (required?' *':'');
    const inp = ce('input'); inp.name = name; inp.value = value ?? ''; inp.placeholder = name;
    if (name.includes('_id') || name==='orden') inp.type='number';
    wrap.appendChild(inp);
    return wrap;
  }

  function makeSelect(name, label, options, selectedValue, required=false, getLabel){
    const wrap = ce('label'); wrap.style.display='block'; wrap.style.margin='8px 0';
    wrap.textContent = label + (required?' *':'');
    const sel = ce('select'); sel.name = name;
    (options||[]).forEach(o=>{
      const opt = ce('option');
      opt.value = o.id;
      const lab = getLabel ? getLabel(o) : (o.nombre ?? ('#'+o.id));
      opt.textContent = lab;
      if (String(o.id) === String(selectedValue ?? '')) opt.selected = true;
      sel.appendChild(opt);
    });
    wrap.appendChild(sel);
    return wrap;
  }

  function userFullName(u){
    return (u?.nombre ?? '') + (u?.apellidos ? (' ' + u.apellidos) : '');
  }

  async function ensureCatalogs(){
    if (entity === 'asignaturas' && !gradosCache){
      try { gradosCache = await jget(`${API}/grados`); } catch(e){ console.error(e); gradosCache = []; }
    }
    if (entity === 'temas' && !asignaturasCache){
      try { asignaturasCache = await jget(`${API}/asignaturas`); } catch(e){ console.error(e); asignaturasCache = []; }
    }
    if (entity === 'etiquetas' && !usuariosCache){
      try { usuariosCache = await jget(`${API}/admin/usuarios?limit=500`); } catch(e){ console.error(e); usuariosCache = []; }
    }
    if (entity === 'usuarios' && !rolesCache){
      try { rolesCache = await jget(`${API}/admin/roles?limit=500`); } catch(e){ console.error(e); rolesCache = []; }
    }
  }

  async function buildForm(row){
    frm.innerHTML='';
    await ensureCatalogs();

    meta.fields.filter(f=>f.writable!==false).forEach(f=>{
      const req = !!(f.required);

      // Asignaturas: select de grado
      if (entity === 'asignaturas' && f.name === 'grado_id'){
        const gradoId = row ? (row.grado_id ?? row.gradoId) : null;
        frm.appendChild( makeSelect('grado_id','Grado', (gradosCache||[]), gradoId, req) );
        return;
      }

      // Temas: select de asignatura por nombre
      if (entity === 'temas' && f.name === 'asignatura_id'){
        const asigId = row ? (row.asignatura_id ?? row.asignaturaId) : null;
        frm.appendChild( makeSelect('asignatura_id','Asignatura', (asignaturasCache||[]), asigId, req) );
        return;
      }

      // Etiquetas: select de creador por nombre completo
      if (entity === 'etiquetas' && f.name === 'creador_id'){
        const uid = row ? (row.creador_id ?? row.creadorId) : null;
        frm.appendChild( makeSelect('creador_id','Creador', (usuariosCache||[]), uid, req, userFullName) );
        return;
      }

      // Usuarios: select de rol por nombre + password virtual opcional
      if (entity === 'usuarios' && f.name === 'rol_id'){
        const rid = row ? (row.rol_id ?? row.rolId) : null;
        frm.appendChild( makeSelect('rol_id','Rol', (rolesCache||[]), rid, req) );
        return;
      }
      if (entity === 'usuarios' && f.name === 'password_hash'){
        // Campo virtual: password en claro sólo si se quiere cambiar/crear
        const wrap = ce('label'); wrap.style.display='block'; wrap.style.margin='8px 0';
        wrap.textContent = 'password (opcional)';
        const inp = ce('input'); inp.name = 'password'; inp.type = 'password'; inp.placeholder = '••••••';
        wrap.appendChild(inp); frm.appendChild(wrap); return;
      }

      frm.appendChild( makeInput(f.name, row ? row[f.name] : '', req) );
    });
  }

  // Editar/Nuevo: cargamos detalle para preselección correcta
  async function openEdit(row){
    mTitle.textContent = row ? 'Editar' : 'Nuevo';
    show(mAlert,'',null);
    let detail = null;
    if (row){
      const id = row[meta.pk];
      try { detail = await jget(`${API}/admin/${entity}/${id}`); }
      catch (e) { console.error(e); }
    }
    await buildForm(detail);
    modal.style.display='flex';
    frm.dataset.id = row ? row[meta.pk] : '';
  }

  async function delRow(row){
    if (!confirm('¿Borrar este registro?')) return;
    const id = row[meta.pk];
    await jpost(`${API}/admin/${entity}/${id}`, 'DELETE', {});
    await reload();
  }

  async function saveForm(){
    show(mAlert,'',null);
    const data = {};
    new FormData(frm).forEach((v,k)=> data[k]=v);
    const id = frm.dataset.id;
    try{
      if (id) await jpost(`${API}/admin/${entity}/${id}`, 'PUT', data);
      else    await jpost(`${API}/admin/${entity}`, 'POST', data);
      modal.style.display='none';
      await reload();
    }catch(e){ show(mAlert, e.message || 'Error', 'err'); }
  }

  async function reload(){
    const params = new URLSearchParams();
    const q = (qs('#q').value || '').trim(); if (q) params.set('q', q);
    rows = await jget(`${API}/admin/${entity}` + (params.toString()?`?${params}`:''));
    renderTable();
  }

  // init
  (async ()=>{
    meta = await jget(`${API}/admin/meta/${entity}`);
    await reload();
  })();

  // eventos
  qs('#btnReload').onclick = reload;
  qs('#btnNew').onclick    = ()=>openEdit(null);
  qs('#btnSave').onclick   = (e)=>{ e.preventDefault(); saveForm(); };
  qs('#btnCancel').onclick = (e)=>{ e.preventDefault(); modal.style.display='none'; };

  // buscar con debounce + Enter
  let t=null;
  qs('#q').addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(reload, 300); });
  qs('#q').addEventListener('keydown', (e)=>{ if (e.key === 'Enter'){ e.preventDefault(); reload(); } });
})();
</script>
