<?php
/**
 * Tests for how s.js is loaded around the WP Consent API.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use WorDBless\BaseTestCase;

/**
 * Tests that s.js is left to the client, which checks statistics consent, whenever the WP Consent API is active.
 */
class Tracking_Script_Consent_Test extends BaseTestCase {

	/**
	 * Remove the script so each test starts without it.
	 */
	public function tear_down(): void {
		wp_dequeue_script( 'woocommerce-analytics' );
		wp_deregister_script( 'woocommerce-analytics' );

		parent::tear_down();
	}

	/**
	 * Test that the script URL changes weekly, which is what busts the browser cache.
	 */
	public function test_tracking_script_url_is_the_weekly_s_js(): void {
		$this->assertSame(
			'https://stats.wp.com/s-' . gmdate( 'YW' ) . '.js',
			Woocommerce_Analytics::get_tracking_script_url()
		);
	}

	/**
	 * Test that without the WP Consent API, s.js is enqueued as before and the client does not load it.
	 */
	public function test_tracking_script_is_enqueued_without_the_consent_api(): void {
		Woocommerce_Analytics::enqueue_tracking_script();

		$this->assertTrue( wp_script_is( 'woocommerce-analytics', 'enqueued' ) );
		$this->assertSame( Woocommerce_Analytics::get_tracking_script_url(), wp_scripts()->registered['woocommerce-analytics']->src );
		$this->assertStringContainsString( 'wcAnalytics.trackingScriptUrl = null;', $this->render_analytics_data() );
	}

	/**
	 * Test that with the WP Consent API, PHP enqueues the handle without s.js and hands the URL to the client instead.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_tracking_script_is_left_to_the_client_when_the_consent_api_is_active(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-functions.php';

		Woocommerce_Analytics::enqueue_tracking_script();

		$this->assertTrue( wp_script_is( 'woocommerce-analytics', 'enqueued' ) );
		$this->assertFalse( wp_scripts()->registered['woocommerce-analytics']->src );
		$this->assertStringContainsString(
			'wcAnalytics.trackingScriptUrl = "' . Woocommerce_Analytics::get_tracking_script_url() . '";',
			$this->render_analytics_data()
		);
	}

	/**
	 * Test that with the WP Consent API, scripts that depend on the handle still print.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_dependents_of_the_handle_still_print_when_the_consent_api_is_active(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-functions.php';

		Woocommerce_Analytics::enqueue_tracking_script();
		wp_enqueue_script( 'analytics-dependent', 'https://example.com/dependent.js', array( 'woocommerce-analytics' ), '1.0' );

		ob_start();
		wp_print_scripts();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'https://example.com/dependent.js', $output );
		$this->assertStringNotContainsString( 'stats.wp.com', $output );
	}

	/**
	 * Test that with the WP Consent API, dequeuing the handle still turns s.js off.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_dequeued_handle_keeps_s_js_off_when_the_consent_api_is_active(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-functions.php';

		Woocommerce_Analytics::enqueue_tracking_script();
		wp_dequeue_script( 'woocommerce-analytics' );

		$this->assertStringContainsString( 'wcAnalytics.trackingScriptUrl = null;', $this->render_analytics_data() );
	}

	/**
	 * Render the injected analytics script and return its markup.
	 *
	 * @return string The markup written by `inject_analytics_data()`.
	 */
	private function render_analytics_data(): string {
		$universal = new Universal();

		ob_start();
		$universal->inject_analytics_data();
		return (string) ob_get_clean();
	}
}
