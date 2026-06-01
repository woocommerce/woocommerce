<?php

namespace Automattic\WooCommerce\Tests\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\Improvements\SavePublishClarity;
use WP_UnitTestCase;

/**
 * @covers SavePublishClarity
 */
class SavePublishClarityTest extends WP_UnitTestCase {

	public function tear_down(): void {
		remove_action( 'admin_head', array( SavePublishClarity::class, 'output_styles' ) );
		remove_action( 'admin_footer', array( SavePublishClarity::class, 'output_header_html' ) );
		parent::tear_down();
	}

	public function test_init_does_not_register_hooks_when_flag_is_disabled(): void {
		// No cookie set — flag is off by default.
		SavePublishClarity::init();

		$this->assertFalse(
			has_action( 'admin_head', array( SavePublishClarity::class, 'output_styles' ) ),
			'admin_head hook should not be registered when flag is off'
		);
		$this->assertFalse(
			has_action( 'admin_footer', array( SavePublishClarity::class, 'output_header_html' ) ),
			'admin_footer hook should not be registered when flag is off'
		);
	}

	public function test_init_registers_hooks_when_flag_is_enabled(): void {
		$_COOKIE['wc_prototype_flags'] = wp_json_encode( array( 'save_publish_clarity' => true ) );

		SavePublishClarity::init();

		$this->assertNotFalse(
			has_action( 'admin_head', array( SavePublishClarity::class, 'output_styles' ) ),
			'admin_head hook should be registered when flag is on'
		);
		$this->assertNotFalse(
			has_action( 'admin_footer', array( SavePublishClarity::class, 'output_header_html' ) ),
			'admin_footer hook should be registered when flag is on'
		);

		unset( $_COOKIE['wc_prototype_flags'] );
	}
}
