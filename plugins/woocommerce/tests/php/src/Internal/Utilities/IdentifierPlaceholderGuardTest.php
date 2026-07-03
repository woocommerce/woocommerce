<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use WC_Unit_Test_Case;

/**
 * Guards against unguarded use of the `%i` SQL identifier placeholder.
 *
 * WordPress 6.2 added `%i` to `wpdb::prepare()` for table/column names, but a
 * `$wpdb` drop-in can run on a supported WordPress version without implementing
 * it (its `has_cap( 'identifier_placeholders' )` returns false). On such a layer
 * `prepare()` escapes `%i` to a literal and shifts the remaining arguments,
 * silently producing malformed queries.
 *
 * Call sites must therefore route trusted identifiers through
 * {@see \Automattic\WooCommerce\Internal\Utilities\DatabaseUtil::get_sql_identifier()}
 * (or the `wc_get_sql_identifier()` wrapper) instead of passing a raw `%i`
 * placeholder to `prepare()`. This test fails if a new raw `%i` placeholder is
 * introduced in a `prepare()` call anywhere under `src/` or `includes/`.
 */
class IdentifierPlaceholderGuardTest extends WC_Unit_Test_Case {

	/**
	 * The single legitimate `prepare( '%i', ... )` lives inside the helper itself.
	 *
	 * @var string
	 */
	private const ALLOWED_RELATIVE_PATH = 'src/Internal/Utilities/DatabaseUtil.php';

	/**
	 * @testdox No unguarded %i identifier placeholder is passed to wpdb::prepare() under src/ or includes/.
	 */
	public function test_no_unguarded_identifier_placeholder_in_prepare_calls(): void {
		$roots = array(
			WC_ABSPATH . 'src',
			WC_ABSPATH . 'includes',
		);

		$allowed   = wp_normalize_path( WC_ABSPATH . self::ALLOWED_RELATIVE_PATH );
		$offenders = array();

		foreach ( $roots as $root ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS )
			);

			foreach ( $iterator as $file ) {
				if ( 'php' !== strtolower( $file->getExtension() ) ) {
					continue;
				}

				$path = wp_normalize_path( $file->getPathname() );
				if ( $path === $allowed ) {
					continue;
				}

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local source file for static analysis.
				$contents = file_get_contents( $file->getPathname() );

				// Flag a `%i` identifier placeholder appearing within a prepare() call.
				// - `[^;]*?` keeps the match inside a single statement (no cross-statement spanning).
				// - `(?<![:%])` excludes STR_TO_DATE minute specifiers (`%H:%i:%s`) and escaped `%%i`.
				// - `(?![a-zA-Z0-9_])` excludes longer tokens such as `%input`.
				if ( preg_match( '/->prepare\(\s*[^;]*?(?<![:%])%i(?![a-zA-Z0-9_])/s', $contents ) ) {
					$offenders[] = $path;
				}
			}
		}

		$this->assertSame(
			array(),
			$offenders,
			"Use wc_get_sql_identifier() (or DatabaseUtil::get_sql_identifier()) instead of a raw %i placeholder in wpdb::prepare() in:\n"
				. implode( "\n", $offenders )
		);
	}
}
