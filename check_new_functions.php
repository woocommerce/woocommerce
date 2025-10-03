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

// Print the diff output
echo implode("\n", $output) . "\n";