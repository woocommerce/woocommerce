<?php

/*
 * CLI script that takes a path as first parameter
 * The path must exist and must be a directory
 * Opens a non-recursive directory iterator on it
 * For each directory, opens a recursive directory iterator on that directory
 * Allow to set a $ignored_directories array, that if the basename is present, skip it
 * For the main directory and for each recursive directory, iterate on the PHP files
 * Open the PHP file and loop through the lines
 * If the line contains "WPCS:", capture everything starting from "WPCS:" until the end of the line
 * Explode the captured string by the comma
 * Iterate on the exploded array, looking for replacements, like "XSS ok." to "WordPress.Security.EscapeOutput.OutputNotEscaped"
 * Print the list of the would-be changes.
 */

// Ensure a path is provided as the first argument
if ( $argc < 2 ) {
	echo "Usage: php script.php <path_to_directory>\n";
	exit( 1 );
}

$use_copy = false;

$path = $argv[1];

// Check if the path exists and is a directory
if ( ! is_dir( $path ) ) {
	echo "The specified path must exist and be a directory.\n";
	exit( 1 );
}

// Copy directory to tmp using exec or similar.
$tempnam = sys_get_temp_dir() . '/woocommerce';
echo "Removing directory: " . $tempnam . "\n";
passthru( "rm -rf $tempnam" );
echo "Creating directory: " . $tempnam . "\n";
mkdir( $tempnam, 0755, true );

// Array of directories to ignore
$ignored_directories = [ 'node_modules', 'vendor', 'bin', 'build' ];

// Build exclude parameters for rsync from the $ignored_directories array
$exclude_params = '';
foreach ( $ignored_directories as $dir ) {
	$exclude_params .= " --exclude='$dir'";
}

// Use rsync with exclude parameters
echo "Copying directory to temp directory (excluding: " . implode( ', ', $ignored_directories ) . ")\n";
passthru( "rsync -av $exclude_params $path/ $tempnam/", $return_var );

@unlink( $tempnam . '/woocommerce.zip' );

if ( $return_var !== 0 ) {
	echo "Failed to copy directory to temp directory.\n";
	exit( 1 );
}

$GLOBALS['okay'] = [];

// Create a non-recursive directory iterator for the main directory
$dir_iterator = new DirectoryIterator( $use_copy ? $tempnam : $path );

$GLOBALS['wpcs_stats'] = [
	'wpcs_comments_found'    => 0,
	'wpcs_comments_modified' => 0,
];

register_shutdown_function( static function () {
	echo "WPCS comments found: " . $GLOBALS['wpcs_stats']['wpcs_comments_found'] . "\n";
	echo "WPCS comments modified: " . $GLOBALS['wpcs_stats']['wpcs_comments_modified'] . "\n";
} );

foreach ( $dir_iterator as $fileinfo ) {
	if ( $fileinfo->isDir() && ! $fileinfo->isDot() ) {
		$basename = $fileinfo->getBasename();

		// Skip ignored directories
		if ( in_array( $basename, $ignored_directories ) ) {
			continue;
		}

		// Create a recursive iterator for each subdirectory
		$recursiveIterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $fileinfo->getPathname() ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $recursiveIterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				validateAndProcessFile( $file->getPathname() );
			}
		}
	}
}

function validateAndProcessFile( $filename ) {
	$contents         = file_get_contents( $filename );
	$originalContents = $contents;
	$updatedContents  = processPhpContents( $contents, $filename );

	if ( $originalContents !== $updatedContents ) {
		$tempFileName = tempnam( sys_get_temp_dir(), 'php_syntax_check' );
		file_put_contents( $tempFileName, $updatedContents );
		if ( ! checkSyntax( $tempFileName ) ) {
			unlink( $tempFileName );
			throw new Exception( "Syntax error would occur after processing $filename" );
		}
		unlink( $tempFileName );

		echo "Changes in $filename:\n";
		printChanges( $originalContents, $updatedContents );
		file_put_contents( $filename, $updatedContents );
	}
}

register_shutdown_function( static function () {
	print_r( $GLOBALS['okay'] );
} );

