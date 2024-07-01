<?php

namespace Automattic\WooCommerce\Tests\Internal\Integration;

use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Closure;
use WP_UnitTestCase;

/**
 * Tests for WPConsentAPI.
 *
 * @since 8.5.0
 */
class WPConsentAPITest extends WP_UnitTestCase {

	/**
	 * @var WPConsentAPI
	 */
	private WPConsentAPI $wp_consent_api_integration;

	/**
	 * @var string
	 */
	private string $plugin;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->wp_consent_api_integration = $this->getMockBuilder( WPConsentAPI::class )
												->onlyMethods( array( 'is_wp_consent_api_active' ) )
												->getMock();
		$this->plugin                     = plugin_basename( WC_PLUGIN_FILE );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void {
		parent::tearDown();
		unset( $this->wp_consent_api_integration );
		unset( $this->plugin );
	}

	/**
	 * Get a closure for the on_init method of the WPConsentAPI class.
	 *
	 * @return Closure
	 */
	private function get_closure_for_on_init_method() {
		return Closure::bind(
			function() {
				$this->on_init();
			},
			$this->wp_consent_api_integration,
			$this->wp_consent_api_integration
		);
	}

	/**
	 * Tests no filter set if WPConsentAPI is not active.
	 */
	public function test_wp_consent_api_not_available(): void {
		$this->wp_consent_api_integration->method( 'is_wp_consent_api_active' )->willReturn( false );
		$this->get_closure_for_on_init_method()();
		$this->assertFalse( has_filter( "wp_consent_api_registered_{$this->plugin}" ) );
	}

	/**
	 * Tests filter set if WPConsentAPI is active.
	 */
	public function test_wp_consent_api_available(): void {
		$this->wp_consent_api_integration->method( 'is_wp_consent_api_active' )->willReturn( true );
		$this->get_closure_for_on_init_method()();
		$this->assertTrue( has_filter( "wp_consent_api_registered_{$this->plugin}" ) );
	}
}
