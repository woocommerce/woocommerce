<?php

namespace Automattic\WooCommerce\Tests\Internal\Integration;

use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Closure;
use WP_UnitTestCase;

class WPConsentAPITest extends WP_UnitTestCase {

	/**
	 * @var WPConsentAPI
	 */
	private WPConsentAPI $WPConsentAPIIntegration;

	/**
	 * @var string
	 */
	private string $plugin;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->WPConsentAPIIntegration = $this->getMockBuilder( WPConsentAPI::class )
			->onlyMethods( [ 'is_wp_consent_api_active' ] )
			->getMock();
		$this->plugin = plugin_basename( WC_PLUGIN_FILE );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void {
		parent::tearDown();
		unset( $this->WPConsentAPIIntegration );
		unset( $this->plugin );
	}

	/**
	 * Get a closure for the on_plugins_loaded method of the WPConsentAPI class.
	 *
	 * @return Closure
	 */
	private function get_closure_for_on_plugins_loaded_method() {
		return Closure::bind(
			function() {
				$this->on_plugins_loaded();
			},
			$this->WPConsentAPIIntegration,
			$this->WPConsentAPIIntegration
		);
	}

	public function test_wp_consent_api_not_available(): void {
		$this->WPConsentAPIIntegration->method( 'is_wp_consent_api_active' )->willReturn( false );
		$this->get_closure_for_on_plugins_loaded_method()();
		$this->assertFalse( has_filter( "wp_consent_api_registered_{$this->plugin}" ) );
	}

	public function test_wp_consent_api_available(): void {
		$this->WPConsentAPIIntegration->method( 'is_wp_consent_api_active' )->willReturn( true );
		$this->get_closure_for_on_plugins_loaded_method()();
		$this->assertTrue( has_filter( "wp_consent_api_registered_{$this->plugin}" ) );
	}
}
