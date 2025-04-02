<?php
// Path to the file
$file_path = __DIR__ . '/includes/agents/sdk/class-mpai-sdk-integration.php';

// Read the file content
$content = file_get_contents($file_path);

// Replace error log calls with logger method calls
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

// Write the modified content back to the file
file_put_contents($file_path, $content);

echo "Fixed logger calls in " . $file_path . "\n";