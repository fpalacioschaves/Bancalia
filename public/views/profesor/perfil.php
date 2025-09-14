<?php
// /Bancalia/public/views/profesor/perfil.php
$u = $user ?? null;
?>
<style>
  /* Layout en una sola columna */
  .stack{display:grid;grid-template-columns:1fr;gap:16px}
  .row{display:flex;align-items:center;gap:10px}
  .row .grow{flex:1}

  /* Controles consistentes y fluidos */
  .form input, .form select { height:42px; width:100%; }
  .table-wrap{overflow:auto}
  .muted{color:var(--muted)}

  /* Input password con mismo look incluso con autofill */
  input:-webkit-autofill,
  input:-webkit-autofill:hover,
  input:-webkit-autofill:focus,
  input:-webkit-autofill:active{
    -webkit-box-shadow: 0 0 0px 1000px #0f141f inset;
    -webkit-text-fill-color: var(--text);
    transition: background-color 9999s ease-in-out 0s;
  }

  /* Evitar que el texto del <select> quede tapado por la flecha nativa */
  .form select{ padding-right:34px; -webkit-padding-end:34px; }
</style>

<div class="page">
  <h2 style="margin:0 0 12px">Mi perfil</h2>

  <div class="stack">
    <!-- Card: Datos personales -->
    <div class="card">
      <h3 style="margin-top:0">Datos personales</h3>
      <form id="frmPerfil" class="form">
        <label>Nombre
          <input type="text" name="nombre" value="<?= htmlspecialchars((string)($u['nombre'] ?? '')) ?>" required />
        </label>
        <label>Email
          <input type="email" name="email" value="<?= htmlspecialchars((string)($u['email'] ?? '')) ?>" required />
        </label>
        <label>Nueva contraseña <small class="muted">(opcional)</small>
          <input type="password" name="password" placeholder="••••••••" />
        </label>
        <div class="row" style="justify-content:flex-end">
          <button class="btn primary">Guardar cambios</button>
        </div>
        <div id="pfAlert" class="alert" hidden></div>
      </form>
    </div>

    <!-- Card: Materias impartidas -->
    <div class="card">
      <h3 style="margin-top:0">Materias impartidas</h3>

      <!-- Barra de alta -->
      <div class="row" style="margin-bottom:10px">
        <select id="gradoSel" class="grow" aria-label="Grado"></select>
        <select id="cursoSel" class="grow" aria-label="Curso"></select>
        <select id="asigSel"  class="grow" aria-label="Asignatura"></select>
        <button class="btn" id="btnAdd" style="height:42px">Añadir</button>
      </div>

      <div class="table-wrap">
        <table id="tblImp">
          <thead>
            <tr>
              <th>Grado</th><th>Curso</th><th>Asignatura</th>
              <th style="text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div id="impAlert" class="alert" hidden></div>
    </div>
  </div>
</div>