function processPhpContents( $contents, $filename ) {
	// Fix a typo.
	$contents = str_replace( 'WordPress.XSS.EscapeOutput.OutputNotEscaped', 'WordPress.Security.EscapeOutput.OutputNotEscaped', $contents );

	$lines           = explode( "\n", $contents );
	$newLines        = [];

	$tracked_by_qit = [
		'input var ok, sanitization ok' => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
		'XSS ok'                        => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
		'input var okay'                => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
		'input var ok'                  => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
		'Input var ok'                  => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
		'csrf ok'                       => '', // Will be dynamically filled
		'CSRF ok'                       => '', // Will be dynamically filled
		'unprepared SQL ok'             => 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared',
		'Sanitization ok'               => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
		'sanitization ok'               => 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized',
	];

	$not_tracked_by_qit = [
		'cache ok'      => 'WordPress.DB.DirectDatabaseQuery.NoCaching',
		'DB call ok'    => 'WordPress.DB.DirectDatabaseQuery.DirectQuery',
		'db call ok'    => 'WordPress.DB.DirectDatabaseQuery.DirectQuery',
		'slow query ok' => '', // Will be dynamically filled based on context
		'override ok'   => '', // Will be dynamically filled based on context
	];

	for ( $i = 0; $i < count( $lines ); $i++ ) {
		$line = $lines[$i];
		$originalLine = $line; // Capture the original line for comparison later
		if ( strpos( $line, 'WPCS:' ) !== false ) {

			echo "$line\n";

			$GLOBALS['wpcs_stats']['wpcs_comments_found'] ++;
			$tracked_changes     = false;
			$not_tracked_changes = false;

			// Handle combined DB call and cache comments
			if ( ( strpos( $line, 'db call ok' ) !== false || strpos( $line, 'DB call ok' ) !== false ) 
				&& strpos( $line, 'cache ok' ) !== false ) {
				
				// This is a special case with both DB call and cache
				$rules = [
					'WordPress.DB.DirectDatabaseQuery.DirectQuery',
					'WordPress.DB.DirectDatabaseQuery.NoCaching'
				];
				$replacement = implode( ', ', $rules );
				
				// Extract the entire WPCS comment and replace it
				if ( preg_match( '/\/\/\s*WPCS:.*$/', $line, $matches ) ) {
					$wpcs_comment = $matches[0];
					$phpcs_comment = str_replace( 'WPCS:', 'phpcs:ignore ' . $replacement, $wpcs_comment );
					$line = str_replace( $wpcs_comment, $phpcs_comment, $line );
					$not_tracked_changes = true;
				}
			}
			// Check for context-specific replacements
			elseif ( strpos( $line, 'slow query ok' ) !== false ) {
				// Detect the context for slow query
				$new_rule = detectSlowQueryContext( $line, $i > 0 ? $lines[$i - 1] : '' );
				$not_tracked_by_qit['slow query ok'] = $new_rule;
				$not_tracked_changes = processReplacement( $line, 'slow query ok', $new_rule );
			} elseif ( strpos( $line, 'override ok' ) !== false ) {
				// Detect the context for override
				$new_rule = detectOverrideContext( $line );
				$not_tracked_by_qit['override ok'] = $new_rule;
				$not_tracked_changes = processReplacement( $line, 'override ok', $new_rule );
			} else {
				// Process tracked by QIT patterns
				foreach ( $tracked_by_qit as $old => $new ) {
					if ( processReplacement( $line, $old, $new ) ) {
						$tracked_changes = true;
					}
				}

				// Process not tracked by QIT patterns only if there's no context-specific replacement
				if ( $tracked_changes ) {
					foreach ( $not_tracked_by_qit as $old => $new ) {
						if ( $old !== 'slow query ok' && $old !== 'override ok' ) { // Skip the ones we handle specifically
							processReplacement( $line, $old, $new ); // We don't need to check return as changes here are dependent on tracked changes
						}
					}
				}
			}

			// Check for exception condition
			if ( !$tracked_changes && !$not_tracked_changes && strpos( $line, 'WPCS:' ) !== false ) {
				// Check if there's any unknown rule that isn't in any of the defined categories
				$unrecognized = true;
				foreach ( array_merge( $tracked_by_qit, $not_tracked_by_qit ) as $old => $new ) {
					if ( strpos( $line, $old ) !== false ) {
						$unrecognized = false;
						break;
					}
				}
				if ( $unrecognized ) {
					echo "WARNING: No applicable changes could be made, and an unrecognized rule was found: $filename on line: $line\n";
					// Don't throw exception, just log and continue
				}
			} else if ( strpos( $line, 'WPCS:' ) !== false ) { // If we still have WPCS: in the line
				$line = str_replace( 'WPCS:', 'phpcs:ignore', $line ); // Replace the comment prefix

				// Format the phpcs:ignore comment properly
				$line = formatPhpcsIgnoreComment( $line );
			}
		}
		$newLines[] = $line;
		if ( $originalLine !== $line ) {
			$GLOBALS['wpcs_stats']['wpcs_comments_modified'] ++;
			echo "Original: $originalLine\n";
			echo "Updated: $line\n";
		}
	}

	return implode( "\n", $newLines );
}

/**
 * Detect the appropriate rule for slow query comments based on context
 *
 * @param string $line Current line with the WPCS comment
 * @param string $prevLine Previous line for additional context
 * @return string The appropriate phpcs rule
 */
