document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    
    try {
        const response = await fetch('../api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast('Login Berhasil.', 'success');
            setTimeout(() => {
                window.location.href = 'explore.php';
            }, 1000);
        } else {
            showToast(data.message || 'Login gagal', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    }
});
