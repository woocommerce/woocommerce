<?php
/**
 * Tests for WC_Report_Customer_List.
 *
 * @package WooCommerce\Tests\Reports
 */

declare( strict_types=1 );

/**
 * Tests for WC_Report_Customer_List.
 */
class WC_Report_Customer_List_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Customer reports display the readable name for a historical Nepal state code.
	 */
	public function test_displays_legacy_nepal_state_name(): void {
		include_once WC_ABSPATH . 'includes/admin/reports/class-wc-report-customer-list.php';

		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'billing_country', 'NP' );
		update_user_meta( $user_id, 'billing_state', 'BAG' );

		$sut = new WC_Report_Customer_List();

		$this->assertSame( 'Bagmati, Nepal', $sut->column_default( get_user_by( 'id', $user_id ), 'location' ) );
	}
}
