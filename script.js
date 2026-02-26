// Simple SPA Logic
function showPage(pageId, linkElement) {
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => {
        page.classList.remove('active');
        // Khusus untuk flexbox pages (Login & Messages & Post Report) agar display propertinya benar saat di-hide
        page.style.display = 'none'; 
    });

    // Show selected page
    const selectedPage = document.getElementById(pageId);
    selectedPage.classList.add('active');
    
    // Restore specific display types
    if(pageId === 'login-page') {
        selectedPage.style.display = 'flex';
        document.getElementById('main-nav').style.display = 'none'; // Sembunyikan nav di login
    } else if (pageId === 'messages-page') {
        selectedPage.style.display = 'flex';
        document.getElementById('main-nav').style.display = 'flex';
    } else if (pageId === 'post-report-page') {
        selectedPage.style.display = 'flex';
        document.getElementById('main-nav').style.display = 'flex';
    } else {
        selectedPage.style.display = 'block'; // Default block for feeds/profile
        document.getElementById('main-nav').style.display = 'flex';
    }

    // Update Nav Active State
    if(linkElement) {
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => link.classList.remove('active'));
        linkElement.classList.add('active');
    }
}

// Logic Tombol Login
function login() {
    // Pindah ke halaman Found Pets setelah login
    showPage('found-pets-page');
    // Set link Found Pets jadi aktif
    const navLinks = document.querySelectorAll('nav a');
    navLinks.forEach(link => {
        if(link.innerText === 'FOUND PETS') link.classList.add('active');
    });
}

// Initial State (Run on load)
document.addEventListener('DOMContentLoaded', () => {
    // Start at Login Page
    showPage('login-page', null);
});
