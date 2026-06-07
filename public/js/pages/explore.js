/**
 * Explore page script
 * Handles loading, searching, filtering, and displaying pet reports
 */

// Global variables (initialized by inline script in PHP: currentUser, initialReports)
let activeModalMap = null;
let allReportsCache = [];
let activeFilter = 'all';

document.addEventListener('DOMContentLoaded', () => {
    if (typeof initialReports !== 'undefined' && initialReports && initialReports.length > 0) {
        allReportsCache = initialReports;
        renderFeed(initialReports);
    } else {
        loadFeed();
    }
    setupSearch();
    setupModal();
    setupLogout(); // from utils.js
});

async function loadFeed(search = '') {
    const container = document.getElementById('feed-container');
    if (container) {
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Memuat laporan...</div>';
    }

    try {
        let url = '../api/reports.php';
        if (search) {
            url += '?search=' + encodeURIComponent(search);
        }
        const response = await fetch(url, {
            credentials: 'same-origin'
        });
        const rawText = await response.text();
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (parseError) {
            throw new Error('Respons API tidak valid: ' + rawText);
        }

        if (data.status === 'success') {
            renderFeed(data.data.reports);
        } else {
            if (container) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--danger);">' + data.message + '</div>';
            }
            showToast(data.message, 'error');
        }
    } catch (error) {
        if (container) {
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--danger);">Gagal memuat laporan. Silakan coba lagi.</div>';
        }
        showToast('Gagal memuat feed: ' + error.message, 'error');
    }
}

function renderFeed(reports) {
    const container = document.getElementById('feed-container');
    if (!container) return;
    
    if (!reports || reports.length === 0) {
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color:var(--text-muted);">Tidak ada laporan di radar area ini.</div>';
        return;
    }
    
    // Update cache hanya saat tidak ada query aktif (load penuh)
    const searchInput = document.getElementById('search-feed');
    if (searchInput && !searchInput.value.trim()) {
        allReportsCache = reports;
    }

    let html = '';
    reports.forEach(report => {
        const petImageSrc = report.image || 'https://via.placeholder.com/600x400?text=Pet+Image';
        const authorImageSrc = report.authorImg;
        const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
        const typeText = report.type === 'found' ? 'FOUND' : 'LOST';
        const title = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
        const speciesLabel = report.species || 'Jenis tidak diketahui';
        const speciesDetailText = report.speciesDetail ? report.speciesDetail : '';
        const rawDescription = report.description || report.desc || '';
        const descriptionSnippet = rawDescription ? rawDescription.substring(0, 90) + (rawDescription.length > 90 ? '...' : '') : 'Tidak ada deskripsi tambahan.';
        const descriptionHtml = speciesDetailText ? '<strong>' + escapeHtml(speciesDetailText) + '</strong> ' + escapeHtml(descriptionSnippet) : escapeHtml(descriptionSnippet);
        const eventDateText = report.eventDate ? report.eventDate : null;
        const createdRelative = report.createdRelative || 'Baru saja';
        const updatedRelative = report.updatedRelative || null;
        const createdUpdatedLabel = updatedRelative ? updatedRelative : createdRelative;
        const likeClass = report.isLiked ? 'liked' : '';
        const iconClass = report.isLiked ? 'fa-solid' : 'fa-regular';
        const locationDescription = report.location_description ? escapeHtml(report.location_description) : '';
        const locationText = escapeHtml(report.location || 'Lokasi tidak tersedia');
        const locationDisplay = locationDescription ? locationText + ' - ' + locationDescription : locationText;
        const conditionText = locationDescription ? truncateText(locationDescription, 18) : '-';
        const conditionClass = 'info-condition';
        const conditionIcon = 'fa-info-circle';
        const hasCoords = report.latitude !== null && report.longitude !== null;
        const locationLabel = hasCoords ? 'Memuat alamat...' : getShortLocation(locationDisplay);
        const locationData = hasCoords ? ' data-latitude="' + report.latitude + '" data-longitude="' + report.longitude + '" data-fallback="' + locationDisplay + '"' : '';

        html += '<div class="feed-card" onclick="openModal(' + report.id + ')">';
        html += '<div class="card-img-box">';
        html += '<div class="card-badge ' + badgeClass + '">' + typeText + '</div>';
        html += '<img src="' + petImageSrc + '" alt="Pet Image" loading="lazy" onerror="this.src=\'https://via.placeholder.com/600x400?text=Pet+Image\'">';
        html += '</div>';
        html += '<div class="card-body">';
        html += '<div class="card-title-row">';
        html += '<h3>' + title + '</h3>';
        html += '<span class="card-label">' + speciesLabel + '</span>';
        html += '</div>';
        html += '<p class="card-description">' + descriptionHtml + '</p>';
        html += '<div class="card-info-grid">';
        html += '<span class="info-item info-event"><i class="fa-solid fa-calendar"></i> ' + escapeHtml(eventDateText || '-') + '</span>';
        html += '<span class="info-item info-location"' + locationData + '><i class="fa-solid fa-map-marker-alt"></i> ' + escapeHtml(locationLabel) + '</span>';
        html += '<span class="info-item info-created"><i class="fa-solid fa-clock"></i> ' + createdUpdatedLabel + '</span>';
        html += '<span class="info-item ' + conditionClass + '" title="' + escapeHtml(locationDescription) + '"><i class="fa-solid ' + conditionIcon + '"></i> ' + conditionText + '</span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="card-footer">';
        html += '<div class="author-box">';
        html += '<img src="' + authorImageSrc + '" class="author-img" alt="Author">';
        html += '<div class="author-text">';
        html += '<span class="author-name">' + report.author + '</span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="action-buttons">';
        if (typeof currentUser !== 'undefined' && report.user_id != currentUser.id) {
            html += '<button class="btn-chat action-btn" title="Chat dengan pembuat laporan" onclick="startChat(' + report.user_id + ', event)">';
            html += '<i class="fa-solid fa-comments"></i>';
            html += '</button>';
        }
        html += '<button class="btn-like action-btn ' + likeClass + '" title="Simpan ke Bookmarks" onclick="toggleLike(' + report.id + ', event)">';
        html += '<i class="' + iconClass + ' fa-bookmark"></i>';
        html += '</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });

    container.innerHTML = html;
    updateMapAddresses();
}

function getShortLocation(text) {
    if (!text) return text;
    const parts = text.split(',');
    return parts[0].trim() || text;
}

function truncateText(text, maxLength = 25) {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength).trim() + '...';
}

function updateMapAddresses() {
    const nodes = document.querySelectorAll('.info-item.info-location[data-latitude][data-longitude]');
    nodes.forEach(async (node) => {
        const lat = node.dataset.latitude;
        const lng = node.dataset.longitude;
        const fallback = node.dataset.fallback || node.textContent.trim();
        if (!lat || !lng) return;

        const address = await reverseGeocode(lat, lng);
        const display = address || fallback || 'Lokasi tidak tersedia';
        node.innerHTML = '<i class="fa-solid fa-map-marker-alt"></i> ' + escapeHtml(getShortLocation(display));
    });
}

async function reverseGeocode(lat, lng) {
    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) return null;
        const data = await response.json();
        return data.display_name || null;
    } catch (error) {
        return null;
    }
}

