<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\LlmsTxt;

use Automattic\WooCommerce\Internal\LlmsTxt\LlmsTxt;

/**
 * Tests for the llms.txt readiness check.
 */
class LlmsTxtTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox Reports unknown and provides guidance when no physical file is present, or disabled when the feature is off.
	 */
	public function test_readiness_without_a_physical_file(): void {
		$file = $this->site_root_file();
		if ( file_exists( $file ) ) {
			$this->markTestSkipped( 'The test site already has an llms.txt file.' );
		}

		$sut    = wc_get_container()->get( LlmsTxt::class );
		$result = $sut->get_status();
		$this->assertSame( 'unknown', $result['status'] );
		$this->assertStringContainsString( 'WooCommerce Store API', $result['section'] );

		add_filter( 'woocommerce_enable_llms_txt', '__return_false' );
		$this->assertSame(
			array(
				'status'  => 'disabled',
				'section' => '',
			),
			$sut->get_status()
		);
	}

	/**
	 * @testdox Detects a physical file without changing its content.
	 */
	public function test_readiness_with_a_physical_file(): void {
		$file   = $this->site_root_file();
		$handle = @fopen( $file, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.PHP.NoSilencedErrors.Discouraged -- Never overwrite an existing site file.
		if ( false === $handle ) {
			$this->markTestSkipped( 'The test site root is not writable or already has an llms.txt file.' );
		}

		try {
			fwrite( $handle, 'Existing provider' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			clearstatcache( true, $file );
			$result = wc_get_container()->get( LlmsTxt::class )->get_status();
			$this->assertSame( 'physical_file', $result['status'] );
			$this->assertSame( 'Existing provider', file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			add_filter(
				'site_url',
				static function ( $url ) {
					return trailingslashit( $url ) . 'wp';
				}
			);
			$this->assertSame( 'unknown', wc_get_container()->get( LlmsTxt::class )->get_status()['status'] );
		} finally {
			unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only the file created by this test.
		}
	}

	/**
	 * Get the physical path checked by the readiness method.
	 *
	 * @return string
	 */
	private function site_root_file(): string {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		return trailingslashit( get_home_path() ) . 'llms.txt';
	}
}
