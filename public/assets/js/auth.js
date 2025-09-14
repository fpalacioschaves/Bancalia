(function(){
  const API = window.API_BASE || '/Bancalia/api';
  const qs  = (s, r)=> (r||document).querySelector(s);
  const ce  = (t)=> document.createElement(t);
  const show = (el,msg,type)=>{ if(!el) return; el.textContent=msg; el.className='alert '+(type||''); el.hidden=!msg; };

  async function fetchJSON(url){
    const r = await fetch(url, { headers:{ 'Accept':'application/json' } });
    const t = await r.text();
    let j={}; try{ j=JSON.parse(t) }catch{}
    if(!r.ok) throw new Error(j.error || t || ('HTTP '+r.status+' '+url));
    return j;
  }
  const loadGrados       = ()=> fetchJSON(API+'/grados');
  const loadCursos       = (gradoId)=> fetchJSON(API+'/cursos'      + (gradoId?('?grado_id='+encodeURIComponent(gradoId)):'')); 
  const loadAsignaturas  = (gradoId)=> fetchJSON(API+'/asignaturas' + (gradoId?('?grado_id='+encodeURIComponent(gradoId)):'')); 

  document.addEventListener('DOMContentLoaded', () => {
    /* ===== Registro ===== */
    const regForm  = qs('#registerForm');
    if (regForm){
      const alert    = qs('#registerAlert');
      const gradoSel = qs('#gradoSelect');
      const cursoSel = qs('#cursoSelect');
      const asigSel  = qs('#asigSelect');
      const profWrap = qs('#profWrap');
      const btnAdd   = qs('#btnAddImp');
      const tblBody  = qs('#tblImp tbody');

      const imparte = [];

      const populate = (sel, items, valueKey='id', labelKey='nombre')=>{
        sel.innerHTML=''; (items||[]).forEach(it=>{ const o=document.createElement('option'); o.value=it[valueKey]; o.textContent=it[labelKey]; sel.appendChild(o); });
      };
      const updateCursoYAsig = async ()=>{
        const gradoId = parseInt(gradoSel.value||'0',10);
        const [cursos, asignaturas] = await Promise.all([ loadCursos(gradoId), loadAsignaturas(gradoId) ]);
        populate(cursoSel, cursos);
        populate(asigSel, asignaturas);
      };
      const renderImparte = ()=>{
        tblBody.innerHTML='';
        if (!imparte.length){
          const tr=document.createElement('tr'); const td=document.createElement('td'); td.colSpan=4; td.textContent='Añade al menos una asignatura'; td.className='muted';
          tr.appendChild(td); tblBody.appendChild(tr); return;
        }
        imparte.forEach((x,i)=>{
          const tr=document.createElement('tr');
          tr.innerHTML = `<td>${x.grado}</td><td>${x.curso}</td><td>${x.asignatura}</td>`;
          const td=document.createElement('td'); td.style.textAlign='right';
          const b=document.createElement('button'); b.className='btn ghost'; b.textContent='Quitar';
          b.onclick=()=>{ imparte.splice(i,1); renderImparte(); };
          td.appendChild(b); tr.appendChild(td); tblBody.appendChild(tr);
        });
      };

      (async ()=>{
        try{ const grados = await loadGrados(); populate(gradoSel, grados); await updateCursoYAsig(); }
        catch(e){ console.error(e); show(alert,'No se pudo cargar grados','err'); }
      })();

      gradoSel.addEventListener('change', async ()=>{ try{ await updateCursoYAsig(); }catch(e){ console.error(e); } });

      regForm.addEventListener('change', (e)=>{
        if (e.target.name === 'rol'){
          const prof = regForm.rol.value === 'profesor';
          profWrap.hidden  = !prof;
          asigSel.required =  prof;
        }
      });

      if (btnAdd){
        btnAdd.addEventListener('click', ()=>{
          const gid = parseInt(gradoSel.value||'0',10);
          const cid = parseInt(cursoSel.value||'0',10);
          const aid = parseInt(asigSel.value ||'0',10);
          const gtx = gradoSel.options[gradoSel.selectedIndex]?.text || '';
          const ctx = cursoSel.options[cursoSel.selectedIndex]?.text || '';
          const atx = asigSel.options[asigSel.selectedIndex]?.text || '';
          if (!gid || !cid || !aid){ show(alert,'Selecciona grado, curso y asignatura','err'); return; }
          if (imparte.some(x=> x.curso_id===cid && x.asignatura_id===aid)) return;
          imparte.push({grado_id:gid, grado:gtx, curso_id:cid, curso:ctx, asignatura_id:aid, asignatura:atx});
          renderImparte();
          show(alert,'',null);
        });
      }

      regForm.addEventListener('submit', async (e)=>{
        e.preventDefault(); show(alert,'',null);
        const fd = new FormData(regForm);
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
          if (!imparte.length){ show(alert,'Añade al menos una asignatura','err'); return; }
          payload.imparte = imparte.map(x=>({ grado_id:x.grado_id, curso_id:x.curso_id, asignatura_id:x.asignatura_id }));
          payload.curso_id = imparte[0].curso_id;           // compat
          payload.asignatura_id = imparte[0].asignatura_id; // compat
        }

        try{
          const res  = await fetch(API + '/register', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify(payload) });
          const data = await res.json();
          if(!res.ok){ show(alert, data.error||'Error al registrarse','err'); return; }
          show(alert,'Cuenta creada. Ya puedes iniciar sesión.','ok');
          setTimeout(()=> location.href = '/Bancalia/public/login', 700);
        }catch(err){ console.error(err); show(alert, 'No se pudo conectar con el servidor','err'); }
      });
    }

    /* ===== Login ===== */
    const loginForm = qs('#loginForm');
    if (loginForm){
      const alert = qs('#loginAlert');
      loginForm.addEventListener('submit', async (e)=>{
        e.preventDefault();
        show(alert,'',null);
        const fd = new FormData(loginForm);
        const payload = { email: fd.get('email'), password: fd.get('password') };

        try{
          const res  = await fetch(API + '/login', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify(payload) });
          const data = await res.json();
          if(!res.ok){ show(alert, data.error||'Credenciales inválidas','err'); return; }

          show(alert,'Sesión iniciada. Redirigiendo…','ok');

          const backendRedirect = (data && typeof data.redirect === 'string') ? data.redirect : '';
          const rol  = parseInt((data && data.user && data.user.rol_id) ? data.user.rol_id : 3, 10);
          const fallback = (rol === 1) ? '/Bancalia/public/admin'
                          : (rol === 2) ? '/Bancalia/public/profesor'
                                        : '/Bancalia/public/alumno/actividades';
          const dest = backendRedirect || fallback;
          setTimeout(()=> { location.href = dest; }, 300);
        }catch(err){
          console.error(err);
          show(alert,'No se pudo conectar con el servidor','err');
        }
      });
    }
  });
})();