function detectSlowQueryContext( $line, $prevLine ) {
	// Check for meta_query
	if ( strpos( $line, 'meta_query' ) !== false || strpos( $prevLine, 'meta_query' ) !== false ) {
		return 'WordPress.DB.SlowDBQuery.meta_query';
	} 
	// Check for tax_query
	elseif ( strpos( $line, 'tax_query' ) !== false || strpos( $prevLine, 'tax_query' ) !== false ) {
		return 'WordPress.DB.SlowDBQuery.tax_query';
	} 
	// Check for meta_key
	elseif ( strpos( $line, 'meta_key' ) !== false ) {
		return 'WordPress.DB.SlowDBQuery.meta_key';
	} 
	// Check for meta_value
	elseif ( strpos( $line, 'meta_value' ) !== false ) {
		return 'WordPress.DB.SlowDBQuery.meta_value';
	} 
	// Default fallback
	else {
		return 'WordPress.DB.SlowDBQuery.slow_db_query';
	}
}

/**
 * Detect the appropriate rule for override comments based on context
 *
 * @param string $line Current line with the WPCS comment
 * @return string The appropriate phpcs rule
 */
function detectOverrideContext( $line ) {
	// Check for common WordPress globals being overridden
	if ( preg_match( '/\$([a-zA-Z_]+)\s*=/', $line, $matches ) ) {
		$var = $matches[1];
		switch ( $var ) {
			case 'menu':
				return 'WordPress.WP.GlobalVariablesOverride.Prohibited';
			case 'parent_file':
			case 'submenu_file':
				return 'WordPress.WP.GlobalVariablesOverride.OverrideProhibited';
			case 'post':
				return 'WordPress.WP.GlobalVariablesOverride.DeprecatedWPGlobals';
			default:
				return 'WordPress.WP.GlobalVariablesOverride.Prohibited';
		}
	}
	// Default fallback
	return 'WordPress.WP.GlobalVariablesOverride.Prohibited';
}

function formatPhpcsIgnoreComment( $line ) {
	if ( strpos( $line, 'phpcs:ignore' ) !== false ) {
		// This regex matches the 'phpcs:ignore' followed by any number of WordPress rules
		$pattern = '/(phpcs:ignore)((?:\s+WordPress\.[a-zA-Z0-9\.]+)+)/';

		$line = preg_replace_callback( $pattern, function ( $matches ) {
			// Split the rules by spaces and trim them
			$rules = array_map( 'trim', explode( ' ', trim( $matches[2] ) ) );

			// Join them back with a comma and a space
			return $matches[1] . ' ' . implode( ', ', $rules );
		}, $line );
	}

	return $line;
}

function processReplacement( &$line, $old, &$new ) {
	$right_side_of_line = substr( $line, strpos( $line, 'WPCS:' ) + 5 );
	/* Right side of line should stop if it encounters "?>" */
	if ( strpos( $right_side_of_line, '?>' ) !== false ) {
		$right_side_of_line = substr( $right_side_of_line, 0, strpos( $right_side_of_line, '?>' ) );
	}

	if ( stripos( $right_side_of_line, 'okay' ) !== false ) {
		$GLOBALS['okay'][] = $right_side_of_line;
	}

	$old_pos = strpos( $line, $old );
	if ( $old_pos !== false ) {
		if ( $old === 'csrf ok' || $old === 'CSRF ok' ) {
			$new = strpos( $line, '$_POST' ) !== false ? 'WordPress.Security.NonceVerification.Missing' : 'WordPress.Security.NonceVerification.Recommended';
		}
		if ( substr( $line, $old_pos + strlen( $old ), 1 ) === ',' || substr( $line, $old_pos + strlen( $old ), 1 ) === '.' ) {
			$old .= substr( $line, $old_pos + strlen( $old ), 1 );
		}
		$right_side_of_line_original = $right_side_of_line;
		$right_side_of_line          = str_replace( $old, $new, $right_side_of_line );
		$line                        = str_replace( $right_side_of_line_original, $right_side_of_line, $line );

		return true;
	}

	return false;
}

function printChanges( $original, $updated ) {
	$originalLines = explode( "\n", $original );
	$updatedLines  = explode( "\n", $updated );
	foreach ( $updatedLines as $key => $line ) {
		if ( $line !== $originalLines[ $key ] ) {
			echo "Line " . ( $key + 1 ) . " changed from:\n" . $originalLines[ $key ] . "\nTo:\n" . $line . "\n";
		}
	}
}

function checkSyntax( $filename ) {
	$output = [];
	$result = 0;
	exec( "php -l " . escapeshellarg( $filename ), $output, $result );

	return $result === 0; // Returns true if no syntax errors
}

if ( ! $use_copy ) {
	return;
}

// Zip the directory.
passthru( sprintf( 'cd %s && zip -r woocommerce.zip woocommerce -x "*bin/*" "*build/*" "*vendor/*" "*tests/*"', sys_get_temp_dir() ), $return_var );

if ( $return_var !== 0 ) {
	echo "Failed to build zip.\n";
	exit( 1 );
}

// Run QIT security test against this zip.
passthru( sprintf( 'qit run:security woocommerce --zip="%s"', sys_get_temp_dir() . '/woocommerce.zip' ), $return_var );

unlink( sys_get_temp_dir() . '/woocommerce.zip' );

if ( $return_var !== 0 ) {
	echo "Failed to run QIT security test.\n";
	exit( 1 );
}
