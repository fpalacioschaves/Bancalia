// /Bancalia/public/assets/js/bancalia.list.js
(function (w, d) {
  'use strict';

  const API = w.API_BASE || '/Bancalia/api';

  function qs(sel, root){ return (root||d).querySelector(sel); }
  function ce(t){ return d.createElement(t); }

  class BancaliaList {
    constructor(opts){
      this.table    = typeof opts.table === 'string' ? qs(opts.table) : opts.table;
      this.tbody    = this.table ? this.table.querySelector('tbody') : null;
      this.form     = typeof opts.form === 'string' ? qs(opts.form) : opts.form;
      this.alertBox = opts.alert ? (typeof opts.alert === 'string' ? qs(opts.alert) : opts.alert) : null;

      this.endpoint   = opts.endpoint;                  // p.ej. '/profesor/actividades'
      this.buildQuery = opts.buildQuery || (()=>({}));  // -> objeto con params
      this.renderRow  = opts.renderRow;                 // (item)=>HTMLElement <tr>
      this.perPage    = opts.perPage || 20;

      if (!this.table || !this.tbody) throw new Error('BancaliaList: tabla/tbody no encontrados');
      if (!this.endpoint) throw new Error('BancaliaList: endpoint requerido');
      if (typeof this.renderRow !== 'function') throw new Error('BancaliaList: renderRow requerido');

      this._bind();
    }

    _bind(){
      // submit de formulario
      if (this.form) {
        this.form.addEventListener('submit', (e)=>{ e.preventDefault(); this.load(1); });
      }
    }

    _showAlert(msg, type){
      if (!this.alertBox) return;
      this.alertBox.textContent = msg || '';
      this.alertBox.className   = 'alert ' + (type||'');
      this.alertBox.hidden      = !msg;
    }

    async _get(url){
      const r = await fetch(url, { headers:{'Accept':'application/json'} });
      const t = await r.text(); let j={}; try{ j=JSON.parse(t) }catch{}
      if (!r.ok) throw new Error((j&&j.error)||t||('HTTP '+r.status));
      return j;
    }

    _paint(items){
      this.tbody.innerHTML = '';
      if (!items || !items.length){
        const tr=ce('tr'); const td=ce('td');
        td.colSpan = this.table.querySelectorAll('thead th').length;
        td.textContent = 'Sin resultados'; td.className='muted';
        tr.appendChild(td); this.tbody.appendChild(tr);
        return;
      }
      items.forEach(it => {
        const tr = this.renderRow(it);
        this.tbody.appendChild(tr);
      });
    }

    async load(page){
      this._showAlert('', null);
      const params = new URLSearchParams(this.buildQuery() || {});
      params.set('page', page || 1);
      params.set('per_page', String(this.perPage));
      try{
        const data = await this._get(API + this.endpoint + '?' + params.toString());
        this._paint(data.items || []);
      }catch(e){
        console.error(e);
        this._showAlert(e.message || 'No se pudo cargar', 'err');
        this._paint([]);
      }
    }
  }

  w.BancaliaList = BancaliaList;
})(window, document);
