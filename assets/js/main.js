document.addEventListener('DOMContentLoaded', function () {
    // Live password confirmation check on registration form
    var pw = document.getElementById('password');
    var confirmPw = document.getElementById('confirm_password');
    if (pw && confirmPw) {
        var check = function () {
            confirmPw.setCustomValidity(
                confirmPw.value && confirmPw.value !== pw.value ? 'Passwords do not match' : ''
            );
        };
        pw.addEventListener('input', check);
        confirmPw.addEventListener('input', check);
    }

    // Confirm before approve/reject actions
    document.querySelectorAll('form.inline-form').forEach(function (form) {
        var actionInput = form.querySelector('input[name="action"]');
        if (actionInput && (actionInput.value === 'approve' || actionInput.value === 'reject')) {
            form.addEventListener('submit', function (e) {
                var verb = actionInput.value === 'approve' ? 'approve' : 'reject';
                if (!confirm('Are you sure you want to ' + verb + ' this application?')) {
                    e.preventDefault();
                }
            });
        }
    });
});
