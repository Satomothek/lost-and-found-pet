document.getElementById('reset-password-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const token = document.getElementById('reset-token').value;
    const password = document.getElementById('reset-password').value;
    const passwordConfirm = document.getElementById('reset-password-confirm').value;

    try {
        const response = await fetch('../api/auth/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token, password, password_confirm: passwordConfirm })
        });

        const data = await response.json();

        if (data.status === 'success') {
            showToast('Sandi berhasil diatur ulang! Silakan login.', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        } else {
            showToast(data.message || 'Gagal mengatur ulang sandi', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    }
});
