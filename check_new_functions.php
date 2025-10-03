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

$pr_branch = $argv[1];
$compare_branch = $argv[2];

// Execute git diff command to get changes between branches for includes/ and src/ directories only
$diff_command = "git diff $compare_branch..$pr_branch -- includes/ src/";
$output = [];
$return_code = 0;

exec($diff_command, $output, $return_code);

if ($return_code !== 0) {
    echo "Error: Failed to execute git diff command\n";
    echo "Command: $diff_command\n";
    exit(1);
}

if (empty($output)) {
    echo "No changes found in includes/ or src/ directories.\n";
    exit(0);
}

// Parse the diff output to find added and deleted functions
$added_function_file_map = [];
$deleted_functions = [];

$current_file = '';
foreach ($output as $line) {
    // Track current file being processed
    if (preg_match('/^diff --git a\/(.+?) b\/(.+?)$/', $line, $matches)) {
        $current_file = $matches[2]; // Use the 'b' (new) file path
    } elseif (preg_match('/^\+\+\+ b\/(.+?)$/', $line, $matches)) {
        $current_file = $matches[1]; // Alternative way to get file path
    }
    
    // Look for added functions (lines starting with +)
    if (preg_match('/^\+.*?function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches)) {
        $function_name = $matches[1];
        $added_function_file_map[$function_name] = $current_file;
    }
    
    // Look for deleted functions (lines starting with -)
    if (preg_match('/^\-.*?function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches)) {
        $function_name = $matches[1];
        $deleted_functions[] = $function_name;
    }
}

// Calculate net added functions (added minus deleted) and clean file paths
$net_function_file_map = [];
foreach ($added_function_file_map as $function => $file_path) {
    // Skip functions that were also deleted (net zero change)
    if (in_array($function, $deleted_functions)) {
        continue;
    }
    
    // Remove "plugins/woocommerce/" prefix from file path
    if (strpos($file_path, 'plugins/woocommerce/') === 0) {
        $file_path = substr($file_path, 19); // Remove "plugins/woocommerce/" (19 characters)
    }
    $net_function_file_map[$function] = $file_path;
}

// Check if there are any net added functions
if (empty($net_function_file_map)) {
    exit(0);
}

// Print error message and formatted table
echo "No new functions are allowed in WooCommerce. All the new code should go into classes in the src directory\n\n";

// Find the longest function name to determine column width
$max_function_length = max(array_map('strlen', array_keys($net_function_file_map)));
$column_width = max(15, $max_function_length + 2); // Minimum width of 15, plus 2 for padding

// Format as table
printf("%-{$column_width}s | %s\n", "Function Name", "File Path");
echo str_repeat("-", $column_width + 3) . str_repeat("-", 50) . "\n";
foreach ($net_function_file_map as $function => $file) {
    printf("%-{$column_width}s | %s\n", $function, $file);
}

exit(1);