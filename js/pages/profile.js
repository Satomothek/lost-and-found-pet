/**
 * Profile page script
 * Handles loading stats, activities, bookmarks, editing profile, updating avatar, and managing report modal
 */

// Global variables (initialized by inline script in PHP: currentUser)
let activeProfileModalMap = null;

// Load profile on page load
document.addEventListener('DOMContentLoaded', () => {
    loadProfileActivity();
    loadProfileBookmarks();
    loadProfileStats();
    setupProfileForm();
    setupAvatarUpload();
    setupTabSwitching();
    setupReportDetailModal();
    setupLogout(); // from utils.js
});

// Load profile activity
async function loadProfileActivity() {
    try {
        const response = await fetch('../api/profile.php?action=reports');
        const data = await response.json();
        
        if (data.status === 'success') {
            renderActivity(data.data.reports || [], 'profile-activity-list');
        }
    } catch (error) {
        showToast('Gagal memuat aktivitas: ' + error.message, 'error');
    }
}

// Load profile bookmarks
async function loadProfileBookmarks() {
    try {
        const response = await fetch('../api/profile.php?action=bookmarks');
        const data = await response.json();
        
        if (data.status === 'success') {
            renderActivity(data.data.bookmarks || [], 'profile-bookmarks-list');
        }
    } catch (error) {
        showToast('Gagal memuat bookmarks: ' + error.message, 'error');
    }
}