<script>
(function(){
  const API = window.API_BASE || '/Bancalia/api';
  const qs = (s,r)=> (r||document).querySelector(s);
  const ce = (t)=> document.createElement(t);
  const show = (el,msg,type)=>{ if(!el)return; el.textContent=msg; el.className='alert '+(type||''); el.hidden=!msg; };

  // --- refs
  const frm       = qs('#frmPerfil');
  const pfAlert   = qs('#pfAlert');
  const gradoSel  = qs('#gradoSel');
  const cursoSel  = qs('#cursoSel');
  const asigSel   = qs('#asigSel');
  const btnAdd    = qs('#btnAdd');
  const tblBody   = qs('#tblImp tbody');
  const impAlert  = qs('#impAlert');

  // --- helpers fetch
  async function jget(url){
    const r=await fetch(url,{headers:{'Accept':'application/json'}});
    const t=await r.text(); let j={}; try{ j=JSON.parse(t) }catch{}
    if(!r.ok) throw new Error((j && j.error) ? j.error : ('HTTP '+r.status+' '+url));
    return j;
  }
  async function jsend(url,method,body){
    const r=await fetch(url,{method,headers:{'Content-Type':'application/json','Accept':'application/json'}, body: body?JSON.stringify(body):null});
    const t=await r.text(); let j={}; try{ j=JSON.parse(t) }catch{ j={}; }
    if(!r.ok) throw new Error((j && j.error) ? j.error : ('HTTP '+r.status+' '+url));
    return j;
  }

  function populate(sel, items, value='id', label='nombre'){
    sel.innerHTML='';
    (items||[]).forEach(it=>{
      const o=ce('option'); o.value=it[value]; o.textContent=it[label]; sel.appendChild(o);
    });
  }

  async function loadCatalogos(){
    try{
      const grados = await jget(API+'/grados');
      populate(gradoSel, grados);
      await syncCursoAsig();
    }catch(e){
      console.error(e);
      show(impAlert,'No se pudieron cargar grados', 'err');
    }
  }

  async function syncCursoAsig(){
    const gid = parseInt(gradoSel.value||'0',10);
    try{
      const [cursos, asigs] = await Promise.all([
        jget(API+'/cursos?grado_id='+gid),
        jget(API+'/asignaturas?grado_id='+gid),
      ]);
      populate(cursoSel, cursos);
      populate(asigSel, asigs);
      show(impAlert,'',null);
    }catch(e){
      console.error(e);
      show(impAlert,'No se pudieron cargar cursos/asignaturas', 'err');
    }
  }

  async function reloadImparte(){
    try{
      const data = await jget(API+'/profesor/perfil'); // GET datos e imparte[]
      const imp  = (data && data.imparte) ? data.imparte : [];
      tblBody.innerHTML='';
      if (!imp.length){
        const tr=ce('tr'); const td=ce('td'); td.colSpan=4; td.textContent='Sin materias registradas';
        td.className='muted'; tr.appendChild(td); tblBody.appendChild(tr);
        return;
      }
      imp.forEach(row=>{
        const tr=ce('tr');
        tr.innerHTML = `<td>${row.grado}</td><td>${row.curso}</td><td>${row.asignatura}</td>`;
        const td=ce('td'); td.style.textAlign='right';
        const b=ce('button'); b.className='btn ghost'; b.textContent='Quitar';
        b.onclick=()=> delImparte(row.id);
        td.appendChild(b); tr.appendChild(td);
        tblBody.appendChild(tr);
      });
      show(impAlert,'',null);
    }catch(e){
      console.error(e);
      show(impAlert, e.message || 'No se pudo cargar la lista de materias', 'err');
    }
  }

  async function delImparte(id){
    try{
      // DELETE; si tu host no lo soporta, usa alias POST
      try{ await jsend(API+'/profesor/imparte/'+id, 'DELETE'); }
      catch(e){ await jsend(API+'/profesor/imparte/delete/'+id, 'POST'); }
      reloadImparte();
    }catch(e){ console.error(e); show(impAlert, e.message||'No se pudo eliminar', 'err'); }
  }

  gradoSel.addEventListener('change', syncCursoAsig);

  btnAdd.addEventListener('click', async ()=>{
    show(impAlert,'',null);
    const gid = parseInt(gradoSel.value||'0',10);
    const cid = parseInt(cursoSel.value||'0',10);
    const aid = parseInt(asigSel.value ||'0',10);
    if (!gid || !cid || !aid){ show(impAlert,'Selecciona grado, curso y asignatura', 'err'); return; }
    try{
      await jsend(API+'/profesor/imparte', 'POST', { grado_id:gid, curso_id:cid, asignatura_id:aid });
      reloadImparte();
    }catch(e){
      console.error(e);
      show(impAlert, e.message || 'No se pudo añadir', 'err');
    }
  });

  // Guardar perfil
  frm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    show(pfAlert,'',null);
    const fd = new FormData(frm);
    const payload = {
      nombre: fd.get('nombre'),
      email:  fd.get('email'),
      password: fd.get('password') || ''
    };
    try{
      // PUT; si no está permitido, alias POST
      try { await jsend(API+'/profesor/perfil', 'PUT', payload); }
      catch(e){ await jsend(API+'/profesor/perfil/update', 'POST', payload); }
      show(pfAlert,'Guardado', 'ok');
    }catch(e){
      console.error(e);
      show(pfAlert, e.message || 'No se pudo guardar', 'err');
    }
  });

  // init
  (async ()=>{
    await loadCatalogos();
    await reloadImparte();
  })();
})();
</script>
