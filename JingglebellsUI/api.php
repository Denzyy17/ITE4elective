<?php
/**
 * API endpoint for review analysis
 * Handles fake review detection and sentiment analysis
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['text']) || empty(trim($input['text']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Review text is required']);
    exit();
}

$reviewText = trim($input['text']);

// Validate text length
if (strlen($reviewText) > 5000) {
    http_response_code(400);
    echo json_encode(['error' => 'Review text is too long. Maximum 5000 characters.']);
    exit();
}

// Get the directory where this script is located
$scriptDir = __DIR__;
$pythonScript = $scriptDir . DIRECTORY_SEPARATOR . 'predict.py';

// Check if Python script exists
if (!file_exists($pythonScript)) {
    http_response_code(500);
    echo json_encode(['error' => 'Prediction script not found']);
    exit();
}

// Escape the text for shell execution
$escapedText = escapeshellarg($reviewText);

// Build Python command
// On Windows, prefer 'py' launcher, then try python/python3
$pythonCmd = 'python3';
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows: try 'py' launcher first (most reliable on Windows)
    $pythonTest = shell_exec('py --version 2>nul');
    if (!empty($pythonTest)) {
        $pythonCmd = 'py';
    } else {
        // Fallback to python or python3
        $pythonTest = shell_exec('where python 2>nul');
        if (!empty($pythonTest)) {
            $pythonCmd = 'python';
        } else {
            $pythonTest = shell_exec('where python3 2>nul');
            if (!empty($pythonTest)) {
                $pythonCmd = 'python3';
            }
        }
    }
}

// Execute Python script
$command = $pythonCmd . ' ' . escapeshellarg($pythonScript) . ' ' . $escapedText . ' 2>&1';

// Run the command
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// Combine output lines
$outputString = implode("\n", $output);

// Check for errors
if ($returnCode !== 0) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Prediction failed',
        'details' => $outputString,
        'return_code' => $returnCode
    ]);
    exit();
}

// Extract JSON from output (filter out any non-JSON lines)
// Try to find JSON object in the output
if (preg_match('/\{[\s\S]*\}/', $outputString, $matches)) {
    $outputString = $matches[0];
} else {
    // If no JSON found, try to clean the output
    $lines = explode("\n", $outputString);
    $jsonLines = [];
    $inJson = false;
    $braceCount = 0;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        
        // Check if line contains JSON start
        if (strpos($trimmed, '{') !== false) {
            $inJson = true;
        }
        
        if ($inJson) {
            $jsonLines[] = $line;
            $braceCount += substr_count($line, '{') - substr_count($line, '}');
            // If braces are balanced, we have complete JSON
            if ($braceCount === 0 && strpos($trimmed, '}') !== false) {
                break;
            }
        }
    }
    
    if (!empty($jsonLines)) {
        $outputString = implode("\n", $jsonLines);
    }
}

// Parse JSON response from Python
$result = json_decode($outputString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from prediction script',
        'details' => $outputString,
        'json_error' => json_last_error_msg()
    ]);
    exit();
}

// Check if there's an error in the result
if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode($result);
    exit();
}

// Return successful response
http_response_code(200);
echo json_encode($result);

