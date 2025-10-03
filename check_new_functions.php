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

// Execute git diff command to get changes between branches
$gitCommand = "git diff $compareBranch..$prBranch --name-only";
$output = [];
$returnCode = 0;

exec($gitCommand, $output, $returnCode);

if ($returnCode !== 0) {
    echo "Error: Failed to execute git diff command\n";
    echo "Command: $gitCommand\n";
    exit(1);
}

// Filter files to only include those in includes/ or src/ directories
$relevantFiles = array_filter($output, function($file) {
    return strpos($file, 'includes/') === 0 || strpos($file, 'src/') === 0;
});

if (empty($relevantFiles)) {
    echo "No changes found in includes/ or src/ directories.\n";
    exit(0);
}

echo "Files changed in includes/ or src/ directories:\n";
foreach ($relevantFiles as $file) {
    echo "  - $file\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "GIT DIFF OUTPUT:\n";
echo str_repeat("=", 60) . "\n\n";

// Get the full diff for the relevant files
$fileList = implode(' ', array_map('escapeshellarg', $relevantFiles));
$diffCommand = "git diff $compareBranch..$prBranch -- $fileList";

exec($diffCommand, $diffOutput, $diffReturnCode);

if ($diffReturnCode !== 0) {
    echo "Error: Failed to execute git diff command for file contents\n";
    echo "Command: $diffCommand\n";
    exit(1);
}

// Print the diff output
echo implode("\n", $diffOutput) . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "DIFF COMPLETE\n";
echo str_repeat("=", 60) . "\n";