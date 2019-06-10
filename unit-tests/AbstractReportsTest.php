<?php
/**
 * Abstract for reports tests - these tests can only run if WC Admin exists.
 */

namespace WooCommerce\RestApi\UnitTests;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\UnitTests\Bootstrap;
use \WC_REST_Unit_Test_Case;
use \WP_REST_Request;
use \WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use \WooCommerce\RestApi\UnitTests\Helpers\QueueHelper;
use \WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;

/**
 * Class AbstractReportsTest.
 */
abstract class AbstractReportsTest extends WC_REST_Unit_Test_Case {

	/**
	 * Setup test reports categories data.
	 */
	public function setUp() {
		if ( Bootstrap::skip_report_tests() ) {
			$this->markTestSkipped( 'Skipping reports tests - woocommerce-admin not found.' );
			return;
		}

		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		wp_set_current_user( $this->user );

		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Orders_Stats_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Products_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Coupons_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Customers_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
	}

}
