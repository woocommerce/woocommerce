<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Admin_Notices class.
 */
class WC_Admin_Notices_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Sets up the test fixture.
	 */
	public function set_up() {
		parent::set_up();

		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-notices.php';

		if ( ! has_action( 'wp_ajax_woocommerce_hide_notice', array( 'WC_Admin_Notices', 'ajax_hide_notice' ) ) ) {
			WC_Admin_Notices::init();
		}

		WC_Admin_Notices::remove_all_notices();
	}

	/**
	 * Tears down the test fixture.
	 */
	public function tear_down() {
		WC_Admin_Notices::remove_all_notices();
		unset( $_POST['wc-hide-notice'], $_POST['_wc_notice_nonce'] );
		parent::tear_down();
	}

	/**
	 * @testdox Should hide the notice and persist the dismissal when dismissed over AJAX.
	 */
	public function test_ajax_hide_notice_dismisses_notice(): void {
		$this->_setRole( 'administrator' );
		WC_Admin_Notices::add_notice( 'test_notice' );

		$hide_action_fired = false;
		$callback          = function () use ( &$hide_action_fired ) {
			$hide_action_fired = true;
		};
		add_action( 'woocommerce_hide_test_notice_notice', $callback );

		$_POST['wc-hide-notice']   = 'test_notice';
		$_POST['_wc_notice_nonce'] = wp_create_nonce( 'woocommerce_hide_notices_nonce' );

		$response = $this->do_ajax( 'woocommerce_hide_notice' );

		remove_action( 'woocommerce_hide_test_notice_notice', $callback );

		$this->assertTrue( $response['success'], 'The AJAX response should indicate success' );
		$this->assertFalse( WC_Admin_Notices::has_notice( 'test_notice' ), 'The notice should no longer be present' );
		$this->assertTrue( WC_Admin_Notices::user_has_dismissed_notice( 'test_notice' ), 'The dismissal should be recorded in user meta' );
		$this->assertTrue( $hide_action_fired, 'The woocommerce_hide_{name}_notice action should have fired' );
	}

	/**
	 * @testdox Should reject the AJAX dismissal when the nonce is invalid.
	 */
	public function test_ajax_hide_notice_rejects_invalid_nonce(): void {
		$this->_setRole( 'administrator' );
		WC_Admin_Notices::add_notice( 'test_notice' );

		$_POST['wc-hide-notice']   = 'test_notice';
		$_POST['_wc_notice_nonce'] = 'invalid-nonce';

		$this->expectException( 'WPAjaxDieStopException' );

		try {
			$this->_handleAjax( 'woocommerce_hide_notice' );
		} finally {
			$this->assertTrue( WC_Admin_Notices::has_notice( 'test_notice' ), 'The notice should still be present' );
		}
	}

	/**
	 * @testdox Should reject the AJAX dismissal when the user lacks the required capability.
	 */
	public function test_ajax_hide_notice_rejects_unauthorized_user(): void {
		$this->_setRole( 'subscriber' );
		WC_Admin_Notices::add_notice( 'test_notice' );

		$_POST['wc-hide-notice']   = 'test_notice';
		$_POST['_wc_notice_nonce'] = wp_create_nonce( 'woocommerce_hide_notices_nonce' );

		$response = $this->do_ajax( 'woocommerce_hide_notice' );

		$this->assertFalse( $response['success'], 'The AJAX response should indicate failure' );
		$this->assertTrue( WC_Admin_Notices::has_notice( 'test_notice' ), 'The notice should still be present' );
		$this->assertFalse( WC_Admin_Notices::user_has_dismissed_notice( 'test_notice' ), 'No dismissal should be recorded in user meta' );
	}

	/**
	 * @testdox Should return an error when no notice name is provided.
	 */
	public function test_ajax_hide_notice_requires_notice_name(): void {
		$this->_setRole( 'administrator' );

		$_POST['_wc_notice_nonce'] = wp_create_nonce( 'woocommerce_hide_notices_nonce' );

		$response = $this->do_ajax( 'woocommerce_hide_notice' );

		$this->assertFalse( $response['success'], 'The AJAX response should indicate failure' );
	}

	/**
	 * Triggers an ajax endpoint and captures the JSON response.
	 *
	 * @param string $ajax_action The action to be triggered.
	 *
	 * @return array|null
	 */
	private function do_ajax( string $ajax_action ) {
		$output_buffering_level = ob_get_level();

		try {
			// Note that _handleAjax makes use of output buffering, which the die
			// handler usually cleans up; the finally block below closes only any
			// buffer it leaves dangling so the buffer level stays balanced.
			$this->_handleAjax( $ajax_action );
		} catch ( Exception $e ) {
			unset( $e );
		} finally {
			while ( ob_get_level() > $output_buffering_level ) {
				ob_end_clean();
			}
		}

		$result               = json_decode( $this->_last_response, true );
		$this->_last_response = false;

		return $result;
	}
}
