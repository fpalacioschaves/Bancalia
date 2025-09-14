(() => {
  // Helpers globales
  window.$fetchJSON = function (url, opts) {
    return fetch(url, opts)
      .then(r => r.json().catch(() => ({ ok: false, error: 'Error' })))
      .then(j => {
        if (j && j.ok === false) throw new Error(j.error || 'Error');
        return j;
      });
  };

  window.$setBtnState = function (btn, disabled) {
    if (!btn) return;
    btn.disabled = !!disabled;
    btn.classList.toggle('btn-disabled', !!disabled);
  };

  // Tabs
  function setActiveTab(name) {
    document.querySelectorAll('.tab').forEach(el => el.classList.add('hidden'));
    const active = document.getElementById('tab-' + name);
    if (active) active.classList.remove('hidden');
    document.querySelectorAll('.tablink').forEach(b => {
      b.setAttribute('aria-selected', String(b.dataset.tab === name));
    });
  }
  window.$setActiveTab = setActiveTab;

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tablink').forEach(b => {
      b.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveTab(b.dataset.tab);
      });
    });
    setActiveTab(window.ACTIVE_TAB || 'catalogo');
  });
})();
