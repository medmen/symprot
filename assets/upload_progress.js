document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('file-upload-form');
    if (!form) {
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

    form.addEventListener('submit', function (e) {
        return;
        // dont use submit
        e.preventDefault();

        var xhr = new XMLHttpRequest();
        var data = new FormData(form); // includes CSRF token and all fields

        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                var percent = (event.loaded / event.total) * 100;
                renderProgress(percent);
            }
        };

        xhr.onload = function () {
            // Symfony will redirect after successful submission; follow it
            if (xhr.status >= 200 && xhr.status < 400) {
                // If server redirected, responseURL points to final URL
                if (xhr.responseURL && xhr.responseURL !== window.location.href) {
                    window.location.href = xhr.responseURL;
                } else {
                    // Fallback: reload to render server response
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
        xhr.open('POST', form.action, true);
        // Do NOT set Content-Type header; the browser sets the correct multipart boundary
        xhr.send(data);
    });
});
