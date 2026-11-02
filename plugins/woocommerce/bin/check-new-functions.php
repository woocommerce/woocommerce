<?php
/**
 * WooCommerce New Functions Checker
 *
 * This script checks for new functions added in the "includes" or "src"
 * directories between two git branches.
 *
 * Usage: php check_new_functions.php <pr_branch> <compare_branch>
 * Example: php check_new_functions.php feature/new-functions trunk
 *
 * @package WooCommerce
 */

// This is a CLI-only script: it shells out to git via exec() and writes plain text to stdout.
// WordPress's web-oriented escaping and system-call sniffs therefore don't apply here.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

// Check if we have the required arguments.
if ( $argc < 3 ) {
	echo "Usage: php check_new_functions.php <pr_branch> <compare_branch>\n";
	echo "Example: php check_new_functions.php feature/new-functions trunk\n";
	exit( 1 );
}

$pr_branch      = $argv[1];
$compare_branch = $argv[2];

// Get the root of the repository.
$get_repo_root_command = 'git rev-parse --show-toplevel';
$output                = array();
$return_code           = 0;

exec( $get_repo_root_command, $output, $return_code );

if ( 0 !== $return_code ) {
	echo "Error: Failed to execute git rev-parse command\n";
	echo "Command: $get_repo_root_command\n";
	exit( 1 );
}
$repo_root = current( $output );

// Execute git diff command to get changes between branches for includes/ and src/ directories only.
// Use the three-dot form so we diff against the merge-base (the point where the PR branch
// diverged from the compare branch) rather than comparing branch tips. This prevents false
// positives when the PR branch is behind the compare branch: a two-dot diff would otherwise
// report functions that the compare branch removed/changed as if the PR had added them.
//
// Every interpolated value is passed through escapeshellarg(). The branch names come from the
// caller (CI passes the fixed values "HEAD" and "origin/trunk"), so this is defense-in-depth
// rather than a fix for a known exposure; escaping the paths also keeps the command correct
// when the repository lives under a path that contains spaces. The two escaped refs are joined
// around the literal "..." so the shell collapses them into a single "<base>...<head>" argument.
$diff_command = 'git diff ' . escapeshellarg( $compare_branch ) . '...' . escapeshellarg( $pr_branch )
	. ' -- ' . escapeshellarg( "$repo_root/plugins/woocommerce/includes/" )
	. ' ' . escapeshellarg( "$repo_root/plugins/woocommerce/src/" );
$output       = array();
$return_code  = 0;

exec( $diff_command, $output, $return_code );

if ( 0 !== $return_code ) {
	echo "Error: Failed to execute git diff command\n";
	echo "Command: $diff_command\n";
	exit( 1 );
}

if ( empty( $output ) ) {
	echo "No changes found in includes/ or src/ directories.\n";
	exit( 0 );
}

// Parse the diff output to find added and deleted functions.
$added_function_file_map = array();
$deleted_functions       = array();

// Files that are allowed to define standalone functions and are therefore not checked.
// WooCommerce database updates must be global functions: they are registered by name in
// WC_Install::$db_updates and invoked dynamically, so they cannot be class methods.
$excluded_files = array(
	'plugins/woocommerce/includes/wc-update-functions.php',
	'plugins/woocommerce/includes/react-admin/wc-admin-update-functions.php',
);

