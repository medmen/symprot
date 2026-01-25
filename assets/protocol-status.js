(function(){
  // Ensure the async=1 param is added when landing on /process_upload with a path and no token.
  // This was previously inline in index.html.twig; moved here to keep JS consolidated.
  (function ensureAsyncParam(){
    try{
      var url = new URL(window.location.href, window.location.origin);
      // Only auto-append async=1 on the processing page when a path is present and no token yet
      if (url.pathname.indexOf('/process_upload') !== -1) {
        if (!url.searchParams.has('async') && url.searchParams.has('path') && !url.searchParams.has('token')) {
          url.searchParams.set('async', '1');
          // Use replace() to avoid back button ping-pong
          window.location.replace(url.toString());
        }
      }
    }catch(e){ /* ignore */ }
  })();

  function start(container){
    if(!container) return;
    var startUrl = container.getAttribute('data-start');
    var url = container.getAttribute('data-url');
    var token = container.getAttribute('data-token');
    var logEl = document.getElementById('status-log');
    if(!url || !token || !logEl){
      return;
    }
    var lastRendered = [];
    var done = false;
    var started = false;
    var redirected = false;
    var redirectUrl = container.getAttribute('data-redirect') || '';

    function render(lines){
      if(!Array.isArray(lines)) return;
      if(lines.join('\n') === lastRendered.join('\n')) return;
      lastRendered = lines.slice();
      logEl.textContent = lines.join('\n');
      logEl.scrollTop = logEl.scrollHeight;
    }

    function poll(){
      if(done) return;
      fetch(url, { credentials: 'same-origin' })
        .then(function(res){
          if(res.status === 403){
            done = true;
            render(["[END FAIL] Zugriff verweigert (Token)"]); return {lines: lastRendered, done: true};
          }
          return res.json();
        })
        .then(function(data){
          if(!data) return;
          if(Array.isArray(data.lines)){
            render(data.lines);
          }
          if(data.done){
            done = true;
            // Decide based on last line
            try{
              var last = lastRendered.length ? lastRendered[lastRendered.length - 1] : '';
              if(redirectUrl && typeof last === 'string' && last.indexOf('[END OK]') === 0 && !redirected){
                redirected = true;
                setTimeout(function(){ window.location.assign(redirectUrl); }, 500);
              }
            } catch(e) {}
          }
        })
        .catch(function(err){
          // Keep errors non-fatal but inform the user
          render(lastRendered.concat(['[warn] Netzwerkfehler: ' + (err && err.message ? err.message : err)]));
        })
        .finally(function(){
          if(!done){
            setTimeout(poll, 2000);
          }
        });
    }

    function startRemote(){
      if(started || !startUrl) return;
      started = true;
      // Fire-and-forget to trigger processing on server; do not block UI
      fetch(startUrl, { credentials: 'same-origin' })
        .catch(function(){ /* ignore */ });
    }

    // kick off
    startRemote();
    poll();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      start(document.getElementById('protocol-status'));
    });
  } else {
    start(document.getElementById('protocol-status'));
  }
})();
