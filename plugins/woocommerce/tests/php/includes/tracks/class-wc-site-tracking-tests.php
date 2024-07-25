<?php

/**
 * Tests for site tracking.
 */
class WC_Site_Tracking_Tests extends WC_Unit_Test_Case {
	/**
	 * Describes when tracking cookies should and should not be set, in relation to
	 * the 'tracking enabled' setting.
	 *
	 * @return void
	 */
	public function test_set_tracking_cookie(): void {
		$last_cookie_key = '';

		/**
		 * Monitor and record when a new cookie is set. Do not actually allow it to be set,
		 * however, as headers will already have been sent (during test suite bootstrap), and
		 * we don't want the noise.
		 *
		 * @param bool   $should_set If wc_setcookie should set the cookie.
		 * @param string $cookie_key The cookie key being set.
		 *
		 * @return false
		 */
		$watch_wc_cookie = function ( $should_set, $cookie_key ) use ( &$last_cookie_key ) {
			$last_cookie_key = $cookie_key;
			return false;
		};

		add_filter( 'woocommerce_set_cookie_enabled', $watch_wc_cookie, 10, 2 );

		update_option( 'woocommerce_allow_tracking', 'yes' );
		WC_Site_Tracking::set_tracking_cookie( 'foo', 'bar' );
		$this->assertEquals( 'foo', $last_cookie_key, 'When tracking is enabled, a tracking cookie can successfully be set.' );

		update_option( 'woocommerce_allow_tracking', 'no' );
		WC_Site_Tracking::set_tracking_cookie( 'bar', 'baz' );
		$this->assertNotEquals( 'bar', $last_cookie_key, 'When tracking is disabled, a tracking cookie cannot be set.' );

		remove_filter( 'woocommerce_set_cookie_enabled', $watch_wc_cookie );
	}
}
