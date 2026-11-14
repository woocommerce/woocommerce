<?php
/**
 * Tests for the proxy speed module MU-plugin template.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Tests that the speed module leaves a shopper's stored statistics deny to the REST route.
 */
class Proxy_Speed_Module_Test extends BaseTestCase {

	/**
	 * File error_log() writes to during a test.
	 *
	 * @var string
	 */
	private $error_log_file;

	/**
	 * Previous error_log ini value.
	 *
	 * @var string|false
	 */
	private $previous_error_log;

	/**
	 * Request globals as they were before the test.
	 *
	 * @var array
	 */
	private $previous_server;

	/**
	 * Load the template, whose own init() returns at load because the test request is not a proxy request.
	 */
	public function set_up(): void {
		parent::set_up();
		require_once dirname( __DIR__, 2 ) . '/src/mu-plugin/woocommerce-analytics-proxy-speed-module-template.php';

		$this->previous_server = $_SERVER;
		$this->error_log_file     = tempnam( sys_get_temp_dir(), 'wca-speed-module' );
		$this->previous_error_log = ini_set( 'error_log', $this->error_log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky
	}

	/**
	 * Restore the request globals and error log.
	 */
	public function tear_down(): void {
		unset( $_COOKIE['wp_consent_statistics'], $_COOKIE['custom_statistics'] );
		$_SERVER = $this->previous_server;
		remove_all_filters( 'wp_consent_cookie_prefix' );
		ini_set( 'error_log', (string) $this->previous_error_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		wp_delete_file( $this->error_log_file );
		parent::tear_down();
	}

	/**
	 * Test that a proxy request carrying a stored deny returns before the module loads the package.
	 */
	public function test_falls_through_to_the_rest_route_when_the_shopper_denied_statistics(): void {
		$this->make_proxy_request();
		$_COOKIE['wp_consent_statistics'] = 'deny';

		( new \WooCommerceAnalyticsProxySpeed() )->init();

		$this->assertSame( '', file_get_contents( $this->error_log_file ) );
	}

	/**
	 * Test that a proxy request without a stored deny goes on to load the package.
	 *
	 * The template's autoloader placeholder is never replaced here, so loading it logs an error.
	 *
	 * @dataProvider provide_choices_the_module_still_serves
	 *
	 * @param string|null $choice The stored statistics choice, or null when none is stored.
	 */
	public function test_goes_on_to_load_the_package_without_a_stored_deny( $choice ): void {
		$this->make_proxy_request();
		if ( null !== $choice ) {
			$_COOKIE['wp_consent_statistics'] = $choice;
		}

		( new \WooCommerceAnalyticsProxySpeed() )->init();

		$this->assertStringContainsString( 'Autoloader path placeholder was not replaced', file_get_contents( $this->error_log_file ) );
	}

	/**
	 * Stored choices that do not deny statistics.
	 *
	 * @return array
	 */
	public static function provide_choices_the_module_still_serves(): array {
		return array(
			'no stored choice' => array( null ),
			'stored allow'     => array( 'allow' ),
		);
	}

	/**
	 * Test that the stored deny is read under a consent cookie prefix filtered by an MU-plugin that loads before the module.
	 */
	public function test_falls_through_under_a_prefix_filtered_by_an_earlier_mu_plugin(): void {
		$this->make_proxy_request();
		add_filter( 'wp_consent_cookie_prefix', fn() => 'custom' );
		$_COOKIE['custom_statistics'] = 'deny';

		( new \WooCommerceAnalyticsProxySpeed() )->init();

		$this->assertSame( '', file_get_contents( $this->error_log_file ) );
	}

	/**
	 * Make the current request look like a proxy tracking request.
	 */
	private function make_proxy_request(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = '/wp-json/woocommerce-analytics/v1/track';
	}
}
