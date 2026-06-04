/**
 * Create/Edit Report page script
 * Handles leaflet map initialization, geolocation, geocoding, form validation, TensorFlow image checking, and upload
 */

// Global variables (initialized by inline script in PHP: currentUser, currentPage, createReportError, createReportSuccess, editReport, editReportId)
window.reportMap = null;
let reportMap;
let reportMarker;
let reportCircle;      // lingkaran radius 15–20 m
const RADIUS_M = 17;   // ~17 m (tengah antara 15–20)
let locationMode = 'gps'; // 'gps' | 'pick'
let gpsOriginLat = null;  // posisi GPS awal — anchor lingkaran
let gpsOriginLng = null;

// COCO-SSD VALIDASI FOTO HEWAN
let tfCocoSsd      = null;
let imageValidated = false;
let imageIsAnimal  = false;

// Hewan yang bisa dideteksi langsung COCO-SSD (hanya 10 class hewan)
const COCO_ANIMALS = ['bird','cat','dog','horse','sheep','cow','elephant','bear','zebra','giraffe'];

// Keyword hewan untuk fallback via nama file
// Reptil, ikan, kelinci, hamster dll tidak ada di COCO-SSD sama sekali
const PET_KEYWORDS = [
    'kura','turtle','tortoise','reptil','reptile','lizard','kadal','iguana',
    'snake','ular','gecko','tokek','chameleon','bunglon','salamander',
    'fish','ikan','goldfish','koi','arwana','cupang','betta',
    'rabbit','kelinci','hamster','marmot','guinea','gerbil','chinchilla',
    'squirrel','tupai','rat','mouse','tikus',
    'parrot','beo','cockatiel','kakatua','lovebird','murai','jalak',
    'canary','kenari','finch','pigeon','merpati',
    'ferret','hedgehog','landak','axolotl','musang','sugar',
    'monkey','monyet','deer','rusa','fox','rubah',
    'cat','dog','kucing','anjing','kitten','puppy',
    'hewan','pet','animal','peliharaan',
];

// Objek NON-hewan COCO-SSD — jika mendominasi foto → TOLAK
const NON_ANIMAL_CLASSES = [
    'car','truck','bus','motorcycle','bicycle','airplane','train','boat',
    'chair','couch','bed','dining table','toilet','tv','laptop','keyboard',
    'cell phone','bottle','cup','fork','knife','spoon','bowl',
    'traffic light','fire hydrant','stop sign','bench','backpack',
    'umbrella','suitcase','sports ball','kite',
];

