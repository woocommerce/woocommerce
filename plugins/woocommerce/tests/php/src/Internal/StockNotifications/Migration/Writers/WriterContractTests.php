<?php
/**
 * WriterContractTests class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\NullWriter;
use WC_Unit_Test_Case;

/**
 * Pins what the writers' return booleans mean, so a caller is never written against the
 * assumption that `false` means the value is absent.
 */
class WriterContractTests extends WC_Unit_Test_Case {

	/**
	 * Option name used by the write tests.
	 */
	private const OPTION = 'wc_bis_writer_contract_test';

	/**
	 * Remove the test option.
	 */
	public function tearDown(): void {
		delete_option( self::OPTION );

		parent::tearDown();
	}

	/**
	 * @testdox writing an unchanged option returns false from DbWriter and true from NullWriter.
	 */
	public function test_an_unchanged_option_write_is_reported_differently_by_the_two_writers(): void {
		$writer = wc_get_container()->get( DbWriter::class );

		$this->assertTrue( $writer->write_option( self::OPTION, 'value' ), 'The first write changes the option.' );
		$this->assertFalse(
			$writer->write_option( self::OPTION, 'value' ),
			'update_option() reports false for a value already equal to the one being written.'
		);
		$this->assertTrue( ( new NullWriter() )->write_option( self::OPTION, 'value' ) );
	}

	/**
	 * @testdox a false return must not be read as the value being absent.
	 */
	public function test_a_false_return_does_not_mean_the_value_is_absent(): void {
		$writer = wc_get_container()->get( DbWriter::class );

		$writer->write_option( self::OPTION, 'value' );

		$this->assertFalse( $writer->write_option( self::OPTION, 'value' ) );
		$this->assertSame( 'value', get_option( self::OPTION ), 'The store holds the value the writer reported false for.' );
	}

	/**
	 * @testdox NullWriter reports a successful write without touching the store.
	 */
	public function test_the_null_writer_writes_nothing(): void {
		$writer = new NullWriter();

		$this->assertTrue( $writer->is_dry_run() );
		$this->assertTrue( $writer->write_option( self::OPTION, 'value' ) );
		$this->assertFalse( get_option( self::OPTION ), 'A dry run must leave the store untouched.' );
	}
}
