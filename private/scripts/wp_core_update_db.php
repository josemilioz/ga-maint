<?php
/**
 * Quicksilver script: run wp core update-db after deploy or DB clone.
 */

// Grab Quicksilver environment info
$site_id   = $_ENV['PANTHEON_SITE'];
$env       = $_ENV['PANTHEON_ENVIRONMENT'];
$site_name = $_ENV['PANTHEON_SITE_NAME'];

echo "==> Running wp core update-db on {$site_name} ({$env})\n";

$command = 'wp core update-db 2>&1';
$output  = [];
$exit    = 0;

exec($command, $output, $exit);

foreach ($output as $line) {
    echo $line . "\n";
}

if ($exit === 0) {
    echo "==> Database updated successfully.\n";
} else {
    echo "==> WARNING: wp core update-db exited with code {$exit}\n";
    // Non-fatal — don't throw, just log
}