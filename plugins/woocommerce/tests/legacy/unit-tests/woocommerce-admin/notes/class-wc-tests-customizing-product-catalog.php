<?php
/**
 * CustomizingProductCatalog note tests
 *
 * @package WooCommerce\Admin\Tests\Notes
 */

use Automattic\WooCommerce\Internal\Admin\Notes\CustomizingProductCatalog;
use Automattic\WooCommerce\Admin\Notes\Note;


/**
 * Class WC_Admin_Tests_Marketing_Notes
 */
class WC_Admin_Tests_Customizing_Product_Catalog extends WC_Unit_Test_Case {

	/**
	 * @var CustomizingProductCatalog
	 */
	private $instance;

	/**
	 * setUp
	 */
	public function setUp(): void {
		parent::setUp();
		$this->empty_posts();
		$this->instance = new CustomizingProductCatalog();
	}

	/**
	 * Empty wp_posts table
	 */
	private function empty_posts() {
		global $wpdb;
		$wpdb->query( "delete from {$wpdb->prefix}posts" );
	}

	/**
	 * Given 0 products
	 * When get_note() is called
	 * Then it should return null
	 */
	public function test_it_does_not_add_note_if_product_count_is_zero() {
		// Given -- setUp() takes care of it.
		// When.
		$note = $this->instance->get_note();

		// Then.
		$this->assertNull( $note );
	}

	/**
	 * Given a fresh product
	 * When get_note() is called
	 * Then it should return null
	 */
	public function test_it_does_not_add_note_if_product_is_less_than_a_day_old() {
		// Given.
		wp_insert_post(
			array(
				'post_title'   => 'a product',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);

		// When.
		$note = $this->instance->get_note();

		// Then.
		$this->assertNull( $note );
	}

	/**
	 * Given a store that has been active for 14+ days
	 * When get_note() is called
	 * Then it should return null
	 */
	public function test_it_does_not_add_note_if_store_has_been_active_for_14_days_or_more() {
		// Given.
		update_option( 'woocommerce_admin_install_timestamp', time() - ( DAY_IN_SECONDS * 14 ) );
		wp_insert_post(
			array(
				'post_title'   => 'a product',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);

		// When.
		$note = $this->instance->get_note();

		// Then.
		$this->assertNull( $note );
	}

	/**
	 * Given a store that has been active for less than 14 days
	 * and a product that is older than day
	 *
	 * When get_note() is called
	 * Then it should return an instance of Note
	 */
	public function test_it_adds_note() {
		// Given.
		update_option( 'woocommerce_admin_install_timestamp', time() - ( DAY_IN_SECONDS * 13 ) );
		$day_before = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$product    = new \WC_Product();
		$product->set_props(
			array(
				'name' => 'test',
			)
		);
		$product_id = $product->save();
		wp_update_post(
			array(
				'ID'            => $product_id,
				'post_date'     => $day_before,
				'post_date_gmt' => get_gmt_from_date( $day_before ),
			)
		);

		// When.
		$note = $this->instance->get_note();
		// Then.
		$this->assertInstanceOf( Note::class, $note );
	}
}
