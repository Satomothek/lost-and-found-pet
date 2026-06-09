document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password-btn');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            let input;
            const inputId = this.getAttribute('data-input');
            const inputName = this.getAttribute('data-input-name');

            if (inputId) {
                input = document.getElementById(inputId);
            } else if (inputName) {
                input = document.querySelector(`input[name="${inputName}"]`);
            }

            if (!input) return;

            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});