$current_file = '';
foreach ( $output as $line ) {
	// Track current file being processed.
	if ( preg_match( '/^diff --git a\/(.+?) b\/(.+?)$/', $line, $matches ) ) {
		// Use the 'b' (new) file path.
		$current_file = $matches[2];
	} elseif ( preg_match( '/^\+\+\+ b\/(.+?)$/', $line, $matches ) ) {
		// Alternative way to get the file path.
		$current_file = $matches[1];
	}

	// Skip files that are allowed to define standalone functions.
	if ( in_array( $current_file, $excluded_files, true ) ) {
		continue;
	}

	// Look for added functions (lines starting with +).
	// "function" must be the first token on the line (after the diff "+" and any indentation),
	// so this matches standalone functions and methods declared without a visibility modifier,
	// but not properly declared methods (public/private/protected ...) nor comment/docblock lines.
	// Candidates are confirmed to be real PHP declarations (not embedded JavaScript) further below.
	if ( preg_match( '/^\+\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches ) ) {
		$function_name                             = $matches[1];
		$added_function_file_map[ $function_name ] = $current_file;
	}

	// Look for deleted functions (lines starting with -), using the same definition as above.
	if ( preg_match( '/^\-\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $matches ) ) {
		$function_name       = $matches[1];
		$deleted_functions[] = $function_name;
	}
}

// Confirm that each candidate is a real PHP function declaration and not JavaScript that happens to
// live inside a PHP file (for example inside an inline <script> block, or a string passed to
// wp_add_inline_script()). The PHP tokenizer reports such JavaScript as inline HTML or string
// tokens, never as a T_FUNCTION declaration, so it tells the two apart reliably, regardless of
// indentation or how the script is embedded.
$php_functions_by_file = array();
foreach ( array_unique( array_values( $added_function_file_map ) ) as $file ) {
	$file_lines  = array();
	$return_code = 0;
	exec( 'git show ' . escapeshellarg( "$pr_branch:$file" ), $file_lines, $return_code );

	// If the file cannot be read, keep its candidates rather than risk hiding a real function.
	if ( 0 !== $return_code ) {
		continue;
	}

	$function_names = array();
	$tokens         = token_get_all( implode( "\n", $file_lines ) );
	$count          = count( $tokens );
	for ( $i = 0; $i < $count; $i++ ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) {
			continue;
		}
		// The function name is the first meaningful token after "function".
		for ( $j = $i + 1; $j < $count; $j++ ) {
			$token = $tokens[ $j ];
			if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
				continue;
			}
			if ( '&' === $token ) {
				// Return-by-reference functions.
				continue;
			}
			if ( is_array( $token ) && T_STRING === $token[0] ) {
				$function_names[] = $token[1];
			}
			// Name found, or this is an anonymous function.
			break;
		}
	}
	$php_functions_by_file[ $file ] = $function_names;
}

foreach ( $added_function_file_map as $function => $file ) {
	if ( isset( $php_functions_by_file[ $file ] ) && ! in_array( $function, $php_functions_by_file[ $file ], true ) ) {
		unset( $added_function_file_map[ $function ] );
	}
}

// Calculate net added functions (added minus deleted) and clean file paths.
$net_function_file_map = array();
foreach ( $added_function_file_map as $function => $file_path ) {
	// Skip functions that were also deleted (net zero change).
	if ( in_array( $function, $deleted_functions, true ) ) {
		continue;
	}

	// Remove "plugins/woocommerce/" prefix from file path.
	$plugin_path_prefix = 'plugins/woocommerce/';
	if ( strpos( $file_path, $plugin_path_prefix ) === 0 ) {
		$file_path = substr( $file_path, strlen( $plugin_path_prefix ) );
	}
	$net_function_file_map[ $function ] = $file_path;
}

// Check if there are any net added functions.
if ( empty( $net_function_file_map ) ) {
	exit( 0 );
}

// Print error message and formatted table.
echo "The following new functions are added in $pr_branch:\n\n";

// Find the longest function name to determine column width.
$max_function_length = max( array_map( 'strlen', array_keys( $net_function_file_map ) ) );

// Minimum width of 15, plus 2 for padding.
$column_width = max( 15, $max_function_length + 2 );

// Format as table.
printf( "%-{$column_width}s | %s\n", 'Function Name', 'File Path' );
echo str_repeat( '-', $column_width + 3 ) . str_repeat( '-', 50 ) . "\n";
foreach ( $net_function_file_map as $function => $file ) {
	printf( "%-{$column_width}s | %s\n", $function, $file );
}

echo "\nNo new functions are allowed in WooCommerce. All the new code should go into classes in the src directory.\n\n";
echo "If any of these is actually a new class method, add a visibility modifier (public, private or protected) to it.\n";

exit( 1 );
