<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\OrdersQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the orders-query ability.
 */
class OrdersQueryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should return the exact total of matching orders on partial, empty and out-of-range pages.
	 */
	public function test_returns_total_across_pages(): void {
		$order_ids = array();
		foreach ( range( 1, 11 ) as $unused ) {
			$order_ids[] = OrderHelper::create_order()->get_id();
		}

		$last = OrdersQuery::execute( array( 'page' => 2 ) );
		$this->assertSame( 11, $last['total'] );
		$this->assertSame( 2, $last['total_pages'] );
		$this->assertCount( 1, $last['orders'] );

		$beyond = OrdersQuery::execute( array( 'page' => 3 ) );
		$this->assertSame( array(), $beyond['orders'] );
		$this->assertSame( 11, $beyond['total'], 'An out-of-range page should keep the matching total.' );

		$none = OrdersQuery::execute( array( 'status' => array( 'refunded' ) ) );
		$this->assertSame( 0, $none['total'] );

		$id = OrdersQuery::execute( array( 'id' => $order_ids[0] ) );
		$this->assertSame( 1, $id['total'] );

		$schema = OrdersQuery::get_registration_args()['output_schema'];
		$this->assertTrue( rest_validate_value_from_schema( $last, $schema, 'orders' ) );
	}

	/**
	 * @testdox Should narrow the total with extension query filters.
	 */
	public function test_total_follows_extension_query_filter(): void {
		$kept     = OrderHelper::create_order();
		$excluded = OrderHelper::create_order();

		$callback = static function ( $args ) use ( $excluded ) {
			$args['post__not_in'] = array( $excluded->get_id() );
			return $args;
		};
		$hook     = OrderUtil::custom_orders_table_usage_is_enabled() ? 'woocommerce_order_query_args' : 'woocommerce_order_data_store_cpt_get_orders_query';
		add_filter( $hook, $callback );
		$result = OrdersQuery::execute( array() );
		remove_filter( $hook, $callback );

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $kept->get_id(), $result['orders'][0]['id'] );
	}
}
