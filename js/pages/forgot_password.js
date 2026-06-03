document.getElementById('forgot-password-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('forgot-email').value;

    try {
        const response = await fetch('../api/auth/request_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });

        const data = await response.json();

        if (data.status === 'success') {
            showToast('Kode OTP telah dikirim ke email Anda.', 'success');
            setTimeout(() => {
                window.location.href = 'verify_otp.php?email=' + encodeURIComponent(email);
            }, 1500);
        } else {
            showToast(data.message || 'Gagal mengirim OTP', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    }
});
