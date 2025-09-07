// Filter Drawer JS — robust opener using [aria-controls="filter-drawer"]
(() => {
  const qs  = (sel, root = document) => root.querySelector(sel);
  const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const drawer   = document.getElementById('filter-drawer');
  if (!drawer) return;

  const panel    = qs('.fdrawer__panel', drawer);
  const backdrop = qs('.fdrawer__backdrop', drawer);

  // Any element that points at the drawer ID will open it
  const openers  = qsa('[aria-controls="filter-drawer"]');   // opener(s)
  const closeBtn = qs('.fdrawer__close', drawer);

  const applyBtn = qs('.fdrawer .btn-block', drawer);
  const clearIn  = qs('.fdrawer__clear', drawer);
  const clearOut = qs('.toolbar .link-strong');

  const inputs = () => qsa('input, select, textarea', drawer);
  let lastFocused = null;

  /* ---------- open / close ---------- */
  function openDrawer(e) {
    e?.preventDefault?.();
    if (drawer.classList.contains('is-open')) return;
    lastFocused = document.activeElement;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    openers.forEach(b => b.setAttribute('aria-expanded', 'true'));
    document.body.classList.add('no-scroll');
    const first = panel.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    (first || panel).focus();
  }
  function closeDrawer() {
    if (!drawer.classList.contains('is-open')) return;
    drawer.classList.remove('is-open','show-location','show-capacity','show-venue-type','show-features','show-wedding-style');
    drawer.setAttribute('aria-hidden', 'true');
    openers.forEach(b => b.setAttribute('aria-expanded', 'false'));
    document.body.classList.remove('no-scroll');
    if (lastFocused && document.contains(lastFocused)) lastFocused.focus();
  }

  // Focus trap
  function trapFocus(e){
    if (!drawer.classList.contains('is-open') || e.key !== 'Tab') return;
    const focusables = qsa('button, [href], input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])', panel)
      .filter(el => !el.hasAttribute('disabled'));
    if (!focusables.length) return;
    const first = focusables[0], last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }

  /* ---------- counters ---------- */
  function getSelections(){
    let count = 0; const data = {};
    inputs().forEach(el => {
      const tag = el.tagName.toLowerCase(), type = (el.type||'').toLowerCase();
      if (el.disabled || type === 'hidden') return;
      if (type === 'checkbox' || type === 'radio') { if (el.checked) { count++; add(el.name||'cb', el.value||true, type==='radio'); } return; }
      if (tag === 'select') { const v = el.value, d = el.dataset.defaultValue ?? (el.options[0]?.value ?? ''); if (v !== '' && v !== d){ count++; add(el.name||'select', v, true);} return; }
      if (['text','search','number','tel','email','url','date','time','datetime-local','range'].includes(type) || tag==='textarea'){
        const v=(el.value||'').trim(), d=(el.defaultValue||'').trim(); if (v!=='' && v!==d){ count++; add(el.name||`${tag}`, v, true); }
      }
    });
    function add(k,v,replace=false){ if(replace) data[k]=v; else { if(!data[k]) data[k]=[]; data[k].push(v);} }
    return {count,data};
  }
  function updateCounters(){
    const {count} = getSelections();
    if (applyBtn) applyBtn.textContent = `Apply Filters (${count})`;
    if (clearIn)  clearIn.textContent = `Clear All (${count})`;
    if (clearOut) clearOut.innerHTML  = `Clear all filters <span class="muted">(${count})</span>`;
  }
  function clearAll(){
    inputs().forEach(el=>{
      const tag = el.tagName.toLowerCase(), type=(el.type||'').toLowerCase();
      if (type==='checkbox' || type==='radio') { el.checked=false; return; }
      if (tag==='select') { el.selectedIndex=0; return; }
      if (['text','search','number','tel','email','url','date','time','datetime-local','range'].includes(type)||tag==='textarea'){ el.value = el.defaultValue||''; }
    });
    updateCounters();
  }

  /* ---------- subpanel routing ---------- */
  const routeMap = { location:'show-location', capacity:'show-capacity', 'venue-type':'show-venue-type', features:'show-features', 'wedding-style':'show-wedding-style' };
  function showPanel(key){
    drawer.classList.remove('show-location','show-capacity','show-venue-type','show-features','show-wedding-style');
    if (key) drawer.classList.add(routeMap[key]);
  }

  /* ---------- events ---------- */
  openers.forEach(btn => btn.addEventListener('click', openDrawer));
  closeBtn?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); trapFocus(e); });

  // categories → subpanels
  qsa('.fdrawer__item', drawer).forEach(btn => btn.addEventListener('click', () => showPanel(btn.dataset.panel)));
  qsa('.fsub__back', drawer).forEach(btn => btn.addEventListener('click', () => showPanel(null)));

  // counters
  drawer.addEventListener('change', updateCounters);
  drawer.addEventListener('input', updateCounters);
  const mo = new MutationObserver(updateCounters);
  mo.observe(drawer, { childList: true, subtree: true });
  updateCounters();

  // clear/apply
  clearIn?.addEventListener('click', e => { e.preventDefault(); clearAll(); });
  clearOut?.addEventListener('click', e => { e.preventDefault(); clearAll(); });
  applyBtn?.addEventListener('click', () => {
    const selections = getSelections();
    document.dispatchEvent(new CustomEvent('filters:apply', { detail: selections }));
    closeDrawer();
  });

  // example listener
  document.addEventListener('filters:apply', (e)=>console.log('Selected filters:', e.detail));
})();