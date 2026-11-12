<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrdersQuery;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the orders-query ability.
 */
class OrdersQueryTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * The order storage that was active before the test.
	 *
	 * @var bool|null
	 */
	private ?bool $original_hpos = null;

	/**
	 * Create the HPOS tables outside the test transaction, since DDL commits implicitly.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		unset( $factory );
		$previous_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Restore the order storage a test switched.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->original_hpos ) {
				$this->toggle_cot_feature_and_usage( $this->original_hpos );
				$this->original_hpos = null;
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
			parent::tearDown();
		}
	}

	/**
	 * Storage modes.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public function storage_modes(): array {
		return array(
			'posts storage' => array( false ),
			'HPOS storage'  => array( true ),
		);
	}

	/**
	 * @testdox Should return exactly the included orders of the requested type and drop excluded IDs.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_include_returns_only_listed_orders( bool $hpos ): void {
		$this->use_storage( $hpos );
		$first  = OrderHelper::create_order();
		$second = OrderHelper::create_order();
		$third  = OrderHelper::create_order();
		OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $first->get_id(),
				'amount'   => 1,
			)
		);

		$included = OrdersQuery::execute( array( 'include' => array( $first->get_id(), $second->get_id(), $third->get_id(), $refund->get_id() ) ) );
		$excluded = OrdersQuery::execute(
			array(
				'include' => array( $first->get_id(), $second->get_id() ),
				'exclude' => array( $second->get_id() ),
			)
		);
		$all_out  = OrdersQuery::execute(
			array(
				'include' => array( $first->get_id() ),
				'exclude' => array( $first->get_id() ),
			)
		);

		$this->assertEqualsCanonicalizing( array( $first->get_id(), $second->get_id(), $third->get_id() ), array_column( $included['orders'], 'id' ), 'A refund ID in include should not return the refund.' );
		$this->assertSame( array( $first->get_id() ), array_column( $excluded['orders'], 'id' ) );
		$this->assertSame( array(), $all_out['orders'], 'Excluding every included ID should return no orders, not all of them.' );
	}

	/**
	 * @testdox Should refuse an include list that is empty or longer than 100 IDs.
	 */
	public function test_refuses_include_outside_size_limits(): void {
		$schema = OrdersQuery::get_registration_args()['input_schema'];

		$this->assertWPError( rest_validate_value_from_schema( array( 'include' => array() ), $schema, 'input' ), 'An empty list should be refused instead of returning every order.' );
		$this->assertWPError( rest_validate_value_from_schema( array( 'include' => range( 1, 101 ) ), $schema, 'input' ) );
		$this->assertTrue( rest_validate_value_from_schema( array( 'include' => range( 1, 100 ) ), $schema, 'input' ) );
	}

	/**
	 * @testdox Should return every included order in one page when per_page is not sent, counting each remaining ID once.
	 */
	public function test_include_without_per_page_returns_one_page(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$order_ids = array();
		foreach ( range( 1, 15 ) as $unused ) {
			$order_ids[] = OrderHelper::create_order()->get_id();
		}

		$all_ids = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'include' => $order_ids ) );
		$paged   = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'include'  => $order_ids,
				'per_page' => 10,
			)
		);

		$deduped = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'include' => array( $order_ids[0], $order_ids[0], $order_ids[1] ),
				'exclude' => array( $order_ids[1] ),
			)
		);
		$all_out = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'include' => array( $order_ids[0] ),
				'exclude' => array( $order_ids[0] ),
			)
		);

		$per_page = OrdersQuery::get_registration_args()['input_schema']['properties']['per_page'];
		$this->assertArrayNotHasKey( 'default', $per_page, 'A client that fills in schema defaults would send 10 and page the included orders.' );
		$this->assertEqualsCanonicalizing( $order_ids, array_column( $all_ids['orders'], 'id' ) );
		$this->assertSame( 1, $all_ids['total_pages'] );
		$this->assertCount( 10, $paged['orders'], 'An explicit per_page should still apply.' );
		$this->assertSame( 1, $deduped['per_page'], 'Duplicate and excluded IDs should not count toward the default per_page.' );
		$this->assertSame( 1, $all_out['per_page'], 'The default per_page should stay at least 1 when every ID is excluded.' );
	}

	/**
	 * @testdox Should refuse an id together with include.
	 */
	public function test_refuses_id_with_include(): void {
		$order = OrderHelper::create_order();

		$result = OrdersQuery::execute(
			array(
				'id'      => $order->get_id(),
				'include' => array( $order->get_id() ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_include_with_id', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * Search terms that each match one detail of the order that create_detailed_order() builds, on both storages.
	 *
	 * @return array<string, array{0: bool, 1: string}>
	 */
	public function search_terms(): array {
		$terms = array(
			'first name'         => 'Jane',
			'last name'          => 'doe',
			'start of the email' => 'jane.doe@',
			'product name'       => 'Enamel',
			'billing address'    => 'Elm Street',
			'phone'              => '0199-44',
			'shipping name'      => 'Robin',
			'shipping line'      => 'Courier',
			'fee name'           => 'Gift wrap',
			'coupon code'        => 'spring10',
			'padded term'        => ' Enamel ',
		);

		$cases = array();
		foreach ( $this->storage_modes() as $storage => list( $hpos ) ) {
			foreach ( $terms as $detail => $term ) {
				$cases[ "$detail, $storage" ] = array( $hpos, $term );
			}
		}
		return $cases;
	}

	/**
	 * @testdox Should find an order by each customer and item detail the search input describes.
	 *
	 * @dataProvider search_terms
	 * @param bool   $hpos   Whether to use the custom orders table.
	 * @param string $search Search text.
	 */
	public function test_search_matches_order_details( bool $hpos, string $search ): void {
		$this->use_storage( $hpos );
		$order = $this->create_detailed_order();
		$this->create_customer_order( 'John', 'Smith', 'john@example.com' );

		$result = OrdersQuery::execute( array( 'search' => $search ) );

		$this->assertSame( array( $order ), array_column( $result['orders'], 'id' ) );
	}

	/**
	 * @testdox Should find an order by its order number, with or without an "Order #" prefix.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_search_matches_order_number( bool $hpos ): void {
		$this->use_storage( $hpos );
		$this->create_customer_order( 'John', 'Smith', 'john@example.com' );
		$order = $this->create_customer_order( 'Jane', 'Doe', 'jane.doe@example.com' );

		$number   = OrdersQuery::execute( array( 'search' => (string) $order ) );
		$prefixed = OrdersQuery::execute( array( 'search' => 'Order #' . $order ) );

		$this->assertContains( $order, array_column( $number['orders'], 'id' ) );
		$this->assertContains( $order, array_column( $prefixed['orders'], 'id' ), 'An "Order #" prefix should match on both storages.' );
	}

	/**
	 * @testdox Should refuse an id together with search.
	 */
	public function test_refuses_id_with_search(): void {
		$order = OrderHelper::create_order();

		$result = OrdersQuery::execute(
			array(
				'id'     => $order->get_id(),
				'search' => 'Jane',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_search_with_id', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return no orders when nothing matches the search.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_search_without_matches_returns_nothing( bool $hpos ): void {
		$this->use_storage( $hpos );
		$this->create_customer_order( 'Jane', 'Doe', 'jane.doe@example.com' );

		$result = OrdersQuery::execute( array( 'search' => 'Nobody' ) );

		$this->assertSame( array(), $result['orders'] );
		$this->assertSame( 0, $result['total_pages'] );
	}

	/**
	 * @testdox Should apply include, customer_id and exclude to the orders a search matches.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_search_combines_with_other_filters( bool $hpos ): void {
		$this->use_storage( $hpos );
		$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		$guest       = $this->create_customer_order( 'Jane', 'Doe', 'jane.doe@example.com' );
		$first       = $this->create_customer_order( 'Jane', 'Doe', 'jane.doe@example.com', null, $customer_id );
		$second      = $this->create_customer_order( 'Jane', 'Doe', 'jane.doe@example.com', null, $customer_id );
		$john        = $this->create_customer_order( 'John', 'Smith', 'john@example.com' );

		$included    = OrdersQuery::execute(
			array(
				'search'  => 'Jane',
				'include' => array( $guest, $john ),
			)
		);
		$by_customer = OrdersQuery::execute(
			array(
				'search'      => 'Jane',
				'customer_id' => $customer_id,
			)
		);
		$excluded    = OrdersQuery::execute(
			array(
				'search'  => 'Jane',
				'exclude' => array( $first ),
			)
		);

		$this->assertSame( array( $guest ), array_column( $included['orders'], 'id' ), 'A search should narrow an include list, not add to it.' );
		$this->assertEqualsCanonicalizing( array( $first, $second ), array_column( $by_customer['orders'], 'id' ) );
		$this->assertEqualsCanonicalizing( array( $guest, $second ), array_column( $excluded['orders'], 'id' ) );
	}

	/**
	 * @testdox Should refuse a search with no text to match, including "0", which the order queries treat as no search.
	 *
	 * @testWith [""]
	 *           ["  "]
	 *           ["0"]
	 *           [" 0 "]
	 *           ["<b></b>"]
	 *           ["Order #"]
	 *
	 * @param string $search Search text.
	 */
	public function test_refuses_search_without_text( string $search ): void {
		$result = OrdersQuery::execute( array( 'search' => $search ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_order_search_empty', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return each order exactly once across pages when orders tie on $orderby $order, with HPOS $hpos.
	 * @testWith [true, "date", "asc"]
	 *           [true, "date", "desc"]
	 *           [true, "date_modified", "asc"]
	 *           [true, "date_modified", "desc"]
	 *           [true, "total", "asc"]
	 *           [true, "total", "desc"]
	 *           [true, "id", "asc"]
	 *           [true, "id", "desc"]
	 *           [false, "date", "asc"]
	 *           [false, "date", "desc"]
	 *           [false, "date_modified", "asc"]
	 *           [false, "date_modified", "desc"]
	 *           [false, "total", "asc"]
	 *           [false, "total", "desc"]
	 *           [false, "id", "asc"]
	 *           [false, "id", "desc"]
	 * @param bool   $hpos    Whether to use the custom orders table.
	 * @param string $orderby Sort field.
	 * @param string $order   Sort direction.
	 */
	public function test_pages_tied_orders_once_each( bool $hpos, string $orderby, string $order ): void {
		$this->use_storage( $hpos );
		$ids = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$ids[] = $this->create_dated_order( 10 );
		}
		$this->set_modified_date( $ids, $hpos );

		$paged = array();
		for ( $page = 1; $page <= 3; $page++ ) {
			$result = OrdersQuery::execute(
				array(
					'orderby'  => $orderby,
					'order'    => $order,
					'page'     => $page,
					'per_page' => 3,
				)
			);
			$this->assertIsArray( $result );
			$paged = array_merge( $paged, array_column( $result['orders'], 'id' ) );
		}

		if ( 'desc' === $order ) {
			rsort( $ids );
		}
		$this->assertSame( $ids, $paged, 'Tied orders should be sorted by ID in the requested direction.' );
	}

	/**
	 * @testdox Should sort orders by total.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_sorts_by_total( bool $hpos ): void {
		$this->use_storage( $hpos );
		$hundred = $this->create_dated_order( 100 );
		$nine    = $this->create_dated_order( 9 );
		$ten     = $this->create_dated_order( 10 );

		$result = OrdersQuery::execute(
			array(
				'orderby' => 'total',
				'order'   => 'asc',
			)
		);

		$this->assertSame( array( $nine, $ten, $hundred ), array_column( $result['orders'], 'id' ) );
	}

	/**
	 * Create a processing order with a fixed creation date.
	 *
	 * @param float $total Order total.
	 * @return int
	 */
	private function create_dated_order( float $total ): int {
		$order = wc_create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->set_date_created( '2026-01-01T10:00:00' );
		$order->set_total( $total );
		$order->save();

		return $order->get_id();
	}

	/**
	 * Give orders one modified date in the table the query reads, since saving an order sets it to now.
	 *
	 * @param int[] $ids  Order IDs.
	 * @param bool  $hpos Whether the custom orders table stores the orders.
	 */
	private function set_modified_date( array $ids, bool $hpos ): void {
		global $wpdb;

		foreach ( $ids as $id ) {
			if ( $hpos ) {
				$wpdb->update( OrderUtil::get_table_for_orders(), array( 'date_updated_gmt' => '2026-01-02 10:00:00' ), array( 'id' => $id ) );
			} else {
				$wpdb->update(
					$wpdb->posts,
					array(
						'post_modified'     => '2026-01-02 10:00:00',
						'post_modified_gmt' => '2026-01-02 10:00:00',
					),
					array( 'ID' => $id )
				);
			}
		}
	}

	/**
	 * @testdox Should return the order number, type, parent, totals and paid and completed dates of every order.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_returns_order_fields( bool $hpos ): void {
		$this->use_storage( $hpos );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$paid_at = strtotime( '2026-01-02 03:04:05 UTC' );
		$parent  = OrderHelper::create_order();
		$child   = OrderHelper::create_order();
		$child->set_parent_id( $parent->get_id() );
		$child->set_date_paid( $paid_at );
		$child->set_date_completed( $paid_at + HOUR_IN_SECONDS );
		$child->save();
		add_filter(
			'woocommerce_order_number',
			static function ( $number ) {
				return 'A-' . $number;
			}
		);

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute( array( 'include' => array( $parent->get_id(), $child->get_id() ) ) );

		$this->assertNotWPError( $result, 'The new fields should match the output schema.' );
		$orders = array_column( $result['orders'], null, 'id' );
		$child  = $orders[ $child->get_id() ];
		$parent = $orders[ $parent->get_id() ];
		$this->assertSame( 'A-' . $child['id'], $child['number'], 'The number should be the one shown to the customer, not the ID.' );
		$this->assertSame( 'shop_order', $child['type'] );
		$this->assertSame( $parent['id'], $child['parent_id'] );
		$this->assertSame( 0, $parent['parent_id'] );
		$this->assertSame( '10', $child['shipping_total'] );
		$this->assertSame( '0', $child['discount_total'] );
		$this->assertSame( '0', $child['total_tax'] );
		$this->assertSame( '2026-01-02T03:04:05+00:00', $child['date_paid_gmt'] );
		$this->assertSame( '2026-01-02T04:04:05+00:00', $child['date_completed_gmt'] );
		$this->assertArrayHasKey( 'date_paid', $child );
		$this->assertArrayHasKey( 'date_completed', $child );
		$this->assertArrayNotHasKey( 'date_paid', $parent, 'An unpaid order should leave the date out rather than return null.' );
		$this->assertArrayNotHasKey( 'date_completed_gmt', $parent );
	}

	/**
	 * @testdox Should return the shipping, fee, coupon and tax lines with include_lines, named as in wc/v3/orders.
	 *
	 * @dataProvider storage_modes
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	public function test_include_lines_returns_every_line_type( bool $hpos ): void {
		$this->use_storage( $hpos );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $order, $rate_id ) = $this->create_order_with_every_line();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'            => $order->get_id(),
				'include_lines' => true,
			)
		);

		$this->assertNotWPError( $result, 'The lines should match the output schema.' );
		$data = $result['orders'][0];
		$this->assertArrayNotHasKey( 'line_items', $data, 'include_lines should not also return the product lines.' );
		$this->assertSame( '5', $data['discount_total'] );

		$this->assertCount( 1, $data['shipping_lines'] );
		$this->assertSame( 'flat_rate_shipping', $data['shipping_lines'][0]['method_id'] );
		$this->assertSame( '1', $data['shipping_lines'][0]['total_tax'] );

		$this->assertCount( 1, $data['fee_lines'] );
		$this->assertSame( 'Handling', $data['fee_lines'][0]['name'] );
		$this->assertSame( 'taxable', $data['fee_lines'][0]['tax_status'] );
		$this->assertSame( '5', $data['fee_lines'][0]['total'] );

		$this->assertCount( 1, $data['coupon_lines'] );
		$this->assertSame( 'save5', $data['coupon_lines'][0]['code'] );
		$this->assertSame( '5', $data['coupon_lines'][0]['discount'] );

		$this->assertCount( 1, $data['tax_lines'] );
		$tax_line = $data['tax_lines'][0];
		$this->assertSame( $rate_id, $tax_line['rate_id'] );
		$this->assertSame( 'VAT', $tax_line['label'] );
		$this->assertFalse( $tax_line['compound'] );
		$this->assertEqualsWithDelta( (float) $data['total_tax'], (float) $tax_line['tax_total'] + (float) $tax_line['shipping_tax_total'], 0.001, 'The tax line should add up to the order tax.' );
	}

	/**
	 * @testdox Should return the SKU, price, tax class and taxes of each line item.
	 */
	public function test_line_items_return_price_and_taxes(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $order ) = $this->create_order_with_every_line();
		$product       = current( $order->get_items() )->get_product();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'                 => $order->get_id(),
				'include_line_items' => true,
			)
		);

		$this->assertNotWPError( $result, 'The line items should match the output schema.' );
		$line_item = $result['orders'][0]['line_items'][0];
		$this->assertArrayNotHasKey( 'shipping_lines', $result['orders'][0], 'Shipping lines should need include_lines.' );
		$this->assertSame( $product->get_sku(), $line_item['sku'] );
		$this->assertSame( '8.75', $line_item['price'], 'The price should be per unit and after the coupon: 35 over 4 units.' );
		$this->assertSame( '', $line_item['tax_class'] );
		$this->assertSame( '4', $line_item['subtotal_tax'] );
		$this->assertSame( '3.5', $line_item['total_tax'] );
	}

	/**
	 * @testdox Should leave out the SKU of a deleted product and return a price of 0 for a line with no quantity.
	 */
	public function test_line_items_without_product_or_quantity(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$order = OrderHelper::create_order();
		$item  = current( $order->get_items() );
		$item->set_quantity( 0 );
		$item->save();
		wc_get_product( $item->get_product_id() )->delete( true );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id'                 => $order->get_id(),
				'include_line_items' => true,
			)
		);

		$this->assertNotWPError( $result );
		$line_item = $result['orders'][0]['line_items'][0];
		$this->assertArrayNotHasKey( 'sku', $line_item, 'A missing product should leave the SKU out rather than return null.' );
		$this->assertSame( '0', $line_item['price'] );
	}

	/**
	 * @testdox Should return no line arrays when neither include_line_items nor include_lines is sent.
	 */
	public function test_returns_no_lines_by_default(): void {
		$order = OrderHelper::create_order();

		$result = OrdersQuery::execute( array( 'id' => $order->get_id() ) );

		foreach ( array( 'line_items', 'shipping_lines', 'fee_lines', 'coupon_lines', 'tax_lines' ) as $key ) {
			$this->assertArrayNotHasKey( $key, $result['orders'][0] );
		}
	}

	/**
	 * Create a taxed order with one product line, one shipping line, a fee and a coupon.
	 *
	 * @return array{0: \WC_Order, 1: int} The order and the ID of its tax rate.
	 */
	private function create_order_with_every_line(): array {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$rate_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
		\WC_Helper_Coupon::create_coupon(
			'save5',
			array(
				'discount_type' => 'fixed_cart',
				'coupon_amount' => '5',
			)
		);

		$order = OrderHelper::create_order();
		$fee   = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'       => 'Handling',
				'tax_status' => 'taxable',
				'total'      => '5',
			)
		);
		$order->add_item( $fee );
		$order->apply_coupon( 'save5' );
		$order->calculate_totals();

		return array( wc_get_order( $order->get_id() ), $rate_id );
	}

	/**
	 * Switch the order storage for the current test.
	 *
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	private function use_storage( bool $hpos ): void {
		$this->original_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( $hpos );
	}

	/**
	 * Create Jane Doe's order, whose address, phone, shipping name and item names appear in no other test order.
	 *
	 * @return int Order ID.
	 */
	private function create_detailed_order(): int {
		$order = wc_get_order( $this->create_customer_order( 'Jane', 'Doe', 'jane.doe@example.com', 'Enamel mug' ) );
		$order->set_billing_address_1( '42 Elm Street' );
		$order->set_billing_phone( '0199-4477' );
		$order->set_shipping_first_name( 'Robin' );

		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Courier express' );
		$order->add_item( $shipping );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_name( 'Gift wrap' );
		$order->add_item( $fee );

		$coupon = new \WC_Order_Item_Coupon();
		$coupon->set_code( 'spring10' );
		$order->add_item( $coupon );

		$order->save();

		return $order->get_id();
	}

	/**
	 * Create an order with one product and the given billing and shipping names.
	 *
	 * @param string      $first_name   Billing first name.
	 * @param string      $last_name    Billing last name.
	 * @param string      $email        Billing email.
	 * @param string|null $product_name Name of the ordered product, or null for the helper's default.
	 * @param int         $customer_id  Customer ID, 0 for a guest.
	 * @return int Order ID.
	 */
	private function create_customer_order( string $first_name, string $last_name, string $email, ?string $product_name = null, int $customer_id = 0 ): int {
		$product = WC_Helper_Product::create_simple_product();
		if ( null !== $product_name ) {
			$product->set_name( $product_name );
			$product->save();
		}

		$order = WC_Helper_Order::create_order( $customer_id, $product );
		$order->set_billing_first_name( $first_name );
		$order->set_billing_last_name( $last_name );
		$order->set_billing_email( $email );
		$order->set_shipping_first_name( $first_name );
		$order->set_shipping_last_name( $last_name );
		$order->save();

		return $order->get_id();
	}
}
