(function(){
  function startPolling(container){
    if(!container) return;
    var url = container.getAttribute('data-url');
    var token = container.getAttribute('data-token');
    var logEl = document.getElementById('status-log');
    if(!url || !token || !logEl){
      return;
    }
    var lastRendered = [];
    var done = false;

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
          }
        })
        .catch(function(err){
          // Keep errors non-fatal but inform the user
          render(lastRendered.concat(['[warn] Netzwerkfehler: ' + (err && err.message ? err.message : err)]));
        })
        .finally(function(){
          if(!done){
            setTimeout(poll, 1000);
          }
        });
    }

    poll();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      startPolling(document.getElementById('protocol-status'));
    });
  } else {
    startPolling(document.getElementById('protocol-status'));
  }
})();
