document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    if (highlightId) {
        // Look for the row with the given ID.
        // EasyAdmin 4 usually has data-id attribute on <tr>
        const row = document.querySelector('tr[data-id="' + highlightId + '"]');
        if (row) {
            row.classList.add('ea-move-highlight');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Optional: remove highlight after a few seconds
            setTimeout(() => {
                row.classList.remove('ea-move-highlight');
            }, 3000);
        }
    }
});
