<?php
/**
 * Abstract for reports tests - these tests can only run if WC Admin exists.
 */

namespace WooCommerce\RestApi\UnitTests;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\UnitTests\Bootstrap;
use \WC_REST_Unit_Test_Case;

/**
 * Class AbstractReportsTest.
 */
abstract class AbstractReportsTest extends WC_REST_Unit_Test_Case {

	/**
	 * User variable.
	 *
	 * @var WP_User
	 */
	protected static $user;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Setup test reports categories data.
	 */
	public function setUp() {
		if ( ! class_exists( '\WC_Admin_Reports_Sync' ) ) {
			$this->markTestSkipped( 'Skipping reports tests - WC_Admin_Reports_Sync class not found.' );
			return;
		}

		parent::setUp();
		wp_set_current_user( self::$user );

		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \WC_Admin_Reports_Orders_Stats_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \WC_Admin_Reports_Products_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \WC_Admin_Reports_Coupons_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \WC_Admin_Reports_Customers_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
	}

}
