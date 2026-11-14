<?php
/**
 * Integration tests for ColumnFit.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use RuntimeException;
use WP_Error;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ColumnFit;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ColumnFit
 */
class ColumnFitTest extends EngineIntegrationTestCase {

	/**
	 * The plan meta table, whose `meta_key` is VARCHAR(255).
	 *
	 * @var string
	 */
	private $table;

	public function setUp(): void {
		parent::setUp();
		$this->table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLAN_META );
	}

	/**
	 * @testdox A value at the column length, counted in characters, fits.
	 */
	public function test_a_value_at_the_column_length_fits(): void {
		ColumnFit::assert_fits_column( $this->table, 'meta_key', str_repeat( 'é', 255 ) );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @testdox A value longer than the column is refused with the column and its length named.
	 */
	public function test_a_value_longer_than_the_column_is_refused(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'is longer than the column allows (255).' );

		ColumnFit::assert_fits_column( $this->table, 'meta_key', str_repeat( 'k', 256 ) );
	}

	/**
	 * @testdox A value with bytes invalid for the column charset is refused.
	 */
	public function test_a_value_invalid_for_the_charset_is_refused(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'has characters the column cannot store.' );

		ColumnFit::assert_fits_column( $this->table, 'meta_key', "key\xC3\x28" );
	}

	/**
	 * @testdox A column that cannot be checked refuses the value.
	 */
	public function test_a_column_that_cannot_be_checked_is_refused(): void {
		$error = static function () {
			return new WP_Error( 'wpdb_get_table_charset_failure', 'Could not retrieve table charset.' );
		};
		add_filter( 'pre_get_col_charset', $error );

		try {
			ColumnFit::assert_fits_column( $this->table, 'meta_key', 'short' );
			$this->fail( 'Expected the unchecked column to refuse the value.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'could not be checked against the column', $e->getMessage() );
		} finally {
			remove_filter( 'pre_get_col_charset', $error );
		}
	}
}
