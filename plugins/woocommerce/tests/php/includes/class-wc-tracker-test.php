<?php
/**
 * Unit tests for the WC_Tracker class.
 *
 * @package WooCommerce\Tests\WC_Tracker.
 */

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Class WC_Tracker_Test
 */
class WC_Tracker_Test extends \WC_Unit_Test_Case {
	// phpcs:enable

	/**
	 * Test the tracking of wc_admin being disabled via filter.
	 */
	public function test_wc_admin_disabled_get_tracking_data() {
		$posted_data = null;

		// Test the case for woocommerce_admin_disabled filter returning true.
		add_filter(
			'woocommerce_admin_disabled',
			function( $default ) {
				return true;
			}
		);

		add_filter(
			'pre_http_request',
			function( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return true;
			},
			3,
			10
		);
		WC_Tracker::send_tracking_data( true );
		$tracking_data = json_decode( $posted_data['body'], true );

		// Test the default case of no filter for set for woocommerce_admin_disabled.
		$this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'yes', $tracking_data['wc_admin_disabled'] );
	}

	/**
	 * Test the tracking of wc_admin being not disabled via filter.
	 */
	public function test_wc_admin_not_disabled_get_tracking_data() {
		$posted_data = null;
		// Bypass time delay so we can invoke send_tracking_data again.
		update_option( 'woocommerce_tracker_last_send', strtotime( '-2 weeks' ) );

		add_filter(
			'pre_http_request',
			function( $pre, $args, $url ) use ( &$posted_data ) {
				$posted_data = $args;
				return true;
			},
			3,
			10
		);
		WC_Tracker::send_tracking_data( true );
		$tracking_data = json_decode( $posted_data['body'], true );

		// Test the default case of no filter for set for woocommerce_admin_disabled.
		$this->assertArrayHasKey( 'wc_admin_disabled', $tracking_data );
		$this->assertEquals( 'no', $tracking_data['wc_admin_disabled'] );
	}

	/**
	 * @testDox Test orders tracking data.
	 */
	public function test_get_tracking_data_orders() {
		$dummy_product          = WC_Helper_Product::create_simple_product();
		$status_entries         = array( 'wc-processing', 'wc-completed', 'wc-refunded', 'wc-pending' );
		$created_via_entries    = array( 'api', 'checkout', 'admin' );
		$payment_method_entries = array( 'paypal', 'stripe', 'cod' );

		$order_count = count( $status_entries ) * count( $created_via_entries ) * count( $payment_method_entries );

		foreach ( $status_entries as $status_entry ) {
			foreach ( $created_via_entries as $created_via_entry ) {
				foreach ( $payment_method_entries as $payment_method_entry ) {
					$order = wc_create_order(
						array(
							'status'         => $status_entry,
							'created_via'    => $created_via_entry,
							'payment_method' => $payment_method_entry,
						)
					);
					$order->add_product( $dummy_product );
					$order->save();
					$order->calculate_totals();
				}
			}
		}

		$order_data = WC_Tracker::get_tracking_data()['orders'];

		foreach ( $status_entries as $status_entry ) {
			$this->assertEquals( $order_count / count( $status_entries ), $order_data[ $status_entry ] );
		}

		// Gross revenue is for wc-completed and wc-refunded status, so we calculate expected revenue per status, multiply by 2, and then multiply by 10 to account for the 10 USD per status.
		$this->assertEquals( ( $order_count / count( $status_entries ) ) * 2 * 10, $order_data['gross'] );

		// Gross revenue is for wc-pending status, so we calculate expected revenue per status, multiply by 1, and then multiply by 10 to account for the 10 USD per status.
		$this->assertEquals( ( $order_count / count( $status_entries ) ) * 1 * 10, $order_data['processing_gross'] );

		// Order count per gateway is calculated for three status (completed, processing and refunded) so we multiply order count by 3 and then divide by the number of status entries.
		$this->assertEquals( ( $order_count * 3 / count( $status_entries ) ), $order_data['gateway__USD_count'] );

		// Order revenue per gateway is calculated for three status (completed, processing and refunded) so we multiply order count by 3, then by 10 to account for 10 USD per order and then divide by the number of status entries.
		$this->assertEquals( ( $order_count * 3 * 10 / count( $status_entries ) ), $order_data['gateway__USD_total'] );

		foreach ( $created_via_entries as $created_via_entry ) {
			$this->assertEquals( ( $order_count / count( $created_via_entries ) ), $order_data['created_via'][ $created_via_entry ] );
		}
	}

	/**
	 * @testDox Test enabled features tracking data.
	 */
	public function test_get_tracking_data_enabled_features() {
		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertIsArray( $tracking_data['enabled_features'] );
	}
}
