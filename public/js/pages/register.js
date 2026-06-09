document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const name = document.getElementById('register-name').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const passwordConfirm = document.getElementById('register-password-confirm').value;
    
    try {
        const response = await fetch('../api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ name, email, password, password_confirm: passwordConfirm })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast('Registrasi berhasil! masuk ke akun anda', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        } else {
            showToast(data.message || 'Registrasi gagal', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    }
});
