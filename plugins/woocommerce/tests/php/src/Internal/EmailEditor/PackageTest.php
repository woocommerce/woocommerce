<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\Internal\EmailEditor\Package;
use WC_Unit_Test_Case;

/**
 * Tests for the Package class: the block email editor starts only when its feature is on.
 */
class PackageTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Starts the email editor only when the block email editor feature is on.
	 * @testWith ["no", false]
	 *           ["yes", true]
	 *
	 * @param string $option  Feature option value.
	 * @param bool   $started Whether the email editor starts.
	 */
	public function test_starts_only_when_feature_is_on( string $option, bool $started ): void {
		update_option( 'woocommerce_feature_block_email_editor_enabled', $option );
		remove_all_filters( 'woocommerce_email_editor_initialized' );

		Package::init();

		$this->assertSame( $started, has_filter( 'woocommerce_email_editor_initialized' ) );
	}
}
