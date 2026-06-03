document.getElementById('verify-otp-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('otp-email').value;
    const otp = document.getElementById('otp-code').value;

    console.log('OTP Verification Data:');
    console.log('Email:', email);
    console.log('OTP:', otp);
    console.log('OTP Length:', otp.length);

    if (otp.length !== 6) {
        showToast('Kode OTP harus 6 digit', 'error');
        return;
    }

    try {
        const payload = { email, otp };
        console.log('Payload:', JSON.stringify(payload));

        const response = await fetch('../api/auth/verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        console.log('Response Status:', response.status);

        const data = await response.json();

        console.log('Response Data:', data);

        if (data.status === 'success') {
            showToast('OTP terverifikasi! Silakan atur sandi baru.', 'success');
            setTimeout(() => {
                window.location.href = 'reset_password.php?token=' + encodeURIComponent(data.data.token);
            }, 1500);
        } else {
            showToast(data.message || 'Verifikasi OTP gagal', 'error');
            console.log('Error Message:', data.message);
        }
    } catch (error) {
        console.error('Fetch Error:', error);
        showToast('Terjadi kesalahan: ' + error.message, 'error');
    }
});

// Auto format input to numeric only
document.getElementById('otp-code').addEventListener('input', (e) => {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});
