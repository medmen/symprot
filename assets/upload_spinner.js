 document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[name="{{ form.vars.name }}"]');
    const overlay = document.getElementById('overlay');

    form.addEventListener('submit', function() {
        overlay.style.display = 'block';
        alert('Bitte warten, das Hochladen kann einige Zeit in Anspruch nehmen.');
    });
});