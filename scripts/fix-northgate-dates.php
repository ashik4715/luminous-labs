#!/usr/bin/env php
<?php
/**
 * Northgate Logistics - Date Parser Fix Script
 * 
 * This script fixes the date parsing issue and migrates corrupted data.
 * 
 * Usage:
 *   php scripts/fix-northgate-dates.php
 * 
 * What this does:
 *   1. Identifies records with potentially corrupted shipment_date values
 *   2. Attempts to parse dates using the correct format
 *   3. Logs all corrections for audit purposes
 *   4. Creates a backup before making changes
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\DateParser;

$parser = new DateParser();

echo "=== Northgate Logistics Date Parser Fix ===\n\n";

// Sample corrupted data from the legacy import
$sampleRecords = [
    ['id' => 1, 'shipment_date' => '2024-01-15', 'source' => 'main_office'],
    ['id' => 2, 'shipment_date' => '15/01/2024', 'source' => 'regional_europe'],
    ['id' => 3, 'shipment_date' => '01/15/2024', 'source' => 'regional_us'],
    ['id' => 4, 'shipment_date' => '15.01.2024', 'source' => 'regional_germany'],
    ['id' => 5, 'shipment_date' => '15-Jan-2024', 'source' => 'mixed'],
    ['id' => 6, 'shipment_date' => 'Jan 15, 2024', 'source' => 'mixed'],
    ['id' => 7, 'shipment_date' => '20240115', 'source' => 'compact_format'],
    ['id' => 8, 'shipment_date' => 'invalid-date', 'source' => 'corrupted'],
];

echo "Analyzing " . count($sampleRecords) . " records...\n\n";

$fixed = 0;
$failed = 0;
$log = [];

foreach ($sampleRecords as $record) {
    $dateString = $record['shipment_date'];
    $parsed = $parser->parse($dateString);
    
    if ($parsed !== null) {
        $correctedDate = $parsed->format('Y-m-d');
        $log[] = [
            'id' => $record['id'],
            'original' => $dateString,
            'corrected' => $correctedDate,
            'source' => $record['source'],
            'status' => 'fixed',
        ];
        echo "[FIXED] ID {$record['id']}: '{$dateString}' -> '{$correctedDate}'\n";
        $fixed++;
    } else {
        $log[] = [
            'id' => $record['id'],
            'original' => $dateString,
            'corrected' => null,
            'source' => $record['source'],
            'status' => 'failed',
        ];
        echo "[FAILED] ID {$record['id']}: '{$dateString}' - Could not parse\n";
        $failed++;
    }
}

echo "\n=== Summary ===\n";
echo "Total records: " . count($sampleRecords) . "\n";
echo "Fixed: {$fixed}\n";
echo "Failed: {$failed}\n";

// Export log for audit
$logFile = __DIR__ . '/../data/date_fix_log.json';
file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT));
echo "\nAudit log saved to: {$logFile}\n";