// Render activity
function renderActivity(reports, containerId = 'profile-activity-list') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const isBookmarkTab = containerId === 'profile-bookmarks-list';
    
    if (!reports || reports.length === 0) {
        const emptyText = isBookmarkTab ? 'Belum ada bookmarks' : 'Belum ada laporan';
        container.innerHTML = '<div class="profile-empty-wrapper"><div class="profile-empty-state">' + emptyText + '</div></div>';
        return;
    }

    container.innerHTML = reports.map(report => {
        const isFound = report.type === 'found';
        const badgeClass = isFound ? 'found' : 'lost';
        const typeText = isFound ? 'FOUND' : 'LOST';
        const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
        const petType = report.species || 'Jenis tidak diketahui';
        const speciesDetailText = report.speciesDetail || report.species_detail || '';
        const rawDescription = report.description || report.desc || '';
        const descriptionSnippet = rawDescription ? rawDescription.substring(0, 90) + (rawDescription.length > 90 ? '...' : '') : 'Tidak ada deskripsi tambahan.';
        const descriptionHtml = speciesDetailText
            ? '<strong>' + escapeHtml(speciesDetailText) + '</strong> ' + escapeHtml(descriptionSnippet)
            : escapeHtml(descriptionSnippet);
        const eventDate = report.eventDate || null;
        const createdRelative = report.createdRelative || report.date || 'Baru saja';
        const updatedRelative = report.updatedRelative || null;
        const createdUpdatedLabel = updatedRelative ? updatedRelative : createdRelative;
        const locationDescription = report.location_description ? escapeHtml(report.location_description) : '';
        const locationText = escapeHtml(report.location || 'Lokasi tidak tersedia');
        const locationDisplay = locationDescription ? locationText + ' - ' + locationDescription : locationText;
        const conditionText = locationDescription || '-';
        const hasCoords = report.latitude !== null && report.latitude !== undefined
                       && report.longitude !== null && report.longitude !== undefined;
        const locationLabel = hasCoords ? 'Memuat alamat...' : getShortLocation(locationDisplay);
        const locationData = hasCoords
            ? ` data-latitude="${report.latitude}" data-longitude="${report.longitude}" data-fallback="${locationDisplay}"`
            : '';
        const authorImg = report.authorImg || report.avatar_url || 'https://i.pravatar.cc/48?img=68';
        const authorName = report.author || report.name || 'Anonim';
        const isLiked = report.isLiked ? true : false;
        const iconClass = isLiked ? 'fa-solid' : 'fa-regular';
        const likeClass = isLiked ? 'liked' : '';
        const petImage = report.image || 'https://via.placeholder.com/600x400?text=Pet+Image';

        return `
        <div class="feed-card" data-report-id="${report.id}" onclick="openReportDetail(${report.id})">
            <div class="card-img-box">
                <img src="${petImage}" alt="${escapeHtml(petName)}" loading="lazy" onerror="this.src='https://via.placeholder.com/600x400?text=Pet+Image'">
                <span class="card-badge badge-${badgeClass}">${typeText}</span>
                ${!isBookmarkTab ? `
                    <div class="activity-options" onclick="toggleActivityMenu(event, ${report.id})">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </div>
                    <div class="activity-dropdown" id="dropdown-${report.id}">
                        <div class="activity-dropdown-item" onclick="editReport(event, ${report.id})">
                            <i class="fa-solid fa-edit"></i> Edit
                        </div>
                        <div class="activity-dropdown-item" onclick="markAsDone(event, ${report.id})">
                            <i class="fa-solid fa-check"></i> Mark as Done
                        </div>
                        <div class="activity-dropdown-item danger" onclick="deleteReport(event, ${report.id})">
                            <i class="fa-solid fa-trash"></i> Delete
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="card-body">
                <div class="card-title-row">
                    <h3>${escapeHtml(petName)}</h3>
                    <span class="card-label">${escapeHtml(petType)}</span>
                </div>
                <p class="card-description">${descriptionHtml}</p>
                <div class="card-info-grid">
                    <span class="info-item info-event"><i class="fa-solid fa-calendar"></i> ${escapeHtml(eventDate || '-')}</span>
                    <span class="info-item info-location"${locationData}><i class="fa-solid fa-map-marker-alt"></i> ${escapeHtml(locationLabel)}</span>
                    <span class="info-item info-created"><i class="fa-solid fa-clock"></i> ${createdUpdatedLabel}</span>
                    <span class="info-item info-condition"><i class="fa-solid fa-map-pin"></i> ${conditionText}</span>
                </div>
            </div>
            <div class="card-footer">
                <div class="author-box">
                    <img src="${escapeHtml(authorImg)}" class="author-img" alt="Author" onerror="this.src='https://i.pravatar.cc/48?img=68'">
                    <div class="author-text">
                        <span class="author-name">${escapeHtml(authorName)}</span>
                        <span style="font-size:0.75rem; color:var(--text-muted);">${createdUpdatedLabel}</span>
                    </div>
                </div>
                <div class="action-buttons">
                    ${isBookmarkTab ? `
                        <button class="btn-like action-btn liked" title="Hapus bookmark" onclick="toggleBookmark(event, ${report.id})">
                            <i class="fa-solid fa-bookmark"></i>
                        </button>
                    ` : `
                        <button class="btn-like action-btn ${likeClass}" title="Simpan ke Bookmarks" onclick="toggleBookmarkCard(event, ${report.id})">
                            <i class="${iconClass} fa-bookmark"></i>
                        </button>
                    `}
                </div>
            </div>
        </div>
    `;
    }).join('');

    updateMapAddresses();
}

function getShortLocation(text) {
    if (!text) return text;
    const parts = text.split(',');
    return parts[0].trim() || text;
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

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function toggleBookmarkCard(event, reportId) {
    event.stopPropagation();
    try {
        const response = await fetch('../api/likes.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: reportId })
        });
        const data = await response.json();
        if (data.status === 'success') {
            loadProfileActivity();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
    }
}

// Load profile stats
async function loadProfileStats() {
    try {
        const response = await fetch('../api/profile.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            const profile = data.data;
            const total  = profile.reports_count ?? 0;
            const lost   = profile.lost_count   ?? 0;
            const found  = profile.found_count  ?? 0;
            
            const reportsEl = document.getElementById('stat-reports');
            if (reportsEl) reportsEl.textContent = total;
            
            const lostEl = document.getElementById('stat-lost');
            if (lostEl) lostEl.textContent = lost;
            
            const foundEl = document.getElementById('stat-found');
            if (foundEl) foundEl.textContent = found;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Setup profile form
function setupProfileForm() {
    const editBtn = document.getElementById('edit-profile-btn');
    if (!editBtn) return;
    
    editBtn.addEventListener('click', () => {
        if (typeof currentUser !== 'undefined') {
            document.getElementById('edit-name').value = currentUser.name;
            document.getElementById('edit-phone').value = currentUser.phone || '';
            document.getElementById('edit-bio').value = currentUser.bio || '';
        }
        document.getElementById('edit-profile-modal').classList.add('show');
    });

    const closeBtn = document.getElementById('close-edit-modal');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            document.getElementById('edit-profile-modal').classList.remove('show');
        });
    }

    const form = document.getElementById('edit-profile-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('name', document.getElementById('edit-name').value);
            formData.append('phone', document.getElementById('edit-phone').value);
            formData.append('bio', document.getElementById('edit-bio').value);
            
            try {
                const response = await fetch('../api/profile.php?action=update', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('Profil berhasil diperbarui', 'success');
                    document.getElementById('edit-profile-modal').classList.remove('show');
                    if (typeof currentUser !== 'undefined') {
                        currentUser.name = data.data.name;
                        currentUser.bio = data.data.bio;
                        currentUser.phone = data.data.phone;
                    }
                    location.reload();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal memperbarui profil: ' + error.message, 'error');
            }
        });
    }
}

// Setup avatar upload
function setupAvatarUpload() {
    const editAvatarBtn = document.getElementById('edit-avatar-btn');
    if (!editAvatarBtn) return;

    editAvatarBtn.addEventListener('click', () => {
        document.getElementById('avatar-input').click();
    });

    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);
            
            try {
                const response = await fetch('../api/profile.php?action=avatar', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showToast('Avatar berhasil diperbarui', 'success');
                    document.getElementById('profile-avatar').src = data.data.avatar_url;
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Gagal upload avatar: ' + error.message, 'error');
            }
        });
    }
}

// Setup tab switching
function setupTabSwitching() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;
            
            // Remove active from all tabs
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.profile-tab-content').forEach(t => t.style.display = 'none');
            
            // Add active to clicked tab
            btn.classList.add('active');
            const targetTab = document.getElementById(tabName + '-tab');
            if (targetTab) targetTab.style.display = 'block';

            if (tabName === 'activity') {
                loadProfileActivity();
            } else if (tabName === 'bookmarks') {
                loadProfileBookmarks();
            }
        });
    });
}

// Activity card options functions
function toggleActivityMenu(event, reportId) {
    event.stopPropagation();
    
    // Close all other dropdowns
    document.querySelectorAll('.activity-dropdown').forEach(dropdown => {
        if (dropdown.id !== `dropdown-${reportId}`) {
            dropdown.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    const dropdown = document.getElementById(`dropdown-${reportId}`);
    if (dropdown) dropdown.classList.toggle('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', () => {
    document.querySelectorAll('.activity-dropdown').forEach(dropdown => {
        dropdown.classList.remove('show');
    });
});

// Unbookmark from bookmarks list
async function toggleBookmark(event, reportId) {
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
            showToast('Bookmark dihapus', 'success');
            loadProfileBookmarks();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
    }
}

// Open report detail modal from profile
async function openReportDetail(reportId) {
    try {
        const response = await fetch(`../api/reports.php?id=${reportId}`, {
            credentials: 'same-origin'
        });

        const data = await response.json();
        if (data.status !== 'success') {
            showToast(data.message || 'Gagal memuat detail laporan', 'error');
            return;
        }

        renderReportDetail(data.data);
        const modal = document.getElementById('report-detail-modal');
        if (modal) modal.classList.add('show');
    } catch (error) {
        showToast('Gagal memuat detail laporan: ' + error.message, 'error');
    }
}

function renderReportDetail(report) {
    // Destroy peta lama sebelum inject HTML baru
    if (activeProfileModalMap) {
        activeProfileModalMap.remove();
        activeProfileModalMap = null;
    }

    const container = document.getElementById('report-modal-body');
    if (!container) return;
    
    const typeText = report.type === 'found' ? 'DITEMUKAN' : 'HILANG';
    const badgeClass = report.type === 'found' ? 'badge-found' : 'badge-lost';
    const petName = report.petName && report.petName !== 'Unknown' && report.petName.trim() !== '' ? report.petName : report.species + ' Tanpa Nama';
    const speciesDetail = report.speciesDetail ? ` (${report.speciesDetail})` : '';
    const createdUpdatedText = report.updatedAt ? `Diperbarui ${report.updatedAt}` : `Dipublikasikan ${report.createdAt}`;
    
    const activeTab = document.querySelector('.tab-btn.active');
    const isActivityTab = activeTab?.dataset.tab === 'activity';

    function formatDateLocal(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    const hasCoords = report.latitude !== null && report.latitude !== undefined
                   && report.longitude !== null && report.longitude !== undefined;

    container.innerHTML = `
        <div class="modal-report-detail">
            <div class="modal-header-section">
                <div class="modal-image-container">
                    <img src="${report.image}" alt="${escapeHtml(petName)}" class="modal-pet-image">
                    <div class="modal-badge ${badgeClass}">${typeText}</div>
                </div>
                <div class="modal-info-section">
                    <h2 class="modal-pet-name">${escapeHtml(petName)}</h2>
                    <p class="modal-pet-species">${escapeHtml(report.species)}${escapeHtml(speciesDetail)}</p>
                    <div class="modal-meta-info">
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-map-marker-alt"></i>
                            <span>${escapeHtml(report.location || 'Lokasi tidak tersedia')}</span>
                        </div>
                        ${report.location_description ? `
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-map-pin"></i>
                            <span>${escapeHtml(report.location_description)}</span>
                        </div>` : ''}
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-calendar"></i>
                            <span>${escapeHtml(report.eventDate || 'Tanggal tidak diketahui')}</span>
                        </div>
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-clock"></i>
                            <span>${createdUpdatedText}</span>
                        </div>
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-plus"></i>
                            <span>Created: ${formatDateLocal(report.created_at)}</span>
                        </div>
                        <div class="modal-meta-item">
                            <i class="fa-solid fa-edit"></i>
                            <span>Edited: ${report.updated_at ? formatDateLocal(report.updated_at) : '-'}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-description-section">
                <h3>Detail Laporan</h3>
                <p class="modal-description">${(report.description || '').replace(/\n/g, '<br>')}</p>
            </div>
            ${hasCoords ? `
            <div class="modal-map-section">
                <h3>Lokasi pada Peta</h3>
                <div class="modal-map-clipper">
                    <div id="profile-modal-map"></div>
                </div>
            </div>
            ` : ''}
            <div class="modal-author-section">
                <div class="modal-author-info">
                    <img src="${escapeHtml(report.authorImg || 'https://i.pravatar.cc/48?img=68')}" alt="Author" class="modal-author-avatar">
                    <div class="modal-author-details">
                        <span class="modal-author-name">${escapeHtml(report.author)}</span>
                        <span class="modal-author-role">Pelapor</span>
                    </div>
                </div>
                <div class="modal-actions">
                    ${isActivityTab ? `
                        <div class="modal-action-wrapper">
                            <button class="btn btn-icon modal-action-btn" title="Opsi laporan" onclick="toggleModalActionMenu(event, ${report.id})">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="modal-action-dropdown" id="modal-dropdown-${report.id}">
                                <div class="modal-action-dropdown-item" onclick="editReport(event, ${report.id})">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </div>
                                <div class="modal-action-dropdown-item" onclick="markAsDone(event, ${report.id})">
                                    <i class="fa-solid fa-check"></i> Mark as Done
                                </div>
                                <div class="modal-action-dropdown-item danger" onclick="deleteReport(event, ${report.id})">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </div>
                            </div>
                        </div>
                    ` : `
                        <button class="btn btn-like action-btn ${report.isLiked ? 'liked' : ''}" title="Simpan ke Bookmarks" onclick="toggleBookmarkModal(event, ${report.id})">
                            <i class="${report.isLiked ? 'fa-solid' : 'fa-regular'} fa-bookmark"></i>
                            <span>${report.likes || 0}</span>
                        </button>
                    `}
                </div>
            </div>
        </div>
    `;

    // Inisialisasi Leaflet
    if (hasCoords && window.L) {
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        });

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const mapEl = document.getElementById('profile-modal-map');
                if (!mapEl) return;

                activeProfileModalMap = L.map('profile-modal-map', {
                    zoomControl: true,
                    scrollWheelZoom: false
                }).setView([report.latitude, report.longitude], 15);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>'
                }).addTo(activeProfileModalMap);

                L.marker([report.latitude, report.longitude]).addTo(activeProfileModalMap);

                activeProfileModalMap.invalidateSize();
                setTimeout(() => { activeProfileModalMap.invalidateSize(); }, 250);
            });
        });
    }
}

async function toggleBookmarkModal(event, reportId) {
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
            openReportDetail(reportId);
            loadProfileBookmarks();
            loadProfileActivity();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Gagal mengupdate bookmark: ' + error.message, 'error');
    }
}

function setupReportDetailModal() {
    const modal = document.getElementById('report-detail-modal');
    const closeButton = document.getElementById('close-report-modal');
    if (!modal || !closeButton) return;

    function closeModal() {
        modal.classList.remove('show');
        document.querySelectorAll('.modal-action-dropdown.show').forEach(d => d.classList.remove('show'));
        if (activeProfileModalMap) {
            activeProfileModalMap.remove();
            activeProfileModalMap = null;
        }
    }

    closeButton.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.modal-action-wrapper')) {
            document.querySelectorAll('.modal-action-dropdown.show').forEach(dropdown => dropdown.classList.remove('show'));
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
}

function toggleModalActionMenu(event, reportId) {
    event.stopPropagation();
    const dropdown = document.getElementById(`modal-dropdown-${reportId}`);
    if (!dropdown) return;
    document.querySelectorAll('.modal-action-dropdown').forEach(menu => {
        if (menu !== dropdown) {
            menu.classList.remove('show');
        }
    });
    dropdown.classList.toggle('show');
}

// Edit report function
async function editReport(event, reportId) {
    event.stopPropagation();
    window.location.href = `create_report.php?edit=${reportId}`;
}

// Mark as done function
async function markAsDone(event, reportId) {
    event.stopPropagation();
    try {
        const response = await fetch(`../api/reports.php?id=${reportId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: 'completed' })
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast('Laporan berhasil ditandai sebagai selesai', 'success');
            loadProfileActivity(); // Reload activity
        } else {
            showToast(data.message || 'Gagal menandai laporan sebagai selesai', 'error');
        }
    } catch (error) {
        showToast('Gagal menandai laporan sebagai selesai: ' + error.message, 'error');
    }
}

// Delete report function
async function deleteReport(event, reportId) {
    event.stopPropagation();
    try {
        const response = await fetch(`../api/reports.php?action=delete&id=${reportId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast('Laporan berhasil dihapus', 'success');
            loadProfileActivity(); // Reload activity
        } else {
            showToast(data.message || 'Gagal menghapus laporan', 'error');
        }
    } catch (error) {
        showToast('Gagal menghapus laporan: ' + error.message, 'error');
    }
}
