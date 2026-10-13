<?php
/**
 * Tests for how s.js is printed around the WP Consent API.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use WorDBless\BaseTestCase;

/**
 * Tests that s.js prints inert, for the client to run after statistics consent, whenever the WP Consent API is active.
 */
class Tracking_Script_Consent_Test extends BaseTestCase {

	/**
	 * Remove the scripts and the filter so each test starts without them.
	 */
	public function tear_down(): void {
		remove_filter( 'wp_script_attributes', array( Woocommerce_Analytics::class, 'hold_tracking_script_until_consent' ) );
		foreach ( array( 'woocommerce-analytics', 'analytics-dependent' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}

		parent::tear_down();
	}

	/**
	 * Test that without the WP Consent API, s.js prints as a regular script, as before.
	 */
	public function test_tracking_script_runs_without_the_consent_api(): void {
		Woocommerce_Analytics::enqueue_tracking_script();

		$output = $this->print_scripts();

		$this->assertStringContainsString( 'src="https://stats.wp.com/s-' . gmdate( 'YW' ) . '.js"', $output );
		$this->assertStringNotContainsString( 'text/plain', $output );
	}

	/**
	 * Test that with the WP Consent API, s.js prints inert and other scripts are untouched.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_tracking_script_prints_inert_when_the_consent_api_is_active(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-functions.php';

		Woocommerce_Analytics::enqueue_tracking_script();
		wp_enqueue_script( 'analytics-dependent', 'https://example.com/dependent.js', array( 'woocommerce-analytics' ), '1.0' );

		$output = $this->print_scripts();

		$this->assertMatchesRegularExpression(
			'#<script type="text/plain" src="https://stats\.wp\.com/s-\d+\.js" id="woocommerce-analytics-js"[^>]* data-wc-analytics-consent="statistics"#',
			$output
		);
		$this->assertMatchesRegularExpression( '#<script (type="text/javascript" )?src="https://example\.com/dependent\.js\?ver=1\.0" id="analytics-dependent-js"#', $output );
	}

	/**
	 * Print the enqueued scripts and return the markup.
	 *
	 * @return string
	 */
	private function print_scripts(): string {
		ob_start();
		wp_print_scripts();
		return (string) ob_get_clean();
	}
}