async function ensureLeafletLoaded() {
    if (window.L) {
        return true;
    }

    const existingLeafletCss = document.querySelector('link[href*="leaflet"]');
    if (!existingLeafletCss) {
        const leafletCss = document.createElement('link');
        leafletCss.rel = 'stylesheet';
        leafletCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
        leafletCss.crossOrigin = '';
        document.head.appendChild(leafletCss);
    }

    return new Promise((resolve) => {
        if (window.L) {
            resolve(true);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
        script.crossOrigin = '';
        script.onload = () => resolve(!!window.L);
        script.onerror = () => {
            console.warn('Leaflet JS failed to load from fallback CDN.');
            resolve(!!window.L);
        };
        document.head.appendChild(script);
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    if (typeof createReportSuccess !== 'undefined' && createReportSuccess) {
        showToast(createReportSuccess, 'success');
    }
    if (typeof createReportError !== 'undefined' && createReportError) {
        showToast(createReportError, 'error');
    }

    await ensureLeafletLoaded();
    setupCreateForm();
    setReportDateRange();
    setupLogout(); // from utils.js
});

// Panggil invalidateSize lagi setelah semua asset selesai load
window.addEventListener('load', () => {
    [200, 500, 1000].forEach(ms => {
        setTimeout(() => {
            if (window.reportMap) window.reportMap.invalidateSize();
        }, ms);
    });
});

function initializeReportMap(latitude = -7.797068, longitude = 110.370529) {
    const mapEl = document.getElementById('report-map');
    if (!mapEl) { console.error('Map element not found'); return; }

    mapEl.style.setProperty('height', '320px', 'important');
    mapEl.style.setProperty('width', '100%', 'important');
    mapEl.style.setProperty('display', 'block', 'important');
    mapEl.style.setProperty('min-height', '320px', 'important');

    if (!window.L) { console.error('Leaflet library not loaded'); return; }

    try {
        if (!reportMap) {
            // ── Inisialisasi pertama ──────────────────────────────────
            reportMap = L.map('report-map', { zoomSnap: 0.5 }).setView([latitude, longitude], 16);
            window.reportMap = reportMap;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(reportMap);

            reportMap.on('click', function(e) {
                if (locationMode === 'pick') {
                    // Cek jarak dari GPS origin
                    if (gpsOriginLat !== null && gpsOriginLng !== null) {
                        const dist = reportMap.distance(
                            L.latLng(gpsOriginLat, gpsOriginLng),
                            e.latlng
                        );
                        if (dist > RADIUS_M) {
                            showToast('Lokasi harus dalam radius ' + RADIUS_M + ' meter dari posisi GPS Anda', 'error');
                            return;
                        }
                    }
                    setReportCoordinates(e.latlng.lat, e.latlng.lng);
                }
            });

            [100, 300, 600, 1000].forEach(ms => {
                setTimeout(() => { if (reportMap) reportMap.invalidateSize(); }, ms);
            });

            // Buat marker pertama kali
            reportMarker = L.marker([latitude, longitude], {
                draggable: locationMode === 'pick'
            }).addTo(reportMap);

            reportMarker.on('dragend', function() {
                const pos = reportMarker.getLatLng();
                // Cek jarak dari GPS origin saat mode pick
                if (locationMode === 'pick' && gpsOriginLat !== null && gpsOriginLng !== null) {
                    const dist = reportMap.distance(
                        L.latLng(gpsOriginLat, gpsOriginLng),
                        pos
                    );
                    if (dist > RADIUS_M) {
                        // Kembalikan marker ke posisi sebelumnya (GPS origin atau posisi terakhir valid)
                        const lastLat = parseFloat(document.getElementById('latitude').value);
                        const lastLng = parseFloat(document.getElementById('longitude').value);
                        reportMarker.setLatLng([lastLat, lastLng]);
                        showToast('Lokasi harus dalam radius ' + RADIUS_M + ' meter dari posisi GPS Anda', 'error');
                        return;
                    }
                }
                setReportCoordinates(pos.lat, pos.lng);
            });

        } else {
            // ── Update berikutnya: HANYA pindahkan marker, jangan reset zoom ──
            if (reportMarker) {
                reportMarker.setLatLng([latitude, longitude]);
            }
            // Geser pan ke marker tanpa mengubah zoom
            reportMap.panTo([latitude, longitude]);
            reportMap.invalidateSize();
        }

        updateRadiusCircle(latitude, longitude);

    } catch (error) {
        console.error('Map initialization error:', error);
    }
}

function updateRadiusCircle(lat, lng) {
    if (!reportMap || !window.L) return;
    if (reportCircle) {
        reportMap.removeLayer(reportCircle);
        reportCircle = null;
    }
    if (locationMode === 'pick') {
        // Gunakan koordinat GPS asal (anchor), bukan posisi marker
        const circleLat = (gpsOriginLat !== null) ? gpsOriginLat : lat;
        const circleLng = (gpsOriginLng !== null) ? gpsOriginLng : lng;
        reportCircle = L.circle([circleLat, circleLng], {
            radius: RADIUS_M,
            color: '#6366f1',
            fillColor: '#6366f1',
            fillOpacity: 0.15,
            weight: 2,
            dashArray: '5, 4'
        }).addTo(reportMap);
    }
}

function setMarkerDraggable(draggable) {
    if (!reportMarker) return;
    if (draggable) {
        reportMarker.dragging.enable();
    } else {
        reportMarker.dragging.disable();
    }
}

function applyLocationMode(mode) {
    locationMode = mode;
    const hint = document.getElementById('map-pick-hint');
    if (mode === 'pick') {
        if (hint) hint.classList.add('visible');
        setMarkerDraggable(true);
        // Simpan posisi GPS saat ini sebagai anchor lingkaran
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);
        if (!isNaN(lat) && !isNaN(lng)) {
            gpsOriginLat = lat;
            gpsOriginLng = lng;
            updateRadiusCircle(lat, lng);
        }
        if (reportMap) reportMap.getContainer().style.cursor = 'crosshair';
    } else {
        if (hint) hint.classList.remove('visible');
        setMarkerDraggable(false);
        // Reset GPS origin dan hapus circle, ambil GPS baru
        gpsOriginLat = null;
        gpsOriginLng = null;
        if (reportCircle) { 
            if (reportMap) reportMap.removeLayer(reportCircle); 
            reportCircle = null; 
        }
        if (reportMap) reportMap.getContainer().style.cursor = '';
        requestCurrentLocation();
    }
}

function setReportCoordinates(lat, lng) {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    if (latInput && lngInput) {
        latInput.value = lat;
        lngInput.value = lng;
    }
    initializeReportMap(lat, lng);
    reverseGeocodeCoordinates(lat, lng);
}

async function reverseGeocodeCoordinates(lat, lng) {
    try {
        const locationDisplay = document.getElementById('location-display');
        if (locationDisplay) {
            locationDisplay.value = 'Mencari lokasi...';
        }

        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, {
            headers: {
                'Accept-Language': 'id'
            }
        });

        if (!response.ok) {
            throw new Error('Reverse geocoding failed');
        }

        const data = await response.json();
        const address = data.address || {};
        
        // Buat alamat dari komponen OpenStreetMap
        const addressParts = [];
        
        if (address.road || address.pedestrian || address.path) {
            addressParts.push(address.road || address.pedestrian || address.path);
        }
        if (address.suburb || address.village) {
            addressParts.push(address.suburb || address.village);
        }
        if (address.city || address.town || address.municipality) {
            addressParts.push(address.city || address.town || address.municipality);
        }
        if (address.county || address.state_district) {
            addressParts.push(address.county || address.state_district);
        }

        const fullAddress = addressParts.filter(p => p && p.length > 0).join(', ') || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        
        if (locationDisplay) {
            locationDisplay.value = fullAddress;
        }
        const locationInput = document.getElementById('location');
        if (locationInput) {
            locationInput.value = fullAddress;
        }

        showToast('Lokasi berhasil diperbarui', 'success');
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        const locationDisplay = document.getElementById('location-display');
        if (locationDisplay) {
            locationDisplay.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
        const locationInput = document.getElementById('location');
        if (locationInput) {
            locationInput.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
    }
}

function requestCurrentLocation() {
    if (!navigator.geolocation) {
        initializeReportMap();
        return;
    }
    navigator.geolocation.getCurrentPosition((position) => {
        setReportCoordinates(position.coords.latitude, position.coords.longitude);
    }, (error) => {
        initializeReportMap();
        console.warn('Geolocation error', error);
    }, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
    });
}

function setupCreateForm() {
    const existingLat = document.getElementById('latitude')?.value;
    const existingLng = document.getElementById('longitude')?.value;
    
    try {
        // Jika ada data existing (mode edit), gunakan koordinat tersebut
        if (existingLat && existingLng && existingLat !== '' && existingLng !== '') {
            console.log('Using existing coordinates:', existingLat, existingLng);
            initializeReportMap(parseFloat(existingLat), parseFloat(existingLng));
            const locationDisplay = document.getElementById('location-display');
            if (locationDisplay && !locationDisplay.value) {
                reverseGeocodeCoordinates(parseFloat(existingLat), parseFloat(existingLng));
            }
        } else {
            // GPS mode default akan dipanggil oleh applyLocationMode('gps') di bawah
            initializeReportMap(); // inisialisasi peta dulu dengan posisi default
        }
    } catch (error) {
        console.error('Setup form error:', error);
        initializeReportMap(); // fallback
    }

    if (typeof editReport !== 'undefined' && editReport) {
        document.getElementById('pet-name').value = editReport.pet_name || '';
        document.getElementById('species').value = editReport.species || '';
        document.getElementById('species-detail').value = editReport.species_detail || '';
        document.getElementById('location-display').value = editReport.location || '';
        document.getElementById('location_description').value = editReport.location_description || '';
        document.getElementById('description').value = editReport.description || '';
        const typeRadio = document.querySelector(`input[name="type"][value="${editReport.type}"]`);
        if (typeRadio) {
            typeRadio.checked = true;
            setReportType(editReport.type);
        }
        const knownSpeciesOptions = Array.from(document.querySelectorAll('#species option')).map(option => option.value);
        if (editReport.species) {
            if (knownSpeciesOptions.includes(editReport.species)) {
                document.getElementById('species').value = editReport.species;
            } else {
                document.getElementById('species').value = 'Lainnya';
                document.getElementById('custom-species-container').style.display = 'block';
                document.getElementById('custom-species').value = editReport.species;
            }
        }
        const reportDateInput = document.getElementById('report-date');
        if (reportDateInput) {
            let dateToUse = editReport.event_date || editReport.created_at;
            const editDate = new Date(dateToUse);
            const today = new Date();
            const minDate = new Date(today);
            minDate.setDate(minDate.getDate() - 7);
            const formatDateForInput = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            const formattedEditDate = formatDateForInput(editDate);
            const maxDate = formatDateForInput(today);
            const minDateStr = formatDateForInput(minDate);
            reportDateInput.min = minDateStr;
            reportDateInput.max = maxDate;
            reportDateInput.value = (editDate < minDate || editDate > today) ? maxDate : formattedEditDate;
        }
        const submitBtn = document.querySelector('.form-submit-btn');
        if (submitBtn) submitBtn.textContent = 'Perbarui Laporan';

        // Preview foto existing saat mode edit
        if (editReport.image_url) {
            const previewImg  = document.getElementById('preview-img');
            const previewName = document.getElementById('preview-file-name');
            if (previewImg) {
                previewImg.src = '../' + editReport.image_url;
                if (previewName) previewName.textContent = 'Foto saat ini';
                const ec = document.getElementById('upload-card-empty');
                if (ec) ec.style.display = 'none';
                const pc = document.getElementById('upload-card-preview');
                if (pc) pc.style.display = 'block';
                imageValidated = true; imageIsAnimal = true;
            }
        }
    }

    // ── Location mode selector ──────────────────────────────────────
    document.querySelectorAll('input[name="location_mode"]').forEach(radio => {
        radio.addEventListener('change', () => {
            applyLocationMode(radio.value);
        });
    });
    // Apply default mode (gps) on load
    applyLocationMode('gps');

    const reportTypeRadios = document.querySelectorAll('input[name="type"]');
    const reportImage = document.getElementById('report-image');
    const selectedFileName = document.getElementById('selected-file-name');
    const reportForm = document.getElementById('form-create-report');
    const speciesSelect = document.getElementById('species');
    const customSpeciesContainer = document.getElementById('custom-species-container');
    const customSpeciesInput = document.getElementById('custom-species');
    const isEditMode = (typeof editReport !== 'undefined' && editReport) ? true : false;

    if (!reportImage) {
        console.error('Report image element not found');
        return;
    }

    if (isEditMode && reportImage) {
        reportImage.required = false;
    }

    const speciesFinalInput = document.getElementById('species-final');

    function syncSpeciesFinal() {
        if (!speciesFinalInput) return;
        if (speciesSelect && speciesSelect.value === 'Lainnya') {
            speciesFinalInput.value = customSpeciesInput ? customSpeciesInput.value.trim() : '';
        } else {
            speciesFinalInput.value = speciesSelect ? speciesSelect.value : '';
        }
    }

    function applySpeciesVisibility() {
        if (!speciesSelect || !customSpeciesContainer) return;
        if (speciesSelect.value === 'Lainnya') {
            customSpeciesContainer.style.display = 'flex';
        } else {
            customSpeciesContainer.style.display = 'none';
            if (customSpeciesInput) customSpeciesInput.value = '';
        }
        syncSpeciesFinal();
    }

    if (speciesSelect) {
        speciesSelect.addEventListener('change', () => {
            applySpeciesVisibility();
            if (speciesSelect.value === 'Lainnya' && customSpeciesInput) {
                customSpeciesInput.focus();
            }
        });
    }

    if (customSpeciesInput) {
        customSpeciesInput.addEventListener('input', syncSpeciesFinal);
    }

    // Sync initial state (penting untuk mode edit & page load)
    applySpeciesVisibility();

    reportTypeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            setReportType(radio.value);
        });
    });

    const initialType = document.querySelector('input[name="type"]:checked');
    if (initialType) {
        setReportType(initialType.value);
    }

    reportImage.addEventListener('change', async () => {
        if (reportImage.files.length > 0) {
            const file = reportImage.files[0];
            showImagePreview(file);
            if (selectedFileName) selectedFileName.textContent = file.name;
            await validateImageWithTF(file);
        } else {
            resetUploadState();
        }
    });

    // Tombol ganti foto
    const btnChangePhoto = document.getElementById('btn-change-photo');
    if (btnChangePhoto) {
        btnChangePhoto.addEventListener('click', () => {
            reportImage.value = '';
            resetUploadState();
            reportImage.click();
        });
    }

    // Drag and drop
    const uploadCard = document.getElementById('upload-card-empty');
    if (uploadCard) {
        uploadCard.addEventListener('dragover', (e) => {
            e.preventDefault(); e.stopPropagation();
            uploadCard.style.backgroundColor = 'rgba(59,130,246,0.1)';
            uploadCard.style.borderColor = 'var(--primary)';
        });
        uploadCard.addEventListener('dragleave', (e) => {
            e.preventDefault(); e.stopPropagation();
            uploadCard.style.backgroundColor = '';
            uploadCard.style.borderColor = '';
        });
        uploadCard.addEventListener('drop', (e) => {
            e.preventDefault(); e.stopPropagation();
            uploadCard.style.backgroundColor = '';
            uploadCard.style.borderColor = '';
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                const dt = new DataTransfer();
                for (let i = 0; i < files.length; i++) dt.items.add(files[i]);
                reportImage.files = dt.files;
                reportImage.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    if (reportForm) {
        reportForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const type = document.querySelector('input[name="type"]:checked');
            const species = speciesSelect ? speciesSelect.value.trim() : '';
            const customSpecies = customSpeciesInput ? customSpeciesInput.value.trim() : '';
            const description = document.getElementById('description').value.trim();
            const reportDate = document.getElementById('report-date').value.trim();
            const isEdit = isEditMode;

            if (!isEdit && (!reportImage.files || reportImage.files.length === 0)) {
                showToast('Upload foto hewan terlebih dahulu', 'error');
                if (reportImage) reportImage.focus();
                return;
            }

            if (reportImage.files && reportImage.files.length > 0) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(reportImage.files[0].type)) {
                    showToast('Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WebP', 'error');
                    return;
                }
                if (reportImage.files[0].size > 5 * 1024 * 1024) {
                    showToast('Ukuran foto terlalu besar (max 5MB)', 'error');
                    return;
                }

                // ── Cek hasil validasi TensorFlow.js ──────────────────────
                if (imageValidated && !imageIsAnimal) {
                    showToast('❌ Foto yang diupload bukan foto hewan. Mohon upload foto hewan peliharaan yang jelas.', 'error');
                    const uploadCardEl = document.querySelector('.upload-card');
                    if (uploadCardEl) {
                        uploadCardEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }

                if (!imageValidated && reportImage.files.length > 0) {
                    // Model masih loading — tunggu dulu
                    showToast('Mohon tunggu, AI sedang menganalisis foto...', 'error');
                    return;
                }
            }

            if (!type) {
                showToast('Pilih jenis laporan (Ditemukan atau Hilang)', 'error');
                return;
            }
            if (!species) {
                showToast('Pilih spesies hewan', 'error');
                return;
            }
            if (species === 'Lainnya' && !customSpecies) {
                showToast('Masukkan nama spesies hewan', 'error');
                if (customSpeciesInput) customSpeciesInput.focus();
                return;
            }
            const locationDisplay = document.getElementById('location-display').value.trim();
            if (!locationDisplay) {
                showToast('Pilih lokasi di peta terlebih dahulu', 'error');
                return;
            }
            const locationDescription = document.getElementById('location_description').value.trim();
            if (!locationDescription) {
                showToast('Masukkan deskripsi lokasi', 'error');
                return;
            }
            if (!description) {
                showToast('Masukkan deskripsi detail hewan', 'error');
                return;
            }
            if (!reportDate) {
                showToast('Pilih tanggal pelaporan', 'error');
                return;
            }

            // ── GEMINI AI VALIDATION ─────────────────────────────────────
            const submitBtn = reportForm.querySelector('.form-submit-btn');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;"></i>Validasi laporan...';

            const petName = document.getElementById('pet-name')?.value.trim() || '';
            const speciesForValidation = species === 'Lainnya' ? customSpecies : species;
            const speciesDetail = document.getElementById('species-detail')?.value.trim() || '';

            let validationFailed = false;
            let validationIssues = [];

            try {
                console.log('Starting field validation...');
                const validationResponse = await fetch('../api/reports/validate_fields.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pet_name: petName,
                        species: speciesForValidation,
                        species_detail: speciesDetail,
                        description: description
                    })
                });

                console.log('Validation response status:', validationResponse.status);
                const validationData = await validationResponse.json();
                console.log('Validation data:', validationData);

                if (validationData.status === 'success') {
                    const validation = validationData.data;

                    // Check if validation has errors
                    if (!validation.is_valid && validation.errors && validation.errors.length > 0) {
                        validationFailed = true;
                        validationIssues = validation.errors;
                        console.error('Validation errors:', validationIssues);
                    }

                    // Auto-apply corrections
                    if (validation.has_corrections && validation.corrections) {
                        console.log('Corrections applied:', validation.corrections);

                        if (validation.corrections.species) {
                            console.log('Correcting species:', speciesForValidation, '→', validation.corrections.species);
                            customSpecies = validation.corrections.species;
                            showToast('✅ Spesies diperbaiki: ' + validation.corrections.species, 'success');
                        }

                        if (validation.corrections.pet_name) {
                            const petNameInput = document.getElementById('pet-name');
                            if (petNameInput) {
                                console.log('Correcting pet_name:', petNameInput.value, '→', validation.corrections.pet_name);
                                petNameInput.value = validation.corrections.pet_name;
                            }
                        }

                        if (validation.corrections.species_detail) {
                            const detailInput = document.getElementById('species-detail');
                            if (detailInput) {
                                console.log('Correcting species_detail:', detailInput.value, '→', validation.corrections.species_detail);
                                detailInput.value = validation.corrections.species_detail;
                            }
                        }

                        if (validation.corrections.description) {
                            const descInput = document.getElementById('description');
                            if (descInput) {
                                console.log('Correcting description');
                                descInput.value = validation.corrections.description;
                            }
                        }
                    }

                    // Show warnings as info messages
                    if (validation.warnings && validation.warnings.length > 0) {
                        console.warn('Validation warnings:', validation.warnings);
                        const warningsText = validation.warnings.join('\n• ');
                        showToast('⚠️ Catatan:\n• ' + warningsText, 'warning');
                    }
                } else {
                    console.warn('Validation failed:', validationData.message);
                    validationFailed = true;
                    validationIssues.push(validationData.message || 'Validasi gagal');
                }
            } catch (validationError) {
                console.error('Validation error:', validationError);
                // Don't block on network errors, let it continue
                console.log('Continuing despite validation error...');
            }

            // If validation failed, stop submission
            if (validationFailed && validationIssues.length > 0) {
                showToast('❌ Validasi gagal:\n• ' + validationIssues.join('\n• '), 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                return;
            }

            // ── END GEMINI VALIDATION ────────────────────────────────────

            const formData = new FormData(reportForm);
            if (species === 'Lainnya') {
                formData.set('species', customSpecies);
            }

            // ── Update button for photo verification ──────────────────────
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;"></i>Memverifikasi foto hewan...';

            // Tampilkan overlay status di upload card saat foto baru diupload
            const hasNewImage = reportImage.files && reportImage.files.length > 0;
            const uploadCardEl = document.querySelector('.upload-card');
            let validatingBadge = null;
            if (hasNewImage && uploadCardEl) {
                validatingBadge = document.createElement('div');
                validatingBadge.style.cssText = 'margin-top:12px;display:flex;align-items:center;gap:8px;color:#6366f1;font-size:0.85rem;font-weight:600;';
                validatingBadge.innerHTML = '<i class="fa-solid fa-robot fa-spin"></i> AI sedang memverifikasi foto hewan...';
                uploadCardEl.appendChild(validatingBadge);
            }

            try {
                const targetReportId = (typeof editReportId !== 'undefined') ? editReportId : '';
                const url = isEdit ? `../api/reports.php?action=update&id=${targetReportId}` : '../api/reports.php?action=create';
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    showToast('Respons server tidak valid: ' + text, 'error');
                    return;
                }

                if (data.status === 'success') {
                    submitBtn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:8px;"></i>Berhasil! Mengalihkan...';
                    showToast(isEdit ? 'Laporan berhasil diperbarui' : data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'profile.php';
                    }, 1200);
                } else {
                    // ── Foto bukan hewan — tampilkan error khusus ────────
                    if (response.status === 422) {
                        showToast('❌ ' + data.message, 'error');
                        // Highlight upload area
                        if (uploadCardEl) {
                            uploadCardEl.style.borderColor = '#ef4444';
                            uploadCardEl.style.background = 'linear-gradient(135deg, #fff5f5, #fee2e2)';
                            setTimeout(() => {
                                uploadCardEl.style.borderColor = '';
                                uploadCardEl.style.background = '';
                            }, 3000);
                        }
                        // Reset file input
                        reportImage.value = '';
                        if (selectedFileName) selectedFileName.textContent = 'Tidak ada file dipilih';
                    } else {
                        showToast(data.message, 'error');
                    }
                }
            } catch (error) {
                showToast('Gagal mengirim laporan: ' + error.message, 'error');
            } finally {
                // Selalu restore tombol & hapus badge validasi
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                if (validatingBadge) validatingBadge.remove();
            }
        });
    }
}

