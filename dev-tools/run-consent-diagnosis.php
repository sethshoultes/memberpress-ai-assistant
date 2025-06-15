<?php
/**
 * Consent Flow Diagnosis Runner
 * 
 * Simple script to run consent flow diagnostics
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MemberPress AI Assistant - Consent Flow Diagnosis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .diagnostic-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .run-button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin: 10px 5px; }
        .run-button:hover { background: #005a87; }
        .results { margin-top: 20px; }
        iframe { width: 100%; height: 600px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>MemberPress AI Assistant - Consent Flow Diagnosis</h1>
    
    <div class="diagnostic-section">
        <h2>Diagnostic Tools</h2>
        <p>Use these tools to diagnose consent flow issues after plugin reactivation:</p>
        
        <button class="run-button" onclick="runDiagnosis()">Run Basic Diagnosis</button>
        <button class="run-button" onclick="runValidation()">Run Flow Validation</button>
        <button class="run-button" onclick="runBoth()">Run Both Tests</button>
        
        <div class="results" id="results"></div>
    </div>
    
    <script>
        function runDiagnosis() {
            document.getElementById('results').innerHTML = '<iframe src="consent-flow-diagnosis.php"></iframe>';
        }
        
        function runValidation() {
            document.getElementById('results').innerHTML = '<iframe src="consent-flow-validation.php"></iframe>';
        }
        
        function runBoth() {
            document.getElementById('results').innerHTML = 
                '<h3>Basic Diagnosis</h3><iframe src="consent-flow-diagnosis.php" style="height: 400px;"></iframe>' +
                '<h3>Flow Validation</h3><iframe src="consent-flow-validation.php" style="height: 400px;"></iframe>';
        }
    </script>
</body>
</html>