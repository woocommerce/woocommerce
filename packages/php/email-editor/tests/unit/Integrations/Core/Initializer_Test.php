<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core;

/**
 * Unit test for the core blocks Initializer class.
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
	 * Test it enables email support for an allowed core block.
	 */
	public function testItEnablesEmailSupportForAllowedBlock(): void {
		$initializer = new Initializer();

		$settings = $initializer->update_block_settings( array( 'name' => 'core/paragraph' ) );

		$this->assertTrue( $settings['supports']['email'] );
		$this->assertSame( array( $initializer, 'render_block' ), $settings['render_email_callback'] );
	}
}
