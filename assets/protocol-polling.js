// CSP-safe external module to poll background job status and inject output
// Expects the page to contain:
//  - #job-root with data-job-id, data-status-url, data-output-url
//  - #progress_bar with child .fill and .label
//  - #progress_msg, #protocol_output

function selectProgressElements() {
  const bar = document.getElementById('progress_bar');
  return {
    bar,
    fill: bar ? bar.querySelector('.fill') : null,
    label: bar ? bar.querySelector('.label') : null,
    msg: document.getElementById('progress_msg'),
    out: document.getElementById('protocol_output'),
  };
}

function setPct(els, p) {
  if (!els.fill || !els.label) return;
  const pct = Math.max(0, Math.min(100, Math.round(p)));
  els.fill.style.width = pct + '%';
  els.label.textContent = pct + '%';
}

async function pollUntilDone(statusUrl, onUpdate) {
  // simple long-poll loop using setTimeout, returns final status
  // onUpdate(statusObj) called every tick
  for (;;) {
    try {
      const r = await fetch(statusUrl, { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      const s = await r.json();
      onUpdate(s);
      if (s.status === 'done' || s.status === 'failed') {
        return s;
      }
    } catch (e) {
      // swallow transient network errors
      // optionally log to console in dev
      // console.warn('poll error', e);
    }
    await new Promise(res => setTimeout(res, 1000));
  }
}

async function loadOutputHtml(outputUrl) {
  for (;;) {
    const r = await fetch(outputUrl, { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'text/html' } });
    if (r.status === 202) {
      await new Promise(res => setTimeout(res, 500));
      continue;
    }
    return await r.text();
  }
}

export function initProtocolPolling(root) {
  const jobId = root.getAttribute('data-job-id');
  const statusUrl = root.getAttribute('data-status-url');
  const outputUrl = root.getAttribute('data-output-url');
  if (!jobId || !statusUrl || !outputUrl) return;

  const els = selectProgressElements();
  let done = false;

  const onUpdate = (s) => {
    if (s && typeof s.percent === 'number') setPct(els, s.percent);
    if (els.msg && s && s.message) els.msg.textContent = s.message;
  };

  pollUntilDone(statusUrl, onUpdate).then(async (s) => {
    done = true;
    if (!s) return;
    if (s.status === 'done') {
      if (els.msg) els.msg.textContent = 'Fertig';
      const html = await loadOutputHtml(outputUrl);
      if (els.out) els.out.innerHTML = html;
    } else if (s.status === 'failed') {
      if (els.msg) els.msg.textContent = 'Fehler: ' + (s.error || 'Unbekannt');
      if (els.out) els.out.innerHTML = '<div class="alert alert-danger">Verarbeitung fehlgeschlagen: ' + (s.error || 'Unbekannt') + '</div>';
    }
  });
}

export function boot() {
  const root = document.getElementById('job-root');
  if (root) {
    // unhide marker for debugging if needed
    root.hidden = true;
    initProtocolPolling(root);
  }
}
