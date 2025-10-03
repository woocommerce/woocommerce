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
$addedFunctionFileMap = [];
$deletedFunctions = [];

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
        $addedFunctionFileMap[$functionName] = $currentFile;
    }
    
    // Look for deleted functions (lines starting with -)
    if (preg_match('/^\-.*?function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches)) {
        $functionName = $matches[1];
        $deletedFunctions[] = $functionName;
    }
}

// Calculate net added functions (added minus deleted) and clean file paths
$netFunctionFileMap = [];
foreach ($addedFunctionFileMap as $function => $filePath) {
    // Skip functions that were also deleted (net zero change)
    if (in_array($function, $deletedFunctions)) {
        continue;
    }
    
    // Remove "plugins/woocommerce/" prefix from file path
    if (strpos($filePath, 'plugins/woocommerce/') === 0) {
        $filePath = substr($filePath, 19); // Remove "plugins/woocommerce/" (19 characters)
    }
    $netFunctionFileMap[$function] = $filePath;
}

// Check if there are any net added functions
if (empty($netFunctionFileMap)) {
    exit(0);
}

// Print error message and formatted table
echo "No new functions are allowed in WooCommerce. All the new code should go into classes in the src directory\n\n";

// Find the longest function name to determine column width
$maxFunctionLength = max(array_map('strlen', array_keys($netFunctionFileMap)));
$columnWidth = max(15, $maxFunctionLength + 2); // Minimum width of 15, plus 2 for padding

// Format as table
printf("%-{$columnWidth}s | %s\n", "Function Name", "File Path");
echo str_repeat("-", $columnWidth + 3) . str_repeat("-", 50) . "\n";
foreach ($netFunctionFileMap as $function => $file) {
    printf("%-{$columnWidth}s | %s\n", $function, $file);
}

exit(1);