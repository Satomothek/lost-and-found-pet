<?php
/**
 * Validate Report Fields API
 * POST /api/reports/validate_fields.php
 * Pure PHP validation - no external API required
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../lib/functions.php';
require_once '../../lib/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$petName = trim($input['pet_name'] ?? '');
$species = trim($input['species'] ?? '');
$speciesDetail = trim($input['species_detail'] ?? '');
$description = trim($input['description'] ?? '');

error_log("=== Validate Fields API ===");
error_log("Species: $species");

// Validate fields
$validation = validatePetFields($petName, $species, $speciesDetail, $description);

error_log("Validation result: " . json_encode($validation));

// Return result
successResponse('Validation complete', $validation);

closeConnection($connection);
?>
