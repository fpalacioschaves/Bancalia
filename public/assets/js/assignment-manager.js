/**
 * AssignmentManager
 * Handles dynamic rows for professor assignments (Familia -> Curso -> Asignatura).
 */
class AssignmentManager {
    constructor(config) {
        this.containerId = config.containerId || 'rowsBody'; // Tbody ID
        this.btnAddId = config.btnAddId || 'btnAddRow';     // Button ID
        this.data = {
            familias: config.familias || [],
            cursos: config.cursos || [],
            asignaturas: config.asignaturas || []
        };
        
        // Maps for cascading
        this.cursosByFam = {};
        this.asigByCurso = {};
        this._buildMaps();

        this.container = document.getElementById(this.containerId);
        this.btnAdd = document.getElementById(this.btnAddId);

        if (this.btnAdd) {
            this.btnAdd.addEventListener('click', () => this.addRow());
        }

        // Initialize existing rows (if any)
        this.initExistingRows();
    }

    _buildMaps() {
        this.data.cursos.forEach(c => {
            (this.cursosByFam[c.familia_id] ||= []).push({ id: c.id, nombre: c.nombre });
        });
        this.data.asignaturas.forEach(a => {
            (this.asigByCurso[a.curso_id] ||= []).push({ id: a.id, nombre: a.nombre });
        });
    }

    // Generate <option> element
    _opt(val, label) {
        const o = document.createElement('option');
        o.value = val;
        o.textContent = label;
        return o;
    }

    // Render Cursos select based on Familia ID
    renderCursos(sel, famId, selectedId = null) {
        sel.innerHTML = '';
        sel.appendChild(this._opt('', '— Curso —'));
        const list = this.cursosByFam[famId] || [];
        list.forEach(c => {
            const o = this._opt(c.id, c.nombre);
            if (selectedId && String(selectedId) === String(c.id)) o.selected = true;
            sel.appendChild(o);
        });
    }

    // Render Asignaturas select based on Curso ID
    renderAsignaturas(sel, curId, selectedId = null) {
        sel.innerHTML = '';
        sel.appendChild(this._opt('', '— Asignatura —'));
        const list = this.asigByCurso[curId] || [];
        list.forEach(a => {
            const o = this._opt(a.id, a.nombre);
            if (selectedId && String(selectedId) === String(a.id)) o.selected = true;
            sel.appendChild(o);
        });
    }

    // Initialize logic for rows already present in HTML (server-rendered)
    initExistingRows() {
        if (!this.container) return;
        const rows = this.container.querySelectorAll('tr');
        rows.forEach((tr, idx) => {
            // Check if we have pre-loaded data (e.g. from a JS variable 'existingRows')
            // Otherwise, rely on DOM attributes if available
            this._attachRowEvents(tr);

            // Trigger initial render if needed (optional, assuming PHP rendered options correctly)
            // But if PHP rendered options, we just need to attach change events.
            // If PHP *didn't* render dependent options (only selected value), we might need to trigger change.
        });
    }

    // Add a new empty row
    addRow(prefill = {}) {
        const tr = document.createElement('tr');
        
        // Build HTML template
        // Note: We use simpler class names for selection
        const famOptions = this.data.familias.map(f => `<option value="${f.id}">${f.nombre}</option>`).join('');
        
        // Calculate default Academic Year: if month >= 9 then Y-(Y+1), else (Y-1)-Y
        const d = new Date();
        const m = d.getMonth() + 1; // 1-12
        const y = d.getFullYear();
        const defaultAnio = (m >= 9) ? `${y}-${y+1}` : `${y-1}-${y}`;
        const valAnio = prefill.anio || defaultAnio;

        tr.innerHTML = `
            <td class="px-3 py-2">
                <input type="hidden" name="pa_id[]" value="${prefill.pa_id || ''}">
                <select name="asig_familia_id[]" class="js-fam w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <option value="">— Familia/Grado —</option>
                    ${famOptions}
                </select>
            </td>
            <td class="px-3 py-2">
                <select name="asig_curso_id[]" class="js-cur w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <option value="">— Curso —</option>
                </select>
            </td>
            <td class="px-3 py-2">
                <select name="asig_asignatura_id[]" class="js-asig w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <option value="">— Asignatura —</option>
                </select>
            </td>
            <td class="px-3 py-2">
                <input name="asig_anio[]" type="text" value="${valAnio}" placeholder="2025-2026" required
                       class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
            </td>
            <td class="px-3 py-2">
                <input name="asig_horas[]" type="number" min="0" value="${prefill.horas || ''}"
                       class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
            </td>
            <td class="px-3 py-2">
                <input name="asig_obs[]" type="text" value="${prefill.obs || ''}"
                       class="w-48 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400" placeholder="Notas">
            </td>
            <td class="px-3 py-2 text-right">
                <button type="button" class="js-del inline-flex items-center rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">Quitar</button>
            </td>
        `;

        this.container.appendChild(tr);
        this._attachRowEvents(tr);

        // Prefill logic
        if (prefill.familia_id) {
            const selFam = tr.querySelector('.js-fam');
            selFam.value = prefill.familia_id;
            // Trigger change manually or call render directly
            const selCur = tr.querySelector('.js-cur');
            this.renderCursos(selCur, prefill.familia_id, prefill.curso_id);
            
            if (prefill.curso_id) {
                const selAsig = tr.querySelector('.js-asig');
                this.renderAsignaturas(selAsig, prefill.curso_id, prefill.asignatura_id);
            }
        }
    }

    _attachRowEvents(tr) {
        const selFam = tr.querySelector('.js-fam') || tr.querySelector('.famSel'); // Support legacy class if needed
        const selCur = tr.querySelector('.js-cur') || tr.querySelector('.cursoSel');
        const selAsig = tr.querySelector('.js-asig') || tr.querySelector('.asigSel');
        const btnDel = tr.querySelector('.js-del') || tr.querySelector('.btnDelNew');

        if (selFam) {
            selFam.addEventListener('change', () => {
                const fid = parseInt(selFam.value || 0);
                this.renderCursos(selCur, fid);
                this.renderAsignaturas(selAsig, 0); // Reset asignatura
            });
        }

        if (selCur) {
            selCur.addEventListener('change', () => {
                const cid = parseInt(selCur.value || 0);
                this.renderAsignaturas(selAsig, cid);
            });
        }

        if (btnDel) {
            btnDel.addEventListener('click', () => {
                tr.remove();
            });
        }
    }
}
