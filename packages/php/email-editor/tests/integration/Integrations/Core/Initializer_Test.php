<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Integrations\Core;

use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

/**
 * Integration test for Initializer class
 */
class Initializer_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Initializer instance
	 *
	 * @var Initializer
	 */
	private Initializer $initializer;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->initializer = new Initializer();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_email_editor_theme_json', array( $this->initializer, 'adjust_theme_json' ) );
		remove_filter( 'safe_style_css', array( $this->initializer, 'allow_styles' ) );
		remove_action( 'woocommerce_email_editor_render_start', array( $this->initializer, 'reset_renderers' ) );
		parent::tearDown();
	}

	/**
	 * Test that initialize registers hooks.
	 */
	public function testInitializeRegistersHooks(): void {
		$this->initializer->initialize();

		$this->assertNotFalse( has_filter( 'woocommerce_email_editor_theme_json', array( $this->initializer, 'adjust_theme_json' ) ) );
		$this->assertNotFalse( has_filter( 'safe_style_css', array( $this->initializer, 'allow_styles' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_email_editor_render_start', array( $this->initializer, 'reset_renderers' ) ) );
	}

	/**
	 * Test that reset_renderers fires exactly once per render_start action even after multiple initializations.
	 */
	public function testResetRenderersFiresOncePerRenderStart(): void {
		$spy = new Initializer_Spy();
		$spy->initialize();
		$spy->initialize();
		$spy->initialize();

		try {
			// Simulate multiple renders.
			do_action( 'woocommerce_email_editor_render_start' );
			do_action( 'woocommerce_email_editor_render_start' );

			$this->assertSame( 2, $spy->reset_renderers_call_count );
		} finally {
			remove_filter( 'woocommerce_email_editor_theme_json', array( $spy, 'adjust_theme_json' ) );
			remove_filter( 'safe_style_css', array( $spy, 'allow_styles' ) );
			remove_action( 'woocommerce_email_editor_render_start', array( $spy, 'reset_renderers' ) );
		}
	}
}

/**
 * Test spy that counts reset_renderers() calls.
 */
class Initializer_Spy extends Initializer { // phpcs:ignore -- Multiple classes needed for test spy.
	/**
	 * Number of times reset_renderers() was called.
	 *
	 * @var int
	 */
	public int $reset_renderers_call_count = 0;

	/**
	 * Override to count calls.
	 */
	public function reset_renderers(): void {
		++$this->reset_renderers_call_count;
		parent::reset_renderers();
	}
}