function setReportType(type) {
    document.querySelectorAll('.type-card').forEach((card) => {
        const input = card.querySelector('input[name="type"]');
        const isChecked = input && input.value === type;
        card.classList.toggle('active', isChecked);
        if (input) {
            input.checked = isChecked;
        }
    });
}

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function setReportDateRange() {
    const dateInput = document.getElementById('report-date');
    if (!dateInput) return;
    const today = new Date();
    const maxDate = formatLocalDate(today);
    const minDate = new Date(today);
    minDate.setDate(minDate.getDate() - 7);
    const minDateStr = formatLocalDate(minDate);
    dateInput.min = minDateStr;
    dateInput.max = maxDate;
    if (!dateInput.value) {
        dateInput.value = maxDate;
    }
}

// Cek nama file mengandung keyword hewan
function filenameContainsPetKeyword(filename) {
    const lower = filename.toLowerCase().replace(/[_\-\.]/g, ' ');
    return PET_KEYWORDS.some(kw => lower.includes(kw));
}

// Preload model di background
if (typeof cocoSsd !== 'undefined') {
    cocoSsd.load().then(m => { tfCocoSsd = m; console.log('COCO-SSD loaded ✓'); })
                  .catch(e => console.warn('COCO-SSD gagal load:', e));
}

/* ── PREVIEW HELPERS ── */
function showImagePreview(file) {
    const url = URL.createObjectURL(file);
    const previewImg = document.getElementById('preview-img');
    if (previewImg) {
        previewImg.src = url;
        previewImg.onload = () => URL.revokeObjectURL(url);
    }
    const fn = document.getElementById('preview-file-name');
    if (fn) fn.textContent = file.name;
    
    const ec = document.getElementById('upload-card-empty');
    if (ec) ec.style.display = 'none';
    
    const pc = document.getElementById('upload-card-preview');
    if (pc) pc.style.display = 'block';
}

