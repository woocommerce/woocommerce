<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\ImportExport\Support;

use Automattic\WooCommerce\Internal\CLI\ImportExport\Support\StdinSpooler;
use WC_Unit_Test_Case;

/**
 * Tests for the StdinSpooler class.
 */
class StdinSpoolerTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should copy every byte and return true when the source stream is fully read.
	 */
	public function test_copy_stream_to_stream_returns_true_for_a_complete_copy(): void {
		$source      = fopen( 'php://memory', 'r+b' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$destination = fopen( 'php://memory', 'r+b' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $source, 'sku,name' . "\n" . '1,Widget' . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $source );

		$result = StdinSpooler::copy_stream_to_stream( $source, $destination );

		rewind( $destination );
		$copied = stream_get_contents( $destination );

		fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $destination ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertTrue( $result, 'A fully readable source stream should copy successfully' );
		$this->assertSame( 'sku,name' . "\n" . '1,Widget' . "\n", $copied, 'Every byte written to the source should reach the destination' );
	}

	/**
	 * @testdox Should return false when the destination stream cannot be written to.
	 */
	public function test_copy_stream_to_stream_returns_false_for_a_broken_destination(): void {
		$source      = fopen( 'php://memory', 'r+b' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$destination = fopen( 'php://memory', 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- opened read-only so the copy cannot write to it.
		fwrite( $source, 'sku,name' . "\n" . '1,Widget' . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $source );

		$result = @StdinSpooler::copy_stream_to_stream( $source, $destination ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- deliberately exercising a destination stream that cannot be written to.

		fclose( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $destination ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertFalse( $result, 'A destination stream that cannot be written to should not be reported as fully copied' );
	}
}
