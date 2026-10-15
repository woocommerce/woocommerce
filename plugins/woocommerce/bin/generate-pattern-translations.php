<?php
/**
 * Generates pattern metadata strings for translation scanners.
 *
 * @package WooCommerce
 */

use Automattic\WooCommerce\Tools\Generate_Pattern_Translations;

require_once __DIR__ . '/composer/wp/vendor/autoload.php';
require_once __DIR__ . '/class-generate-pattern-translations.php';

$generator = new Generate_Pattern_Translations();
$source    = $generator->generate( dirname( __DIR__ ) );

// This build script writes a local generated file without loading WordPress.
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
if ( false === file_put_contents( __DIR__ . '/../i18n/patterns.php', $source ) ) {
	throw new \RuntimeException( 'Could not write pattern translation strings.' );
}
