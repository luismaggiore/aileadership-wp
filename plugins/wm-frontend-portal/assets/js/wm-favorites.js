(function(){
  const nf = new Intl.NumberFormat(WMFAV.locale || 'es');

  function paint(btn){
    const countEl = btn.querySelector('.like-count');
    const icon    = btn.querySelector('.bi');
    const fav     = btn.dataset.fav === '1';
    const count   = parseInt(btn.dataset.count || '0', 10);
    if (countEl) countEl.textContent = nf.format(Math.max(0, count));
    if (icon) { icon.classList.toggle('bi-heart-fill', fav); icon.classList.toggle('bi-heart', !fav); }
    btn.setAttribute('aria-pressed', fav ? 'true' : 'false');
    btn.setAttribute('aria-label', fav ? (WMFAV.i18n?.remove || 'Quitar') : (WMFAV.i18n?.add || 'Agregar'));
  }

  function initAll(){ document.querySelectorAll('.btn-like').forEach(paint); }

  function updateAllForTopic(id, fav, count){
    document.querySelectorAll('.btn-like[data-topic="'+id+'"]').forEach(b=>{
      b.dataset.fav = fav ? '1' : '0';
      b.dataset.count = String(Math.max(0, count));
      paint(b);
    });
  }

  document.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.btn-like');
    if (!btn) return;
    if (btn.tagName.toLowerCase() === 'a') return; // no logged-in, leave

    ev.preventDefault();
    const id = btn.dataset.topic;
    const wasFav = btn.dataset.fav === '1';
    const wasCnt = parseInt(btn.dataset.count || '0', 10);
    const nextFav= !wasFav;
    const nextCnt= nextFav ? wasCnt + 1 : Math.max(0, wasCnt - 1);

    updateAllForTopic(id, nextFav, nextCnt); // optimistic

    btn.disabled = true;
    try{
      const res = await fetch(WMFAV.ajax, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action:'wm_toggle_favorite', nonce: WMFAV.nonce, topic_id: id })
      });
      const json = await res.json();
      if (!json || !json.success) {
        updateAllForTopic(id, wasFav, wasCnt);
        if (json && json.data && json.data.error === 'auth') {
          window.location.href = (WMFAV.login || '/acceso/') + '?redirect_to=' + encodeURIComponent(window.location.href);
          return;
        }
        throw new Error('toggle_failed');
      }
      updateAllForTopic(id, !!json.data.favorited, parseInt(json.data.count || '0', 10));
    }catch(e){ console.error(e); }
    finally{ btn.disabled=false; }
  });

  (document.readyState === 'loading') ? document.addEventListener('DOMContentLoaded', initAll) : initAll();
})();
