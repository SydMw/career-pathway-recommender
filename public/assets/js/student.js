// Disables the submit button and shows a working indicator while the
// student's data is sent to the server and the ML API generates a
// recommendation, so the user gets feedback during the round trip instead
// of wondering if their click registered.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('recommendation-form');
    var btn = document.getElementById('submit-btn');
    if (!form || !btn) return;

    form.addEventListener('submit', function () {
        if (form.checkValidity()) {
            btn.disabled = true;
            btn.textContent = 'Generating recommendation...';
        }
    });
});
