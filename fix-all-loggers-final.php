<?php
// Path to the file to fix
$file_path = __DIR__ . '/includes/agents/sdk/class-mpai-sdk-integration.php';

// Read the file
$content = file_get_contents($file_path);

// Replace all instances of logger methods with direct error_log calls
$replacements = [
    // Replace $this->logger->info calls
    '$this->logger->info(' => 'error_log("MPAI SDK INFO: " . ',
    
    // Replace $this->logger->warning calls
    '$this->logger->warning(' => 'error_log("MPAI SDK WARNING: " . ',
    
    // Replace $this->logger->error calls
    '$this->logger->error(' => 'error_log("MPAI SDK ERROR: " . ',
];

// Apply replacements
foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Write the fixed content back to the file
file_put_contents($file_path, $content);

echo "Fixed all logger calls in {$file_path} to use error_log directly\n";