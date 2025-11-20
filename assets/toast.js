// Flash message auto-hide and manual close behavior
// Shows each .alert for 7 seconds, then fades it out. Clicking the X hides it immediately.
(function () {
  const AUTO_HIDE_MS = 7000;

  function hideWithFade(el) {
    if (!el) return;
    // Trigger fade-out by removing 'show'
    el.classList.remove('show');
    // After CSS transition ends (or fallback), remove from layout
    const duration = 300; // ms, typical Bootstrap fade duration
    let done = false;
    const onEnd = () => {
      if (done) return;
      done = true;
      el.style.display = 'none';
    };
    el.addEventListener('transitionend', onEnd, { once: true });
    // Fallback timeout in case transitionend doesn't fire
    setTimeout(onEnd, duration + 50);
  }

  function setupAlert(alertEl) {
    if (!alertEl || alertEl.dataset.toastInitialized === '1') return;
    alertEl.dataset.toastInitialized = '1';

    // Ensure fade-in state so that removing 'show' later will animate
    alertEl.classList.add('fade', 'show');

    // Wire close button to hide immediately
    const closeBtn = alertEl.querySelector('.close, .btn-close');
    let timerId = null;

    if (closeBtn) {
      closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (timerId) {
          clearTimeout(timerId);
          timerId = null;
        }
        hideWithFade(alertEl);
      });
    }

    // Auto hide after 7 seconds
    timerId = setTimeout(() => {
      hideWithFade(alertEl);
    }, AUTO_HIDE_MS);
  }

  function initExistingAlerts() {
    document.querySelectorAll('.alert').forEach(setupAlert);
  }

  function observeNewAlerts() {
    const container = document.querySelector('#messages') || document.body;
    const observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        m.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) return;
          if (node.classList && node.classList.contains('alert')) {
            setupAlert(node);
          }
          // Also check any alerts inside the added subtree
          node.querySelectorAll && node.querySelectorAll('.alert').forEach(setupAlert);
        });
      }
    });
    observer.observe(container, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initExistingAlerts();
      observeNewAlerts();
    });
  } else {
    initExistingAlerts();
    observeNewAlerts();
  }
})();
