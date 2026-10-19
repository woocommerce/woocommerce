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

		$tracking = $this->get_script_attributes( $this->print_scripts(), 'woocommerce-analytics-js' );

		$this->assertIsArray( $tracking );
		$this->assertMatchesRegularExpression( '#^https://stats\.wp\.com/s-\d+\.js$#', $tracking['src'] ?? '' );
		$this->assertNotSame( 'text/plain', $tracking['type'] ?? null );
		$this->assertArrayNotHasKey( 'data-wc-analytics-consent', $tracking );
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

		$output    = $this->print_scripts();
		$tracking  = $this->get_script_attributes( $output, 'woocommerce-analytics-js' );
		$dependent = $this->get_script_attributes( $output, 'analytics-dependent-js' );

		$this->assertIsArray( $tracking );
		$this->assertSame( 'text/plain', $tracking['type'] ?? null );
		$this->assertSame( 'statistics', $tracking['data-wc-analytics-consent'] ?? null );
		$this->assertMatchesRegularExpression( '#^https://stats\.wp\.com/s-\d+\.js$#', $tracking['src'] ?? '' );

		$this->assertIsArray( $dependent );
		$this->assertSame( 'https://example.com/dependent.js?ver=1.0', $dependent['src'] ?? null );
		$this->assertNotSame( 'text/plain', $dependent['type'] ?? null );
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

	/**
	 * Read the attributes of the script tag with the given id. WordPress 7.0+ prints them in a different order than 6.9.
	 *
	 * @param string $output Printed markup.
	 * @param string $id     Script tag id.
	 * @return array<string, string|true>|null Null when there is no such tag.
	 */
	private function get_script_attributes( string $output, string $id ): ?array {
		$processor = new \WP_HTML_Tag_Processor( $output );
		while ( $processor->next_tag( 'script' ) ) {
			if ( $processor->get_attribute( 'id' ) !== $id ) {
				continue;
			}

			$attributes = array();
			foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
				$attributes[ $name ] = $processor->get_attribute( $name );
			}
			return $attributes;
		}

		return null;
	}
}
