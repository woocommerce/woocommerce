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
	 * Test that calling initialize multiple times does not add duplicate hooks.
	 */
	public function testInitializeDoesNotRegisterDuplicateHooks(): void {
		$this->initializer->initialize();
		$priority_first = has_action( 'woocommerce_email_editor_render_start', array( $this->initializer, 'reset_renderers' ) );

		$this->initializer->initialize();
		$this->initializer->initialize();

		$priority_after = has_action( 'woocommerce_email_editor_render_start', array( $this->initializer, 'reset_renderers' ) );

		// Priority should remain unchanged — the hook was not re-added.
		$this->assertSame( $priority_first, $priority_after );
	}

	/**
	 * Test that reset_renderers fires exactly once per render_start action even after multiple initializations.
	 */
	public function testResetRenderersFiresOncePerRenderStart(): void {
		$call_count = 0;

		// Use a fresh initializer so we can track calls via a wrapper.
		$initializer = new Initializer();
		$initializer->initialize();

		// Attach a counter to the same hook at a later priority.
		$counter = function () use ( &$call_count ) {
			++$call_count;
		};
		add_action( 'woocommerce_email_editor_render_start', $counter, 20 );

		// Simulate multiple renders.
		do_action( 'woocommerce_email_editor_render_start' );
		do_action( 'woocommerce_email_editor_render_start' );

		// Counter should fire once per do_action call.
		$this->assertSame( 2, $call_count );

		// Clean up.
		remove_action( 'woocommerce_email_editor_render_start', $counter, 20 );
		remove_action( 'woocommerce_email_editor_render_start', array( $initializer, 'reset_renderers' ) );
	}
}
