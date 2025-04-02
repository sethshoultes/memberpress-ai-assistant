<?php
// Path to the file
$file_path = __DIR__ . '/includes/agents/sdk/class-mpai-sdk-integration.php';

// Read the file content
$content = file_get_contents($file_path);

// Array of string replacements [pattern => replacement]
$replacements = [
    // Fix $error_log calls with proper $this->logger references
    '$error_log(\'MPAI SDK INFO: \'' => '$this->logger->info(',
    '$error_log(\'MPAI SDK WARNING: \'' => '$this->logger->warning(',
    '$error_log(\'MPAI SDK ERROR: \'' => '$this->logger->error(',
    
    // Fix error_log calls (without $) to use $this->logger
    'error_log(\'MPAI SDK INFO: \'' => '$this->logger->info(',
    'error_log(\'MPAI SDK WARNING: \'' => '$this->logger->warning(',
    'error_log(\'MPAI SDK ERROR: \'' => '$this->logger->error(',
    'error_log("MPAI SDK INFO:' => '$this->logger->info(',
    'error_log("MPAI SDK WARNING:' => '$this->logger->warning(',
    'error_log("MPAI SDK ERROR:' => '$this->logger->error(',
    
    // Don't modify the logger definition lines
    // These lines define the default logger methods and should still use error_log internally
];

// Apply replacements
foreach ($replacements as $pattern => $replacement) {
    $content = str_replace($pattern, $replacement, $content);
}

// Double-check we don't modify the default logger methods
$logger_method_lines = [
    "'info'    => function( \$message, \$context = [] ) { error_log( 'MPAI SDK INFO: ' . \$message ); },",
    "'warning' => function( \$message, \$context = [] ) { error_log( 'MPAI SDK WARNING: ' . \$message ); },",
    "'error'   => function( \$message, \$context = [] ) { error_log( 'MPAI SDK ERROR: ' . \$message ); },"
];

$original_content = file_get_contents($file_path);
foreach ($logger_method_lines as $line) {
    if (strpos($original_content, $line) !== false && strpos($content, $line) === false) {
        // If our replacements removed the logger methods, put them back
        $content = str_replace(
            "'info'    => function( \$message, \$context = [] ) { \$this->logger->info( \$message ); },",
            "'info'    => function( \$message, \$context = [] ) { error_log( 'MPAI SDK INFO: ' . \$message ); },",
            $content
        );
        $content = str_replace(
            "'warning' => function( \$message, \$context = [] ) { \$this->logger->warning( \$message ); },",
            "'warning' => function( \$message, \$context = [] ) { error_log( 'MPAI SDK WARNING: ' . \$message ); },",
            $content
        );
        $content = str_replace(
            "'error'   => function( \$message, \$context = [] ) { \$this->logger->error( \$message ); },",
            "'error'   => function( \$message, \$context = [] ) { error_log( 'MPAI SDK ERROR: ' . \$message ); },",
            $content
        );
    }
}

// Write the modified content back to the file
file_put_contents($file_path, $content);

echo "Fixed logger calls in " . $file_path . "\n";