function setupSearch() {
    const searchInput = document.getElementById('search-feed');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilterAndSearch, 250);
        });
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            applyFilterAndSearch();
        });
    });
}

function applyFilterAndSearch() {
    const searchInput = document.getElementById('search-feed');
    const query = (searchInput ? searchInput.value : '').trim().toLowerCase();

    if (allReportsCache.length === 0) { loadFeed(query); return; }
    if (!query) { renderFeed(allReportsCache); return; }

    const filtered = allReportsCache.filter(report => {
        const name    = (report.petName || '').toLowerCase();
        const species = (report.species || '').toLowerCase();
        const loc     = (report.location || '').toLowerCase();
        const locDesc = (report.location_description || '').toLowerCase();
        switch (activeFilter) {
            case 'name':     return name.includes(query);
            case 'species':  return species.includes(query);
            case 'location': return loc.includes(query) || locDesc.includes(query);
            default:         return name.includes(query) || species.includes(query) || loc.includes(query) || locDesc.includes(query);
        }
    });
    renderFeed(filtered);
}

function destroyModalMap() {
    // FIX #2: Selalu destroy instance Leaflet lama sebelum membuat yang baru
    if (activeModalMap) {
        activeModalMap.remove();
        activeModalMap = null;
    }
}

function setupModal() {
    const modal = document.getElementById('post-modal');
    const closeBtn = document.getElementById('close-modal');

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
            // FIX #2: Destroy peta saat modal ditutup
            destroyModalMap();
        });
    }

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
                // FIX #2: Destroy peta saat modal ditutup via klik backdrop
                destroyModalMap();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            modal.classList.remove('show');
            // FIX #2: Destroy peta saat modal ditutup via Escape
            destroyModalMap();
        }
    });
}

async function toggleLike(reportId, event) {
    event.stopPropagation();
    try {
        const response = await fetch('../api/likes.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ report_id: reportId })
        });
        const data = await response.json();
        if (data.status === 'success') {
            loadFeed();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal mengupdate like: ' + error.message, 'error');
    }
}

function startChat(userId, event) {
    event.stopPropagation();
    window.location.href = 'messages.php?contact=' + userId;
}

