document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('file-upload-form');
    if (!form) {
        alert('Upload progress cannot work without an upload form.');
        return; // Not on the upload page
    }

    var progressBar = document.getElementById('progress_bar');

    // Helper to render progress into the existing <div id="progress_bar"> element
    function renderProgress(percent) {
        if (!progressBar) return;
        var pct = Math.min(100, Math.max(0, Math.round(percent)));
        // Ensure it is visible and shows a simple bar using inline styles
        // Create or reuse inner fill
        var fill = progressBar.querySelector('.fill');
        if (!fill) {
            fill = document.createElement('div');
            fill.className = 'fill';
            progressBar.appendChild(fill);
        }
        fill.style.width = pct + '%';
        // Add text label
        var label = progressBar.querySelector('.label');
        if (!label) {
            label = document.createElement('span');
            label.className = 'label';
            progressBar.appendChild(label);
        }
        label.textContent = pct + '%';
    }

    var submitting = false;

    form.addEventListener('submit', function (e) {
        // Guard against duplicate submits/navigations
        if (submitting) {
            e.preventDefault();
            return;
        }
        submitting = true;

        // return; // uncomment here to do common upload
        e.preventDefault();

        // Disable submit button(s) to avoid extra clicks
        var submitters = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitters.forEach(function(btn){ btn.disabled = true; });

        // Ensure a file is selected to avoid sending empty request by accident
        var fileInput = form.querySelector('input[type="file"]');
        if (fileInput && (!fileInput.files || fileInput.files.length === 0)) {
            // Let Symfony handle validation/UI as usual
            submitting = false;
            submitters.forEach(function(btn){ btn.disabled = false; });
            form.submit();
            return;
        }

        var xhr = new XMLHttpRequest();
        var data = new FormData(form); // includes CSRF token and all fields

        // Include the clicked submit button (Symfony may rely on it in certain setups)
        if (e.submitter && e.submitter.name) {
            data.append(e.submitter.name, e.submitter.value || '');
        }

        xhr.withCredentials = true; // keep session cookies just in case

        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                var percent = (event.loaded / event.total) * 100;
                console.info('Upload progress: ' + percent + '%');
                renderProgress(percent);
            }
        };

        xhr.onload = function () {
            var ct = xhr.getResponseHeader('Content-Type') || '';

            // Handle JSON response from AJAX upload (no redirects)
            if (xhr.status >= 200 && xhr.status < 300 && ct.indexOf('application/json') !== -1) {
                try {
                    var payload = JSON.parse(xhr.responseText);
                    if (payload && payload.status === 'ok' && payload.processUrl) {
                        renderProgress(100);
                        // Automatically start processing by navigating to the process URL
                        window.location.href = payload.processUrl;
                        return;
                    }
                } catch (e) {
                    console.error('Invalid JSON in upload response', e);
                }
            }

            // Follow redirects explicitly if the browser didn’t for XHR
            if (xhr.status >= 300 && xhr.status < 400) {
                var locationHeader = xhr.getResponseHeader('Location');
                if (locationHeader) {
                    window.location.href = locationHeader;
                    return;
                }
            }

            if (xhr.status >= 200 && xhr.status < 400) {
                // If server redirected, responseURL points to final URL
                if (xhr.responseURL && xhr.responseURL !== window.location.href) {
                    window.location.href = xhr.responseURL;
                } else {
                    // Fallback: if the response seems to be an HTML page with the form (validation errors),
                    // replace the main content so the user sees errors instead of silent reload
                    if (ct.indexOf('text/html') !== -1) {
                        try {
                            var parser = new DOMParser();
                            var doc = parser.parseFromString(xhr.responseText, 'text/html');
                            var newMain = doc.querySelector('#maincontent');
                            var curMain = document.querySelector('#maincontent');
                            if (newMain && curMain) {
                                curMain.innerHTML = newMain.innerHTML;
                                return;
                            }
                        } catch (ignore) {}
                    }
                    // As a last resort, reload the page
                    window.location.reload();
                }
            } else {
                // Show an error message to help diagnose 400s (e.g., size limits)
                console.error('Upload failed with status ' + xhr.status + ': ' + xhr.statusText);
                alert('Upload fehlgeschlagen (' + xhr.status + '). Bitte prüfen Sie die Dateigröße und versuchen Sie es erneut.');
            }
        };

        xhr.onerror = function () {
            console.error('Network error during upload');
            alert('Netzwerkfehler beim Upload. Bitte erneut versuchen.');
        };

        // Open and send as standard multipart/form-data
        xhr.open('POST', form.action || window.location.href, true);
        // Mark as AJAX so the server can return JSON and avoid full redirects
        try { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); } catch (e) {}
        try { xhr.setRequestHeader('Accept', 'application/json, text/html;q=0.8, */*;q=0.5'); } catch (e) {}
        // Do NOT set Content-Type header; the browser sets the correct multipart boundary
        xhr.send(data);
    });
});
