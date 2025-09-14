<?php
// /Bancalia/public/views/auth/register.php
?>
<div class="page">
  <div class="card auth">
    <h2 style="margin-top:0">Crea tu cuenta</h2>

    <form id="registerForm" class="form">
      <label>Nombre
        <input type="text" name="nombre" placeholder="Tu nombre" required />
      </label>

      <label>Email
        <input type="email" name="email" placeholder="tucorreo@ejemplo.com" required />
      </label>

      <label>Contraseña
        <input type="password" name="password" placeholder="••••••••" required />
      </label>

      <!-- Radios bonitos -->
      <fieldset class="rolebox">
        <legend>Rol</legend>
        <label class="rb">
          <input type="radio" name="rol" value="alumno" checked />
          <span class="dot"></span>
          <span>Alumno</span>
        </label>
        <label class="rb">
          <input type="radio" name="rol" value="profesor" />
          <span class="dot"></span>
          <span>Profesor</span>
        </label>
      </fieldset>

      <!-- Selección de grado/curso/asignatura -->
       <fieldset class="rolebox">
        <label>Grado
          <select id="gradoSelect" name="grado_id" required></select>
        </label>
        <br>
        <label>Curso
          <select id="cursoSelect" name="curso_id" required></select>
        </label>
       </fieldset>

      <!-- Solo profesor: asignatura + lista de “imparte” -->
      <div id="profWrap" hidden>
        <fieldset class="rolebox">
        <label>Asignatura
          <select id="asigSelect" name="asignatura_id"></select>
        </label>

        <div class="row eqh" style="gap:10px; margin:8px 0 4px">
          <button type="button" id="btnAddImp" class="btn">Añadir</button>
        </div>

        <div class="table-wrap">
          <table id="tblImp">
            <thead>
              <tr>
                <th>Grado</th><th>Curso</th><th>Asignatura</th>
                <th style="text-align:right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr id="impEmpty"><td colspan="4" class="muted">Añade al menos una asignatura</td></tr>
            </tbody>
          </table>
        </div>
        </fieldset>
      </div>

      <button class="btn primary" style="margin-top:10px">Crear cuenta</button>
      <div id="registerAlert" class="alert" hidden></div>
      <p style="margin:8px 0 0">¿Ya tienes cuenta? <a class="link" href="/Bancalia/public/login">Inicia sesión</a></p>
    </form>
  </div>
</div>

<style>
  .rolebox{border:1px solid var(--border); padding:10px 12px; border-radius:12px; margin:12px 0}
  .rolebox legend{padding:0 6px; color:var(--muted)}
  .rb{display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:10px;}
  .rb input{appearance:none; -webkit-appearance:none; width:0; height:0; position:absolute; opacity:0;}
  .rb .dot{width:18px; height:18px; border-radius:50%;
           border:2px solid var(--border); display:inline-block; position:relative; transition:.15s;}
  .rb input:checked + .dot{border-color:var(--brand); box-shadow:0 0 0 3px rgba(106,166,255,.25);}
  .rb input:checked + .dot::after{content:""; position:absolute; inset:3px; border-radius:50%; background:linear-gradient(135deg,var(--brand),var(--brand-2))}
  .muted{color:var(--muted)}
  .table-wrap{overflow:auto}
  .row.eqh > *{height:40px}
  .form select, .form input{height:40px}
</style>

