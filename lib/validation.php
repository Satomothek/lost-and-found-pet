<?php
/**
 * Pet Report Validation Library
 * lib/validation.php
 *
 * Validasi spesies: Levenshtein (lokal)
 * Validasi ras/species_detail: TheCatAPI / TheDogAPI + cache MySQL 7 hari
 */

// ─── Konstanta ────────────────────────────────────────────────────────────────

const VALID_SPECIES = [
    'anjing', 'dog',
    'kucing', 'cat',
    'kelinci', 'rabbit', 'bunny',
    'burung', 'bird',
    'ikan', 'fish',
    'reptil', 'turtle', 'lizard', 'snake', 'iguana',
    'hamster',
    'marmut', 'guinea pig',
    'ferret', 'musang',
    'bebek', 'ayam',
    'lainnya', 'other',
];

// Typo umum → koreksi langsung tanpa fuzzy
const SPECIES_TYPOS = [
    'ajning'   => 'anjing', 'anjin'    => 'anjing', 'anging'   => 'anjing',
    'kucng'    => 'kucing', 'kcing'    => 'kucing',
    'kelici'   => 'kelinci', 'kelinsi' => 'kelinci',
    'burng'    => 'burung',
    'ikn'      => 'ikan',   'ika'      => 'ikan',
    'reptill'  => 'reptil',
    'hamstr'   => 'hamster',
    'marmot'   => 'marmut', 'marmutt'  => 'marmut',
    'guinea fowl' => 'guinea pig',
    'frret'    => 'ferret',
];

// Mapping spesies lokal → nama API
const SPECIES_API_MAP = [
    'kucing' => 'cat',
    'cat'    => 'cat',
    'anjing' => 'dog',
    'dog'    => 'dog',
];

// API endpoint
const CAT_BREEDS_API = 'https://api.thecatapi.com/v1/breeds';
const DOG_BREEDS_API = 'https://api.thedogapi.com/v1/breeds';


// ─── Helper: Levenshtein Similarity ──────────────────────────────────────────

function stringSimilarity(string $a, string $b): float {
    $a = strtolower($a);
    $b = strtolower($b);
    $maxLen = max(strlen($a), strlen($b));
    if ($maxLen === 0) return 100.0;
    $dist = levenshtein($a, $b);           // PHP built-in
    return (($maxLen - $dist) / $maxLen) * 100;
}

function findBestMatch(string $input, array $list): array {
    $input = strtolower(trim($input));

    // 1. Exact match
    if (in_array($input, array_map('strtolower', $list))) {
        return ['match' => $input, 'confidence' => 100, 'type' => 'exact'];
    }

    // 2. Known typo
    if (isset(SPECIES_TYPOS[$input])) {
        return ['match' => SPECIES_TYPOS[$input], 'confidence' => 95, 'type' => 'typo'];
    }

    // 3. Fuzzy
    $best = null; $bestScore = 0;
    foreach ($list as $item) {
        $score = stringSimilarity($input, $item);
        if ($score > $bestScore && $score >= 65) {
            $bestScore = $score;
            $best = $item;
        }
    }

    if ($best) {
        return ['match' => $best, 'confidence' => round($bestScore), 'type' => 'fuzzy'];
    }

    return ['match' => null, 'confidence' => 0, 'type' => 'none'];
}


// ─── Cache ras di MySQL ───────────────────────────────────────────────────────

/**
 * Ambil daftar ras dari cache MySQL.
 * Kembalikan array nama ras (lowercase), atau null jika cache expired/kosong.
 */
function getBreedsFromCache(string $speciesKey): ?array {
    global $connection;
    if (!$connection) return null;

    $stmt = $connection->prepare(
        "SELECT breed_names FROM breed_cache
          WHERE species_key = ?
            AND created_at > NOW() - INTERVAL 7 DAY
          LIMIT 1"
    );
    if (!$stmt) return null;

    $stmt->bind_param('s', $speciesKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    return json_decode($row['breed_names'], true) ?: null;
}

/**
 * Simpan daftar ras ke cache MySQL.
 */
function saveBreedsToCache(string $speciesKey, array $breeds): void {
    global $connection;
    if (!$connection) return;

    $data = json_encode($breeds);
    $stmt = $connection->prepare(
        "INSERT INTO breed_cache (species_key, breed_names, created_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE breed_names = ?, created_at = NOW()"
    );
    if (!$stmt) return;

    $stmt->bind_param('sss', $speciesKey, $data, $data);
    $stmt->execute();
    $stmt->close();
}


// ─── Fetch ras dari API eksternal ────────────────────────────────────────────

/**
 * Ambil daftar ras dari TheCatAPI / TheDogAPI.
 * Kembalikan array nama ras lowercase, atau null jika gagal.
 */
function fetchBreedsFromAPI(string $speciesKey): ?array {
    $url = match($speciesKey) {
        'cat' => CAT_BREEDS_API,
        'dog' => DOG_BREEDS_API,
        default => null,
    };
    if (!$url) return null;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,           // jangan block terlalu lama
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $code !== 200) {
        error_log("[validation] API $url gagal (HTTP $code)");
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) return null;

    // Ambil nama ras saja, simpan lowercase
    $breeds = array_map(fn($b) => strtolower($b['name'] ?? ''), $data);
    $breeds = array_filter($breeds);   // buang string kosong
    $breeds = array_values($breeds);

    error_log("[validation] Fetched " . count($breeds) . " breeds for $speciesKey");
    return $breeds;
}