async function openModal(reportId) {
    try {
        const response = await fetch(`../api/reports.php?id=${reportId}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();
        if (data.status === 'success') {
            renderModalContent(data.data);
            document.getElementById('post-modal').classList.add('show');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal memuat detail laporan: ' + error.message, 'error');
    }
}

function renderModalContent(report) {
    // FIX #2: Destroy peta lama sebelum inject HTML baru
    destroyModalMap();

    const modalBody = document.getElementById('modal-body-content');
    if (!modalBody) return;

    const typeText = report.type === 'found' ? 'DITEMUKAN' : 'HILANG';
    const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
    const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
    const speciesDetail = report.speciesDetail ? ` (${report.speciesDetail})` : '';
    const createdUpdatedText = report.updatedAt ? `Diperbarui ${report.updatedAt}` : `Dipublikasikan ${report.createdAt}`;
    const locationDescriptionShort = report.location_description ? truncateText(report.location_description, 30) : '';

    modalBody.innerHTML = `
        <div class="modal-report-detail">
            <div class="modal-header-section">
                <div class="modal-image-container">
                    <img src="${report.image}" alt="Pet Image" class="modal-pet-image">
                    <div class="modal-badge ${badgeClass}">${typeText}</div>
                </div>
                <div class="modal-info-section">
                    <h2 class="modal-pet-name">${petName}</h2>
                    <p class="modal-pet-species">${report.species}${speciesDetail}</p>
                    <div class="modal-meta-info">
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-map-marker-alt"></i>
                            <span>${report.location}</span>
                        </div>
                        ${report.location_description ? `
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-info-circle"></i>
                            <span title="${report.location_description}">${locationDescriptionShort}</span>
                        </div>` : ''}
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-calendar"></i>
                            <span>${report.eventDate || 'Tanggal tidak diketahui'}</span>
                        </div>
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-clock"></i>
                            <span>${createdUpdatedText}</span>
                        </div>
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-plus"></i>
                            <span>Created: ${formatDate(report.created_at)}</span>
                        </div>
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-edit"></i>
                            <span>Edited: ${report.updated_at ? formatDate(report.updated_at) : ''}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-description-section">
                <h3>Detail Laporan</h3>
                <p class="modal-description">${report.description.replace(/\n/g, '<br>')}</p>
            </div>
            ${report.latitude !== null && report.longitude !== null ? `
            <div class="modal-map-section">
                <h3>Lokasi pada Peta</h3>
                <div class="modal-map-clipper">
                    <div id="modal-map"></div>
                </div>
            </div>
            ` : ''}
            <div class="modal-author-section">
                <div class="modal-author-info">
                    <img src="${report.authorImg}" alt="Author" class="modal-author-avatar">
                    <div class="modal-author-details">
                        <span class="modal-author-name">${report.author}</span>
                        <span class="modal-author-role">Pelapor</span>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-like action-btn ${report.isLiked ? 'liked' : ''}" title="Simpan ke Bookmarks" onclick="toggleLikeFromModal(${report.id}, event)">
                        <i class="${report.isLiked ? 'fa-solid' : 'fa-regular'} fa-bookmark"></i>
                        <span>${report.likes}</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    // Inisialisasi Leaflet di modal — urutan penting:
    // 1. HTML harus sudah di-inject ke DOM (dilakukan di atas)
    // 2. Modal harus visible (dilakukan di openModal setelah renderModalContent)
    // 3. Leaflet harus bisa membaca ukuran container yang sudah non-zero
    if (report.latitude !== null && report.longitude !== null && window.L) {
        // Fix icon path yang sering broken di PHP project dengan subfolder
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        });

        // Tunggu modal benar-benar visible + layout selesai sebelum init Leaflet.
        // requestAnimationFrame pertama: DOM updated.
        // requestAnimationFrame kedua (nested): browser sudah paint, container punya ukuran nyata.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const mapEl = document.getElementById('modal-map');
                if (!mapEl) return;

                activeModalMap = L.map('modal-map', {
                    zoomControl: true,
                    scrollWheelZoom: false   // nonaktifkan scroll zoom di modal agar tidak ganggu scroll halaman
                }).setView([report.latitude, report.longitude], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(activeModalMap);

                L.marker([report.latitude, report.longitude]).addTo(activeModalMap);

                // Panggil invalidateSize 2x: segera + setelah 250ms (untuk animasi modal)
                activeModalMap.invalidateSize();
                setTimeout(() => { activeModalMap.invalidateSize(); }, 250);
            });
        });
    }
}

async function toggleLikeFromModal(reportId, event) {
    event.stopPropagation();
    try {
        const response = await fetch('../api/likes.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ report_id: reportId })
        });
        const data = await response.json();
        if (data.status === 'success') {
            openModal(reportId);
            loadFeed();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