<script>
(function(){
  const API = window.API_BASE || '/Bancalia/api';
  const qs  = (s,r)=> (r||document).querySelector(s);
  const ce  = (t)=> document.createElement(t);
  const show = (el,msg,type)=>{ if(!el)return; el.textContent=msg; el.className='alert '+(type||''); el.hidden=!msg; };

  const form   = qs('#registerForm');
  const alert  = qs('#registerAlert');
  const profWrap = qs('#profWrap');

  const gradoSel = qs('#gradoSelect');
  const cursoSel = qs('#cursoSelect');
  const asigSel  = qs('#asigSelect');
  const btnAdd   = qs('#btnAddImp');
  const tblBody  = qs('#tblImp tbody');
  const impEmpty = qs('#impEmpty');

  const imparte = []; // [{grado_id, grado, curso_id, curso, asignatura_id, asignatura}]

  async function fetchJSON(url){
    const r = await fetch(url, { headers:{ 'Accept':'application/json' } });
    const t = await r.text();
    let j={}; try{ j=JSON.parse(t) }catch{}
    if(!r.ok) throw new Error(j.error || t || ('HTTP '+r.status+' '+url));
    return j;
  }

  const loadGrados      = ()=> fetchJSON(API+'/grados');
  const loadCursos      = (gid)=> fetchJSON(API+'/cursos'      + (gid?('?grado_id='+encodeURIComponent(gid)):'')); 
  const loadAsignaturas = (gid)=> fetchJSON(API+'/asignaturas' + (gid?('?grado_id='+encodeURIComponent(gid)):'')); 

  const populate = (sel, items, value='id', label='nombre')=>{
    sel.innerHTML='';
    (items||[]).forEach(it=>{ const o=ce('option'); o.value=it[value]; o.textContent=it[label]; sel.appendChild(o); });
  };

  async function syncCursoYAsig(){
    const gid = parseInt(gradoSel.value||'0',10);
    const [cursos, asigs] = await Promise.all([ loadCursos(gid), loadAsignaturas(gid) ]);
    populate(cursoSel, cursos);
    populate(asigSel,  asigs);
  }

  function renderImparte(){
    tblBody.innerHTML='';
    if (!imparte.length){
      const tr=ce('tr'); const td=ce('td'); td.colSpan=4; td.textContent='Añade al menos una asignatura'; td.className='muted';
      tr.appendChild(td); tblBody.appendChild(tr); return;
    }
    imparte.forEach((row, idx)=>{
      const tr=ce('tr');
      tr.innerHTML = `<td>${row.grado}</td><td>${row.curso}</td><td>${row.asignatura}</td>`;
      const td=ce('td'); td.style.textAlign='right';
      const b=ce('button'); b.className='btn ghost'; b.textContent='Quitar';
      b.onclick=()=>{ imparte.splice(idx,1); renderImparte(); };
      td.appendChild(b); tr.appendChild(td); tblBody.appendChild(tr);
    });
  }

  // init opciones
  (async ()=>{
    try{
      const grados = await loadGrados();
      populate(gradoSel, grados);
      await syncCursoYAsig();
    }catch(e){ console.error(e); show(alert,'No se pudieron cargar grados/cursos/asignaturas','err'); }
  })();

  gradoSel.addEventListener('change', async ()=>{
    try{ await syncCursoYAsig(); }catch(e){ console.error(e); }
  });

  // Mostrar/ocultar bloque profesor
  form.addEventListener('change', (e)=>{
    if (e.target.name === 'rol'){
      const prof = form.rol.value === 'profesor';
      profWrap.hidden = !prof;
      asigSel.required = prof;
    }
  });

  // Añadir combinación (solo profesor)
  if (btnAdd){
    btnAdd.addEventListener('click', ()=>{
      const gid = parseInt(gradoSel.value||'0',10);
      const cid = parseInt(cursoSel.value||'0',10);
      const aid = parseInt(asigSel.value ||'0',10);
      const gtx = gradoSel.options[gradoSel.selectedIndex]?.text || '';
      const ctx = cursoSel.options[cursoSel.selectedIndex]?.text || '';
      const atx = asigSel.options[asigSel.selectedIndex]?.text || '';

      if (!gid || !cid || !aid){ show(alert,'Selecciona grado, curso y asignatura','err'); return; }
      // evitar duplicados por (curso_id, asignatura_id)
      if (imparte.some(x=> x.curso_id===cid && x.asignatura_id===aid)) return;
      imparte.push({grado_id:gid, grado:gtx, curso_id:cid, curso:ctx, asignatura_id:aid, asignatura:atx});
      renderImparte();
      show(alert,'',null);
    });
  }

  // Envío del formulario
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    show(alert,'',null);
    const fd = new FormData(form);
    const rol = fd.get('rol') || 'alumno';

    const payload = {
      nombre:   fd.get('nombre'),
      email:    fd.get('email'),
      password: fd.get('password'),
      rol
    };

    if (rol === 'alumno'){
      payload.curso_id = parseInt(fd.get('curso_id')||'0',10);
      if (!payload.curso_id){ show(alert,'Selecciona curso','err'); return; }
    } else {
      // profesor → enviamos array imparte
      if (imparte.length === 0){
        show(alert,'Añade al menos una asignatura','err'); return;
      }
      payload.imparte = imparte.map(x=>({
        grado_id: x.grado_id,
        curso_id: x.curso_id,
        asignatura_id: x.asignatura_id
      }));
      // (compat) también mando el primero como curso_id/asignatura_id por si el backend antiguo lo usa
      payload.curso_id = imparte[0].curso_id;
      payload.asignatura_id = imparte[0].asignatura_id;
    }

    try{
      const res = await fetch((window.API_BASE||'/Bancalia/api') + '/register', {
        method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if(!res.ok){ show(alert, data.error||'Error al registrarse','err'); return; }
      show(alert,'Cuenta creada. Ya puedes iniciar sesión.','ok');
      setTimeout(()=> location.href = '/Bancalia/public/login', 800);
    }catch(err){
      console.error(err);
      show(alert,'No se pudo conectar con el servidor','err');
    }
  });
})();
</script>
