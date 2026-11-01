<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ScheduledActionsDepartures;
use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;

/**
 * Tests for the authenticated live board refresh.
 */
class ScheduledActionsDeparturesAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * @testdox Only a permitted viewer with a valid nonce can refresh the board.
	 *
	 * @testWith ["administrator", true, true]
	 *           ["administrator", false, false]
	 *           ["shop_manager", true, false]
	 *
	 * @param string $role           Viewer role.
	 * @param bool   $valid_nonce    Whether to send a valid nonce.
	 * @param bool   $expect_success Whether the response should include the board.
	 */
	public function test_refresh_authorization( string $role, bool $valid_nonce, bool $expect_success ): void {
		wc_get_container()->get( ScheduledActionsDepartures::class );
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		as_schedule_single_action( strtotime( '2050-01-01 UTC' ), 'departures_live_fixture', array( 'private-live-argument' ) );
		$original_get     = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve test-only request state.
		$original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve test-only request state.
		$_GET             = array(
			'live'   => '1',
			'status' => 'pending',
			'nonce'  => $valid_nonce ? wp_create_nonce( 'wc-action-departures' ) : 'expired',
		);
		$_REQUEST         = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Build the simulated AJAX request.
		try {
			$this->_handleAjax( 'woocommerce_action_departures' );
		} catch ( WPAjaxDieContinueException $exception ) {
			unset( $exception );
		} finally {
			$_GET     = $original_get;
			$_REQUEST = $original_request;
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertSame( $expect_success, $response['success'] );
		if ( $expect_success ) {
			$this->assertStringContainsString( 'departures_live_fixture', $response['data']['html'] );
			$this->assertStringNotContainsString( 'private-live-argument', $response['data']['html'] );
		} else {
			$this->assertNull( $response['data'] );
		}
	}
}