function hideImagePreview() {
    const ec = document.getElementById('upload-card-empty');
    if (ec) ec.style.display = 'block';
    
    const pc = document.getElementById('upload-card-preview');
    if (pc) pc.style.display = 'none';
    
    const b = document.getElementById('preview-validation-badge');
    if (b) {
        b.style.display = 'none'; 
        b.innerHTML = '';
    }
}

function resetUploadState() {
    imageValidated = false; imageIsAnimal = false;
    hideImagePreview();
    const ec = document.getElementById('upload-card-empty');
    if (ec) {
        ec.style.borderColor = ''; 
        ec.style.background = '';
    }
    const sfn = document.getElementById('selected-file-name');
    if (sfn) sfn.textContent = 'Tidak ada file dipilih';
}

function setPreviewBadge(isAnimal, msg) {
    const b  = document.getElementById('preview-validation-badge');
    const pc = document.getElementById('upload-card-preview');
    if (b) {
        b.style.display    = 'flex';
        b.style.background = isAnimal ? 'rgba(16,185,129,0.90)' : 'rgba(239,68,68,0.90)';
        b.style.color      = '#fff';
        b.innerHTML = (isAnimal ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-xmark"></i> ') + msg;
    }
    if (pc) pc.style.borderColor = isAnimal ? '#10b981' : '#ef4444';
}

function setPreviewBadgeLoading() {
    const b = document.getElementById('preview-validation-badge');
    if (b) {
        b.style.display = 'flex';
        b.style.background = 'rgba(99,102,241,0.88)'; b.style.color = '#fff';
        b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> AI memverifikasi...';
    }
}

/* ── VALIDASI UTAMA ── */
async function validateImageWithTF(file) {
    setPreviewBadgeLoading();

    try {
        if (!tfCocoSsd && typeof cocoSsd !== 'undefined') {
            try { tfCocoSsd = await cocoSsd.load(); }
            catch(e) {
                // Model gagal load → jangan block, loloskan saja
                console.warn('COCO-SSD tidak bisa diload:', e);
                imageValidated = true; imageIsAnimal = true;
                const b = document.getElementById('preview-validation-badge');
                if (b) b.style.display = 'none';
                return;
            }
        }

        // Buat img element dari file
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);
        img.src = objectUrl;
        await new Promise(resolve => { img.onload = resolve; });
        URL.revokeObjectURL(objectUrl);

        let hasAnimal = false;
        let personRatio = 0;

        if (tfCocoSsd) {
            const detections = await tfCocoSsd.detect(img);
            console.log('COCO-SSD detections:', detections);

            // ── Pisahkan deteksi hewan vs manusia ──
            const animalDets = detections.filter(d => COCO_ANIMALS.includes(d.class) && d.score > 0.30);
            const personDets = detections.filter(d => d.class === 'person'             && d.score > 0.40);

            hasAnimal = animalDets.length > 0;

            // Hitung rasio area manusia vs total gambar
            const imgArea    = img.naturalWidth * img.naturalHeight || img.width * img.height;
            const personArea = personDets.reduce((s, d) => s + d.bbox[2] * d.bbox[3], 0);
            personRatio = imgArea > 0 ? personArea / imgArea : 0;
        }

        // ── Keputusan ──────────────────────────────────────────
        if (hasAnimal) {
            // ✅ Ada hewan terdeteksi
            imageValidated = true; imageIsAnimal = true;
            setPreviewBadge(true, 'Foto terverifikasi');

        } else if (personRatio > 0.20) {
            // ❌ Foto manusia mendominasi
            imageValidated = true; imageIsAnimal = false;
            setPreviewBadge(false, 'Foto manusia tidak diperbolehkan');

        } else {
            // COCO-SSD tidak mendeteksi hewan dari 10 class-nya.
            // Tapi COCO-SSD tidak tahu kura-kura, ikan, kelinci, hamster, dll.
            // Strategi fallback berlapis:

            // Satu-satunya fallback yang aman: nama file mengandung keyword hewan.
            const reportImage = document.getElementById('report-image');
            const fileName = (reportImage && reportImage.files.length > 0) ? reportImage.files[0].name : '';
            const fileHasPetKeyword = filenameContainsPetKeyword(fileName);

            if (fileHasPetKeyword) {
                // Nama file jelas mengandung keyword hewan → loloskan
                imageValidated = true; imageIsAnimal = true;
                setPreviewBadge(true, 'Foto hewan terverifikasi');
            } else {
                // Tidak ada hewan terdeteksi & nama file tidak menunjukkan hewan → TOLAK
                imageValidated = true; imageIsAnimal = false;
                setPreviewBadge(false, 'Hewan tidak terdeteksi pada foto ini');
            }
        }

    } catch(err) {
        console.error('COCO-SSD error:', err);
        // Error teknis → jangan block user
        imageValidated = true; imageIsAnimal = true;
        const b = document.getElementById('preview-validation-badge');
        if (b) b.style.display = 'none';
    }
}