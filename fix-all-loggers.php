<?php
// Path to the file to fix
$file_path = __DIR__ . '/includes/agents/sdk/class-mpai-sdk-integration.php';

// Read the file
$content = file_get_contents($file_path);

// Replace all instances of $error_log with the proper logger calls
$content = preg_replace(
    '/\$error_log\(\'MPAI SDK INFO: \' \. (.+?)\);/s',
    '$this->logger->info($1);',
    $content
);

$content = preg_replace(
    '/\$error_log\(\'MPAI SDK WARNING: \' \. (.+?)\);/s',
    '$this->logger->warning($1);',
    $content
);

$content = preg_replace(
    '/\$error_log\(\'MPAI SDK ERROR: \' \. (.+?)\);/s',
    '$this->logger->error($1);',
    $content
);

// Write the fixed content back to the file
file_put_contents($file_path, $content);

echo "Fixed all logger calls in {$file_path}\n";