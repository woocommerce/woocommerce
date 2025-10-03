<?php
/**
 * WooCommerce New Functions Checker
 * 
 * This script checks for new functions added in the "includes" or "src" 
 * directories between two git branches.
 * 
 * Usage: php check_new_functions.php <pr_branch> <compare_branch>
 * Example: php check_new_functions.php feature/new-functions trunk
 */

// Check if we have the required arguments
if ($argc < 3) {
    echo "Usage: php check_new_functions.php <pr_branch> <compare_branch>\n";
    echo "Example: php check_new_functions.php feature/new-functions trunk\n";
    exit(1);
}

$prBranch = $argv[1];
$compareBranch = $argv[2];

echo "Checking for new functions between branch '$prBranch' and '$compareBranch'...\n\n";

// Execute git diff command to get changes between branches for includes/ and src/ directories only
$diffCommand = "git diff $compareBranch..$prBranch -- includes/ src/";
$output = [];
$returnCode = 0;

exec($diffCommand, $output, $returnCode);

if ($returnCode !== 0) {
    echo "Error: Failed to execute git diff command\n";
    echo "Command: $diffCommand\n";
    exit(1);
}

if (empty($output)) {
    echo "No changes found in includes/ or src/ directories.\n";
    exit(0);
}

// Parse the diff output to find added and deleted functions
$addedFunctions = [];
$deletedFunctions = [];
$functionFileMap = [];

$currentFile = '';
foreach ($output as $line) {
    // Track current file being processed
    if (preg_match('/^diff --git a\/(.+?) b\/(.+?)$/', $line, $matches)) {
        $currentFile = $matches[2]; // Use the 'b' (new) file path
    } elseif (preg_match('/^\+\+\+ b\/(.+?)$/', $line, $matches)) {
        $currentFile = $matches[1]; // Alternative way to get file path
    }
    
    // Look for added functions (lines starting with +)
    if (preg_match('/^\+.*?function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches)) {
        $functionName = $matches[1];
        $addedFunctions[] = $functionName;
        $functionFileMap[$functionName] = $currentFile;
    }
    
    // Look for deleted functions (lines starting with -)
    if (preg_match('/^\-.*?function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches)) {
        $functionName = $matches[1];
        $deletedFunctions[] = $functionName;
    }
}

// Remove duplicates while preserving order
$addedFunctions = array_unique($addedFunctions);
$deletedFunctions = array_unique($deletedFunctions);

// Print the diff output
echo implode("\n", $output) . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "FUNCTION ANALYSIS RESULTS:\n";
echo str_repeat("=", 60) . "\n\n";

echo "1. Added Functions:\n";
var_dump($addedFunctions);

echo "\n2. Deleted Functions:\n";
var_dump($deletedFunctions);

echo "\n3. Function File Map (Added Functions -> Files):\n";
var_dump($functionFileMap);