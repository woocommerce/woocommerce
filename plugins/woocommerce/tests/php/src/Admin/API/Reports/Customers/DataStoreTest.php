<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Customers;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use WC_Unit_Test_Case;

/**
 * Tests for the Analytics customers data store.
 */
class DataStoreTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should order customers with the same sort value by customer ID, so pages neither repeat nor skip them.
	 */
	public function test_ties_page_by_customer_id(): void {
		global $wpdb;
		$ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$wpdb->insert(
				CustomersDataStore::get_db_table_name(),
				array(
					'first_name' => 'Tie',
					'email'      => "tie-{$i}@example.test",
				)
			);
			$ids[] = (int) $wpdb->insert_id;
		}

		$pages = array();
		for ( $page = 1; $page <= 3; $page++ ) {
			$data  = ( new CustomersDataStore() )->get_data(
				array(
					'customers'    => $ids,
					'orderby'      => 'date_registered',
					'order'        => 'desc',
					'per_page'     => 1,
					'page'         => $page,
					'order_before' => '',
					'order_after'  => '',
					'fields'       => array( 'id' ),
				)
			);
			$pages = array_merge( $pages, array_map( 'intval', array_column( $data->data, 'id' ) ) );
		}
		$this->assertSame( $ids, $pages, 'None of them has a registration date, so the customer ID decides.' );
	}
}
