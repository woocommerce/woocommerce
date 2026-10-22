<?php
/**
 * Guards that every validation throw carries a code.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Support;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Scans the Core and Integration zones for plain DomainException throws.
 *
 * @coversNothing
 */
class NoPlainDomainExceptionTest extends TestCase {

	/**
	 * @testdox Core and Integration throw no plain DomainException: validation errors carry a code.
	 */
	public function test_core_and_integration_throw_no_plain_domain_exception(): void {
		$offenders = array();
		$src       = dirname( __DIR__, 4 ) . '/src';

		foreach ( array( $src . '/Core', $src . '/Integration' ) as $dir ) {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ) );
			foreach ( $files as $file ) {
				if ( ! $file instanceof SplFileInfo || 'php' !== $file->getExtension() ) {
					continue;
				}

				$contents = (string) file_get_contents( $file->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local source file.
				if ( 1 === preg_match( '/new\s+\\\\?DomainException\s*\(/', $contents ) ) {
					$offenders[] = substr( $file->getPathname(), strlen( $src ) + 1 );
				}
			}
		}

		$this->assertSame( array(), $offenders, 'Throw Core\Support\InvalidInputException (validation) or LogicException (internal invariant) instead.' );
	}
}
