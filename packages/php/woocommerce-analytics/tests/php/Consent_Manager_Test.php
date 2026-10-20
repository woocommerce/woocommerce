<?php
/**
 * Tests for Consent_Manager when a consent plugin sets the consent type only in the browser.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Tests that server-side tracking respects a shopper's statistics choice when PHP sees no consent type.
 */
class Consent_Manager_Test extends BaseTestCase {

	/**
	 * Remove the consent cookies so each test starts without a choice.
	 */
	public function tear_down(): void {
		unset( $_COOKIE['wp_consent_statistics'], $_COOKIE['custom_statistics'], $_COOKIE['tk_ai'] );
		parent::tear_down();
	}

	/**
	 * Test that a shopper who denied statistics has no consent, even though PHP sees no consent type.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_denies_consent_when_the_shopper_denied_statistics_without_a_consent_type(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-cookie-functions.php';
		$_COOKIE['wp_consent_statistics'] = 'deny';

		$this->assertFalse( Consent_Manager::has_analytics_consent() );
	}

	/**
	 * Test that a shopper who has not chosen still has consent without a consent type, as the WP Consent API grants.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_grants_consent_without_a_consent_type_when_the_shopper_has_not_chosen(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-cookie-functions.php';

		$this->assertTrue( Consent_Manager::has_analytics_consent() );
	}

	/**
	 * Test that the denied choice is read from the consent cookie under a filtered prefix.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_denies_consent_under_a_filtered_consent_cookie_prefix(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-cookie-functions.php';
		add_filter( 'wp_consent_cookie_prefix', fn() => 'custom' );
		$_COOKIE['custom_statistics'] = 'deny';

		$this->assertFalse( Consent_Manager::has_analytics_consent() );
	}

	/**
	 * Test that with a consent type set, the decision stays with wp_has_consent() and its filter.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_leaves_the_decision_to_wp_has_consent_when_a_consent_type_is_set(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-cookie-functions.php';
		add_filter( 'wp_get_consent_type', fn() => 'optin' );
		add_filter( 'wp_has_consent', '__return_true' );
		$_COOKIE['wp_consent_statistics'] = 'deny';

		$this->assertTrue( Consent_Manager::has_analytics_consent() );
	}

	/**
	 * Test that a server-side event is recorded for a shopper with a visitor id who has not chosen.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_record_event_records_a_shopper_who_has_not_chosen(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-cookie-functions.php';
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';

		WC_Analytics_Tracking::record_event( 'add_to_cart' );

		$this->assertCount( 1, $this->get_pixel_batch_queue() );
	}

	/**
	 * Test that a server-side event is skipped for a shopper who withdrew statistics consent but kept their `tk_ai` cookie.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_record_event_skips_a_shopper_who_denied_statistics(): void {
		require_once __DIR__ . '/mocks/wp-consent-api-cookie-functions.php';
		$_COOKIE['tk_ai']                 = 'test-visitor-id-1234567890ab';
		$_COOKIE['wp_consent_statistics'] = 'deny';

		WC_Analytics_Tracking::record_event( 'add_to_cart' );

		$this->assertSame( array(), $this->get_pixel_batch_queue() );
	}

	/**
	 * Read the pixels queued for shutdown delivery.
	 *
	 * @return array
	 */
	private function get_pixel_batch_queue(): array {
		$property = ( new \ReflectionClass( WC_Analytics_Tracking::class ) )->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		return $property->getValue();
	}
}
