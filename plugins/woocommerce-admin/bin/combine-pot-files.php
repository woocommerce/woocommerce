<?php
/**
 * Command line script for merging two .pot files.
 *
 * @package WooCommerce Admin
 */

/**
 * Get the two file names from the command line.
 */
if ( $argc < 2 ) {
	echo "Usage: php -f {$argv[0]} source-file.pot destination-file.pot\n";
	exit;
}

for ( $index = 1; $index <= 2; $index++ ) {
	if ( ! is_file( $argv[ $index ] ) ) {
		echo "File not found: {$argv[ $index ]}\n";
		exit;
	}
}

/**
 * Check whether an output locale has been requested.
 */
if ( isset( $argv[3] ) && 0 === stripos( $argv[3], 'lang=' ) ) {
	$locale = substr( $argv[3], 5 );
	$target_file = preg_replace( '|\.pot?|', "-{$locale}.po", $argv[2] );
} else {
	$target_file = $argv[2];
}

/**
 * Parse a .pot file into an array.
 *
 * @param string $file_name Pot file name.
 * @return array
 */
function woocommerce_admin_parse_pot( $file_name ) {
	$fh         = fopen( $file_name, 'r' );
	$originals  = array();
	$references = array();
	$messages   = array();
	$have_msgid = false;

	while ( ! feof( $fh ) ) {
		$line = trim( fgets( $fh ) );
		if ( ! $line ) {
			$message               = implode( "\n", $messages );
			$originals[ $message ] = $references;
			$references            = array();
			$messages              = array();
			$have_msgid            = false;
			$message               = '';
			continue;
		}

		if ( 'msgid' == substr( $line, 0, 5 ) ) {
			$have_msgid = true;
		}

		if ( $have_msgid ) {
			$messages[]   = $line;
		} else {
			$references[] = $line;
		}
	}

	fclose( $fh );

	$message               = implode( "\n", $messages );
	$originals[ $message ] = $references;
	return $originals;
}

// Read the translation files.
$originals_1 = woocommerce_admin_parse_pot( $argv[1] );
$originals_2 = woocommerce_admin_parse_pot( $argv[2] );
// Delete the original sources.
unlink( $argv[1] );
unlink( $argv[2] );
// We don't want two .pot headers in the output.
array_shift( $originals_1 );

$fh = fopen( $target_file, 'w' );
foreach ( $originals_2 as $message => $original ) {
	// Use the complete message section to match strings to be translated.
	if ( isset( $originals_1[ $message ] ) ) {
		$original = array_merge( $original, $originals_1[ $message ] );
		unset( $originals_1[ $message ] );
	}

	fwrite( $fh, implode( "\n", $original ) );
	fwrite( $fh, "\n" . $message ."\n\n" );
}

foreach ( $originals_1 as $message => $original ) {
	fwrite( $fh, implode( "\n", $original ) );
	fwrite( $fh, "\n" . $message ."\n\n" );
}

fclose( $fh );

echo "Created {$target_file}\n";
