<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\OrdersQuery;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
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
	 * Switch the order storage for the current test.
	 *
	 * @param bool $hpos Whether to use the custom orders table.
	 */
	private function use_storage( bool $hpos ): void {
		$this->original_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_feature_and_usage( $hpos );
	}
}
