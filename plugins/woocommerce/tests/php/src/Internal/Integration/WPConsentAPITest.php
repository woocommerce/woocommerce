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
		wp_dequeue_script( 'wp-consent-api-integration' );
		wp_deregister_script( 'wp-consent-api-integration' );
		wp_deregister_script( 'wc-order-attribution' );
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

	/**
	 * @testdox Should print allowTracking as false when WP Consent API is active, since cached pages are served to every visitor.
	 */
	public function test_allow_tracking_is_false_when_wp_consent_api_is_active(): void {
		$this->wp_consent_api_integration->method( 'is_wp_consent_api_active' )->willReturn( true );
		$this->get_closure_for_on_init_method()();

		$this->assertFalse(
			apply_filters( 'wc_order_attribution_allow_tracking', true ),
			'The cacheable allowTracking flag must not enable tracking before the browser checks consent'
		);
	}

	/**
	 * @testdox Should print the consent category before order-attribution.js, so it waits for the consent integration on load.
	 */
	public function test_consent_category_is_printed_before_order_attribution_script(): void {
		wp_register_script( 'wc-order-attribution', 'https://example.org/order-attribution.js', array(), '1.0', true );

		Closure::bind(
			function () {
				$this->enqueue_consent_api_scripts();
			},
			$this->wp_consent_api_integration,
			WPConsentAPI::class
		)();

		$this->assertContains(
			'window.wc_order_attribution.params.consentCategory = "marketing";',
			(array) wp_scripts()->get_data( 'wc-order-attribution', 'before' ),
			'The consent category must be set before order-attribution.js runs its page-load init'
		);
	}
}
