(function(){
  const API = window.API_BASE || '/Bancalia/api';
  const qs = (s, r)=> (r||document).querySelector(s);
  const show = (el,msg,type)=>{ if(!el) return; el.textContent=msg; el.className='alert '+(type||''); el.hidden=!msg; };

  // Helpers catálogo
  async function fetchJSON(url){
    const r = await fetch(url, { headers:{ 'Accept':'application/json' } });
    if(!r.ok) throw new Error('HTTP '+r.status+' '+url);
    return r.json();
  }
  const loadGrados       = ()=> fetchJSON(API+'/grados');
  const loadCursos       = (gradoId)=> fetchJSON(API+'/cursos'      + (gradoId?('?grado_id='+encodeURIComponent(gradoId)):''));
  const loadAsignaturas  = (gradoId)=> fetchJSON(API+'/asignaturas' + (gradoId?('?grado_id='+encodeURIComponent(gradoId)):''));

  document.addEventListener('DOMContentLoaded', () => {
    // ===== Registro =====
    const regForm  = qs('#registerForm');
    if (regForm){
      const alert    = qs('#registerAlert');
      const gradoSel = qs('#gradoSelect');
      const cursoSel = qs('#cursoSelect');
      const asigWrap = qs('#asigWrap');
      const asigSel  = qs('#asigSelect');

      const populate = (sel, items, valueKey='id', labelKey='nombre')=>{
        sel.innerHTML = '';
        items.forEach(it=>{
          const o = document.createElement('option');
          o.value = it[valueKey]; o.textContent = it[labelKey];
          sel.appendChild(o);
        });
      };

      const updateCursoYAsig = async ()=>{
        const gradoId = parseInt(gradoSel.value||'0',10);
        try{
          const [cursos, asignaturas] = await Promise.all([
            loadCursos(gradoId),
            loadAsignaturas(gradoId)
          ]);
          populate(cursoSel, cursos);
          populate(asigSel, asignaturas);
        }catch(e){
          console.error(e);
          show(alert,'No se pudo cargar cursos/asignaturas','err');
        }
      };

      // Cargar grados y luego cursos/asignaturas del primero
      (async ()=>{
        try{
          const grados = await loadGrados();
          populate(gradoSel, grados);
          await updateCursoYAsig();
        }catch(e){
          console.error(e);
          show(alert,'No se pudo cargar grados','err');
        }
      })();

      gradoSel.addEventListener('change', updateCursoYAsig);

      // Mostrar/ocultar asignatura según rol
      regForm.addEventListener('change', (e)=>{
        if (e.target.name === 'rol'){
          const prof = regForm.rol.value === 'profesor';
          asigWrap.hidden  = !prof;
          asigSel.required =  prof;
        }
      });

      regForm.addEventListener('submit', async (e)=>{
        e.preventDefault();
        show(alert,'',null);
        const fd = new FormData(regForm);
        const payload = {
          nombre:   fd.get('nombre'),
          email:    fd.get('email'),
          password: fd.get('password'),
          rol:      fd.get('rol') || 'alumno',
          curso_id: parseInt(fd.get('curso_id')||'0',10)
        };
        if (payload.rol === 'profesor') {
          payload.asignatura_id = parseInt(fd.get('asignatura_id')||'0',10);
        }
        try{
          const res  = await fetch(API+'/register', {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'Accept':'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if(!res.ok){ show(alert, data.error||'Error al registrarse','err'); return; }
          show(alert,'Cuenta creada. Ya puedes iniciar sesión.','ok');
          setTimeout(()=> location.href='/Bancalia/public/login', 700);
        }catch(err){
          console.error(err);
          show(alert,'No se pudo conectar con el servidor','err');
        }
      });

      // Estado inicial del selector de asignatura
      const profInit = regForm.rol.value === 'profesor';
      asigWrap.hidden  = !profInit;
      asigSel.required =  profInit;
    }

    // ===== Login =====
    const loginForm = qs('#loginForm');
    if (loginForm){
      const alert = qs('#loginAlert');
      loginForm.addEventListener('submit', async (e)=>{
        e.preventDefault();
        show(alert,'',null);
        const fd = new FormData(loginForm);
        const payload = { email: fd.get('email'), password: fd.get('password') };
        try{
          const res  = await fetch(API+'/login', {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'Accept':'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if(!res.ok){ show(alert, data.error||'Error al iniciar sesión','err'); return; }
          show(alert,'Sesión iniciada. Redirigiendo…','ok');
          const rol  = (data.user && data.user.rol_id) || 3;
          const dest = (rol===2) ? '/Bancalia/public/profesor/actividades' : '/Bancalia/public/alumno/actividades';
          setTimeout(()=> location.href = dest, 600);
        }catch(err){
          console.error(err);
          show(alert,'No se pudo conectar con el servidor','err');
        }
      });
    }
  });
})();
