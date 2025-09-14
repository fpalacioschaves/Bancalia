// /Bancalia/public/assets/js/listing.js
(function (global) {
  const API = global.API_BASE || '/Bancalia/api';

  function debounce(fn, ms) {
    let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); };
  }
  async function fetchJSON(url) {
    const r = await fetch(url, { headers:{'Accept':'application/json'} });
    const t = await r.text(); let j={}; try{ j=JSON.parse(t) }catch{}
    if(!r.ok) throw new Error((j&&j.error)||t||('HTTP '+r.status));
    return j;
  }

  function makeAjaxSearch({ form, input, btn, endpoint, params, render, onError }) {
    const load = async (page=1)=>{
      try{
        const p = new URLSearchParams({ page: String(page), per_page:'20', ...(params?params():{}) });
        const data = await fetchJSON(API + endpoint + '?' + p.toString());
        render(data.items||[]);
      }catch(e){ onError && onError(e); }
    };
    if (form) form.addEventListener('submit', (e)=>{ e.preventDefault(); load(1); });
    if (btn)  btn.addEventListener('click', ()=> load(1));
    if (input) input.addEventListener('input', debounce(()=> load(1), 250));
    load(1);
    return { reload: load };
  }

  global.BancaliaList = { makeAjaxSearch };
})(window);
