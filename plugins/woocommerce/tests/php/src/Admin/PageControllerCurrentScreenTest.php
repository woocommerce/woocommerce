<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use WC_Unit_Test_Case;

/**
 * Tests for current screen detection in PageController.
 */
class PageControllerCurrentScreenTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should return false when the WordPress screen API is unavailable.
	 */
	public function test_get_current_screen_id_returns_false_when_screen_api_is_unavailable(): void {
		$process = proc_open( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Required to verify behavior before the WordPress admin bootstrap.
			array(
				PHP_BINARY,
				dirname( __DIR__, 3 ) . '/fixtures/page-controller-without-screen-api.php',
				ABSPATH . 'wp-load.php',
			),
			array(
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			),
			$pipes
		);

		if ( ! is_resource( $process ) ) {
			$this->fail( 'Failed to start the isolated WordPress bootstrap process.' );
		}

		$stdout = stream_get_contents( $pipes[1] );
		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[1] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a process pipe, not a filesystem resource.
		fclose( $pipes[2] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing a process pipe, not a filesystem resource.

		$exit_code = proc_close( $process );

		$this->assertSame( 0, $exit_code, $stderr );
		$this->assertSame(
			array(
				'get_current_screen_exists' => false,
				'screen_id'                 => false,
			),
			json_decode( trim( $stdout ), true, 512, JSON_THROW_ON_ERROR ),
			'PageController should use its false fallback before WordPress loads the screen API.'
		);
	}
}
