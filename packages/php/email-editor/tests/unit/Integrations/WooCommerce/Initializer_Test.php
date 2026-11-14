<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce;

/**
 * Unit test for the WooCommerce blocks Initializer class.
 */
class Initializer_Test extends \Email_Editor_Unit_Test {
	/**
	 * Test it returns block settings without a block name unchanged.
	 */
	public function testItReturnsSettingsWithoutNameUnchanged(): void {
		$settings = array( 'title' => 'No name' );

		$this->assertSame( $settings, ( new Initializer() )->update_block_settings( $settings ) );
	}

	/**
	 * Test it enables email support and full alignment for the product image block.
	 */
	public function testItEnablesEmailSupportForProductImageBlock(): void {
		$initializer = new Initializer();

		$settings = $initializer->update_block_settings( array( 'name' => 'woocommerce/product-image' ) );

		$this->assertTrue( $settings['supports']['email'] );
		$this->assertSame( array( 'full' ), $settings['supports']['align'] );
		$this->assertSame( array( $initializer, 'render_block' ), $settings['render_email_callback'] );
	}
}