/**
 * Ambil daftar ras: cek cache dulu, kalau miss → fetch API → simpan cache.
 */
function getBreeds(string $speciesKey): ?array {
    $cached = getBreedsFromCache($speciesKey);
    if ($cached !== null) {
        error_log("[validation] Cache hit: $speciesKey (" . count($cached) . " breeds)");
        return $cached;
    }

    $fresh = fetchBreedsFromAPI($speciesKey);
    if ($fresh !== null) {
        saveBreedsToCache($speciesKey, $fresh);
    }
    return $fresh;   // bisa null jika API + cache sama-sama gagal
}


// ─── Validasi species_detail (ras) ───────────────────────────────────────────

/**
 * Validasi ras hewan.
 * - Jika spesies didukung (kucing/anjing): cek ke API (via cache).
 * - Jika spesies tidak didukung API: lewati, anggap valid.
 *
 * Return:
 *   ['valid' => true]
 *   ['valid' => false, 'error' => '...']
 *   ['valid' => true,  'correction' => '...', 'warning' => '...']  ← ada typo tapi masih mirip
 */
function validateBreed(string $speciesDetail, string $species): array {
    $speciesDetail = trim($speciesDetail);
    if ($speciesDetail === '') {
        return ['valid' => true];   // opsional, boleh kosong
    }

    // Tentukan key API
    $speciesKey = SPECIES_API_MAP[strtolower($species)] ?? null;
    if (!$speciesKey) {
        // Spesies di luar kucing/anjing → tidak ada API, lewati
        return ['valid' => true];
    }

    $breeds = getBreeds($speciesKey);

    if ($breeds === null) {
        // API & cache gagal → jangan block user, loloskan dengan warning
        error_log("[validation] Breed list unavailable for $speciesKey, skipping breed check");
        return ['valid' => true, 'warning' => 'Validasi ras tidak tersedia saat ini, mohon periksa kembali nama ras.'];
    }

    $result = findBestMatch($speciesDetail, $breeds);

    if ($result['confidence'] === 100) {
        return ['valid' => true];
    }

    if ($result['confidence'] >= 65) {
        // Mirip → koreksi otomatis + beri tahu user
        return [
            'valid'      => true,
            'correction' => ucwords($result['match']),   // e.g. "Persian"
            'warning'    => "Ras diperbaiki: '$speciesDetail' → '{$result['match']}'",
        ];
    }

    // Terlalu berbeda → error
    return [
        'valid' => false,
        'error' => "Ras '$speciesDetail' tidak ditemukan untuk spesies $species. "
                 . "Contoh ras yang valid: " . implode(', ', array_slice($breeds, 0, 5)) . ', dst.',
    ];
}


// ─── Fungsi utama ─────────────────────────────────────────────────────────────

function validatePetFields(
    string $petName,
    string $species,
    string $speciesDetail,
    string $description
): array {
    $errors      = [];
    $corrections = [];
    $warnings    = [];

    error_log("=== validatePetFields === species=$species | detail=$speciesDetail");

    // ── 1. Spesies ────────────────────────────────────────────────────────────
    if (empty($species)) {
        $errors[] = 'Spesies hewan tidak boleh kosong';
    } else {
        $speciesMatch = findBestMatch($species, VALID_SPECIES);

        if ($speciesMatch['confidence'] >= 65) {
            if ($speciesMatch['confidence'] < 100) {
                // Koreksi typo spesies
                $corrections['species'] = $speciesMatch['match'];
                $warnings[] = "Spesies diperbaiki: '$species' → '{$speciesMatch['match']}'";
                // Gunakan versi yang sudah dikoreksi untuk validasi ras
                $species = $speciesMatch['match'];
            }
        } else {
            $errors[] = "Spesies '$species' tidak dikenali. "
                      . "Contoh: anjing, kucing, kelinci, burung, ikan, reptil, hamster.";
        }
    }

    // ── 2. Ras / species_detail (cek internet + cache) ────────────────────────
    if (!empty($speciesDetail) && empty($errors)) {
        $breedResult = validateBreed($speciesDetail, $species);

        if (!$breedResult['valid']) {
            $errors[] = $breedResult['error'];
        } else {
            if (isset($breedResult['correction'])) {
                $corrections['species_detail'] = $breedResult['correction'];
            }
            if (isset($breedResult['warning'])) {
                $warnings[] = $breedResult['warning'];
            }
        }
    }

    // ── 3. Deskripsi ─────────────────────────────────────────────────────────
    if (empty($description)) {
        $errors[] = 'Deskripsi laporan tidak boleh kosong';
    } elseif (strlen($description) < 20) {
        $warnings[] = 'Deskripsi terlalu singkat, mohon berikan detail lebih lengkap (min. 20 karakter)';
    }

    // ── 4. Nama hewan (opsional, hanya warning jika terlalu pendek) ───────────
    if (!empty($petName) && strlen($petName) < 2) {
        $warnings[] = 'Nama hewan terlalu pendek';
    }

    return [
        'is_valid'        => count($errors) === 0,
        'errors'          => $errors,
        'warnings'        => $warnings,
        'corrections'     => $corrections,
        'has_corrections' => count($corrections) > 0,
    ];
}