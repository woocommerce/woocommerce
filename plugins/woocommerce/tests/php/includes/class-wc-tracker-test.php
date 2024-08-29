<?php
/**
 * Unit tests for the WC_Tracker class.
 *
 * @package WooCommerce\Tests\WC_Tracker.
 */

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\PluginUtil;

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
	 * @testDox Test the features compatibility data for plugin tracking data.
	 */
	public function test_get_tracking_data_plugin_feature_compatibility() {
		$legacy_mocks = array(
			'get_plugins' => function() {
				return array(
					'plugin1' => array(
						'Name' => 'Plugin 1',
					),
					'plugin2' => array(
						'Name' => 'Plugin 2',
					),
					'plugin3' => array(
						'Name' => 'Plugin 3',
					),
				);
			},
		);
		$this->register_legacy_proxy_function_mocks( $legacy_mocks );

		update_option( 'active_plugins', array( 'plugin1', 'plugin2' ) );

		$pluginutil_mock = new class() extends PluginUtil {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function is_woocommerce_aware_plugin( $plugin ): bool {
				if ( 'plugin1' === $plugin ) {
					return false;
				}

				return true;
			}
		};

		$featurescontroller_mock = new class() extends FeaturesController {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function get_compatible_features_for_plugin( string $plugin_name, bool $enabled_features_only = false ): array {
				$compat = array();
				switch ( $plugin_name ) {
					case 'plugin2':
						$compat = array(
							'compatible'   => array( 'feature1' ),
							'incompatible' => array( 'feature2' ),
							'uncertain'    => array( 'feature3' ),
						);
						break;
					case 'plugin3':
						$compat = array(
							'compatible'   => array( 'feature2' ),
							'incompatible' => array(),
							'uncertain'    => array( 'feature1', 'feature3' ),
						);
						break;
				}

				return $compat;
			}
		};

		$container = wc_get_container();
		$container->get( PluginUtil::class ); // Ensure that the class is loaded.
		$container->replace( PluginUtil::class, $pluginutil_mock );
		$container->replace( FeaturesController::class, $featurescontroller_mock );

		$tracking_data = WC_Tracker::get_tracking_data();

		$this->assertEquals(
			array(),
			$tracking_data['active_plugins']['plugin1']['feature_compatibility']
		);
		$this->assertEquals(
			array(
				'compatible'   => array( 'feature1' ),
				'incompatible' => array( 'feature2' ),
				'uncertain'    => array( 'feature3' ),
			),
			$tracking_data['active_plugins']['plugin2']['feature_compatibility']
		);
		$this->assertEquals(
			array(
				'compatible' => array( 'feature2' ),
				'uncertain'  => array( 'feature1', 'feature3' ),
			),
			$tracking_data['inactive_plugins']['plugin3']['feature_compatibility']
		);

		$this->reset_container_replacements();
		$container->reset_all_resolved();
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

	/**
	 * @testDox Test store_id is included in tracking data.
	 */
	public function test_get_tracking_data_store_id() {
		update_option( \WC_Install::STORE_ID_OPTION, '12345' );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'store_id', $tracking_data );
		$this->assertEquals( '12345', $tracking_data['store_id'] );
		delete_option( \WC_Install::STORE_ID_OPTION );
	}

	/**
	 * @testDox Test woocommerce_install_admin_timestamp is included in tracking data.
	 */
	public function test_get_tracking_data_admin_install_timestamp() {
		$time = time();
		update_option( 'woocommerce_admin_install_timestamp', $time );
		$tracking_data = WC_Tracker::get_tracking_data();
		$this->assertArrayHasKey( 'admin_install_timestamp', $tracking_data['settings'] );
		$this->assertEquals( $tracking_data['settings']['admin_install_timestamp'], $time );
		delete_option( 'woocommerce_admin_install_timestamp' );
	}
}
