<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Products;

use Automattic\WooCommerce\Internal\Products\ProductsOrderingMoveService;
use Automattic\WooCommerce\Internal\Products\ProductsOrderingReindexService;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductsOrderingMoveService class.
 */
final class ProductsOrderingMoveServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductsOrderingMoveService
	 */
	private ProductsOrderingMoveService $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductsOrderingMoveService();
		$this->sut->init( new ProductsOrderingReindexService() );
	}

	/**
	 * @testdox move() repositions a product within a fully indexed catalog and shifts the affected range.
	 * @dataProvider move_provider
	 *
	 * @param int   $sorting_idx     Index (0-based) of the product being moved.
	 * @param int   $previd_idx      Index of the product immediately before the drop target, or -1 if dropped at the top.
	 * @param int   $nextid_idx      Index of the product immediately after the drop target, or -1 if dropped at the bottom.
	 * @param int[] $expected_orders Expected menu_order values indexed by original product position [P1..P5].
	 */
	public function test_move( int $sorting_idx, int $previd_idx, int $nextid_idx, array $expected_orders ): void {
		global $wpdb;

		$products    = array();
		$product_ids = array();
		for ( $i = 1; $i <= 5; ++$i ) {
			$product = new \WC_Product_Simple();
			$product->set_menu_order( $i );
			$product->save();

			$product_id              = $product->get_id();
			$product_ids[]           = $product_id;
			$products[ $product_id ] = $product;
		}

		$result = $this->sut->move(
			$previd_idx >= 0 ? $product_ids[ $previd_idx ] : 0,
			$product_ids[ $sorting_idx ],
			$nextid_idx >= 0 ? $product_ids[ $nextid_idx ] : 0
		);

		$this->assertEmpty( $result->reindexed, 'No reindex should occur when all products are already sequentially indexed.' );
		foreach ( $product_ids as $index => $product_id ) {
			$actual = (int) $wpdb->get_var( $wpdb->prepare( "SELECT menu_order FROM {$wpdb->posts} WHERE ID = %d", $product_id ) );
			$this->assertSame( $expected_orders[ $index ], $actual, "Product at index {$index} has wrong menu_order." );
			$products[ $product_id ]->delete( true );
		}
	}

	/**
	 * Data provider for test_move.
	 *
	 * Columns: sorting_idx, previd_idx (-1 = none), nextid_idx (-1 = none), expected menu_orders [P1..P5].
	 *
	 * @return array
	 */
	public function move_provider(): array {
		return array(
			'last to first'             => array( 4, -1, 0, array( 2, 3, 4, 5, 1 ) ),
			'first to last'             => array( 0, 4, -1, array( 5, 1, 2, 3, 4 ) ),
			'middle one position left'  => array( 2, 0, 1, array( 1, 3, 2, 4, 5 ) ),
			'middle one position right' => array( 2, 3, 4, array( 1, 2, 4, 3, 5 ) ),
			'middle to first'           => array( 2, -1, 0, array( 2, 3, 1, 4, 5 ) ),
			'middle to last'            => array( 2, 4, -1, array( 1, 2, 5, 3, 4 ) ),
			'drop in place'             => array( 2, 1, 3, array( 1, 2, 3, 4, 5 ) ),
		);
	}

	/**
	 * @testdox move() triggers a full reindex and then applies the move when no products have been indexed yet.
	 */
	public function test_move_with_no_prior_indexing(): void {
		global $wpdb;

		$alpha_id = $this->create_product( 'Alpha', 0 );
		$beta_id  = $this->create_product( 'Beta', 0 );
		$gamma_id = $this->create_product( 'Gamma', 0 );
		$products = array( $alpha_id, $beta_id, $gamma_id );

		$result = $this->sut->move( $alpha_id, $gamma_id, $beta_id );

		$this->assertSame( array( $alpha_id => 1 ), $result->reindexed );
		$this->assertSame(
			array(
				$gamma_id => 2,
				$beta_id  => 3,
			),
			$result->moved
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$db_positions = $wpdb->get_results( $wpdb->prepare( "SELECT ID, menu_order FROM {$wpdb->posts} WHERE ID IN (%d, %d, %d) ORDER BY menu_order ASC", $alpha_id, $gamma_id, $beta_id ) );
		$this->assertEquals(
			array(
				$alpha_id => 1,
				$gamma_id => 2,
				$beta_id  => 3,
			),
			array_column( $db_positions, 'menu_order', 'ID' )
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() triggers a full reindex when the target position is adjacent to an unindexed product.
	 */
	public function test_move_from_indexed_into_unindexed_group(): void {
		$delta_id = $this->create_product( 'Delta', 0 );
		$gamma_id = $this->create_product( 'Gamma', 0 );
		$alpha_id = $this->create_product( 'Alpha', 1 );
		$beta_id  = $this->create_product( 'Beta', 2 );
		$products = array( $delta_id, $gamma_id, $alpha_id, $beta_id );

		$result = $this->sut->move( $delta_id, $beta_id, $gamma_id );

		$this->assertSame( array( $delta_id => 1 ), $result->reindexed );
		$this->assertSame(
			array(
				$beta_id  => 2,
				$gamma_id => 3,
				$alpha_id => 4,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() triggers a reindex when two products share the same position (collision) and then applies the move.
	 */
	public function test_move_triggers_reindex_on_position_collision(): void {
		$delta_id = $this->create_product( 'Delta', 1 );
		$beta_id  = $this->create_product( 'Beta', 2 );
		$gamma_id = $this->create_product( 'Gamma', 2 );
		$products = array( $delta_id, $beta_id, $gamma_id );

		$result = $this->sut->move( $delta_id, $gamma_id, $beta_id );

		$this->assertSame( array( $delta_id => 1 ), $result->reindexed );
		$this->assertSame(
			array(
				$gamma_id => 2,
				$beta_id  => 3,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() resolves a collision even when colliding anchors already match the requested order.
	 */
	public function test_move_resolves_collision_matching_requested_order(): void {
		$alpha_id = $this->create_product( 'Alpha', 1 );
		$beta_id  = $this->create_product( 'Beta', 2 );
		$gamma_id = $this->create_product( 'Gamma', 2 );
		$products = array( $alpha_id, $beta_id, $gamma_id );

		$result = $this->sut->move( $alpha_id, $beta_id, $gamma_id );

		// Reindex resolves the collision; product is already in place post-reindex, so no move needed.
		$this->assertSame(
			array(
				$alpha_id => 1,
				$beta_id  => 2,
				$gamma_id => 3,
			),
			$result->reindexed
		);
		$this->assertSame( array(), $result->moved );

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() triggers a reindex but skips the move when the product is already in the correct position after reindexing.
	 */
	public function test_move_skips_apply_when_in_place_after_reindex(): void {
		$gamma_id = $this->create_product( 'Gamma', 0 );
		$beta_id  = $this->create_product( 'Beta', 0 );
		$alpha_id = $this->create_product( 'Alpha', 0 );
		$products = array( $gamma_id, $beta_id, $alpha_id );

		$result = $this->sut->move( $alpha_id, $beta_id, $gamma_id );

		$this->assertSame(
			array(
				$alpha_id => 1,
				$beta_id  => 2,
				$gamma_id => 3,
			),
			$result->reindexed
		);
		$this->assertSame( array(), $result->moved );

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() does nothing when moving an unindexed product to the first position and it already sorts first.
	 */
	public function test_move_unindexed_to_first_position(): void {
		$alpha_id = $this->create_product( 'Alpha', 1 );
		$beta_id  = $this->create_product( 'Beta', 2 );
		$gamma_id = $this->create_product( 'Gamma', 0 );
		$products = array( $alpha_id, $beta_id, $gamma_id );

		$result = $this->sut->move( 0, $gamma_id, $alpha_id );

		$this->assertSame( array(), $result->reindexed );
		$this->assertSame( array(), $result->moved );

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() is a no-op when both anchors are zero.
	 */
	public function test_move_with_both_anchors_zero(): void {
		$alpha_id = $this->create_product( 'Alpha', 1 );

		$result = $this->sut->move( 0, $alpha_id, 0 );

		$this->assertSame( array(), $result->reindexed );
		$this->assertSame( array(), $result->moved );

		wc_get_product( $alpha_id )->delete( true );
	}

	/**
	 * @testdox move() triggers a full reindex when moving an unindexed product to the last position.
	 */
	public function test_move_unindexed_to_last_position(): void {
		$alpha_id = $this->create_product( 'Alpha', 1 );
		$beta_id  = $this->create_product( 'Beta', 2 );
		$gamma_id = $this->create_product( 'Gamma', 0 );
		$products = array( $alpha_id, $beta_id, $gamma_id );

		$result = $this->sut->move( $beta_id, $gamma_id, 0 );

		$this->assertSame( array(), $result->reindexed );
		$this->assertSame(
			array(
				$alpha_id => 1,
				$beta_id  => 2,
				$gamma_id => 3,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() triggers a full reindex when moving an unindexed product into an already indexed group.
	 */
	public function test_move_from_unindexed_into_indexed_group(): void {
		$alpha_id = $this->create_product( 'Alpha', 1 );
		$beta_id  = $this->create_product( 'Beta', 2 );
		$gamma_id = $this->create_product( 'Gamma', 0 );
		$products = array( $alpha_id, $beta_id, $gamma_id );

		$result = $this->sut->move( $alpha_id, $gamma_id, $beta_id );

		$this->assertSame( array( $beta_id => 3 ), $result->reindexed );
		$this->assertSame(
			array(
				$alpha_id => 1,
				$gamma_id => 2,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() does not reindex and leaves unindexed products untouched when the move stays within the indexed group.
	 */
	public function test_move_within_indexed_group_with_unindexed_present(): void {
		$alpha_id   = $this->create_product( 'Alpha', 1 );
		$beta_id    = $this->create_product( 'Beta', 2 );
		$gamma_id   = $this->create_product( 'Gamma', 3 );
		$delta_id   = $this->create_product( 'Delta', 0 );
		$epsilon_id = $this->create_product( 'Epsilon', 0 );
		$products   = array( $alpha_id, $beta_id, $gamma_id, $delta_id, $epsilon_id );

		$result = $this->sut->move( $alpha_id, $gamma_id, $beta_id );

		$this->assertSame( array(), $result->reindexed );
		$this->assertSame(
			array(
				$gamma_id => 2,
				$beta_id  => 3,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() correctly shifts all products in the range when non-anchor duplicates exist (no reindex).
	 */
	public function test_move_with_non_anchor_duplicate_in_range(): void {
		$alpha_id   = $this->create_product( 'Alpha', 1 );
		$beta_id    = $this->create_product( 'Beta', 2 );
		$charlie_id = $this->create_product( 'Charlie', 2 );
		$delta_id   = $this->create_product( 'Delta', 3 );
		$echo_id    = $this->create_product( 'Echo', 4 );
		$products   = array( $alpha_id, $beta_id, $charlie_id, $delta_id, $echo_id );

		$result = $this->sut->move( $alpha_id, $echo_id, $beta_id );

		$this->assertSame( array(), $result->reindexed );
		$this->assertSame(
			array(
				$echo_id    => 2,
				$beta_id    => 3,
				$charlie_id => 3,
				$delta_id   => 4,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @testdox move() triggers a reindex when previous and next anchors share the same non-zero position.
	 */
	public function test_move_triggers_reindex_on_anchor_collision(): void {
		$alpha_id = $this->create_product( 'Alpha', 1 );
		$beta_id  = $this->create_product( 'Beta', 5 );
		$gamma_id = $this->create_product( 'Gamma', 5 );
		$products = array( $alpha_id, $beta_id, $gamma_id );

		$result = $this->sut->move( $beta_id, $alpha_id, $gamma_id );

		$this->assertSame( array( $gamma_id => 3 ), $result->reindexed );
		$this->assertSame(
			array(
				$beta_id  => 1,
				$alpha_id => 2,
			),
			$result->moved
		);

		array_walk( $products, static fn( $id ) => wc_get_product( $id )->delete( true ) );
	}

	/**
	 * @param string $name       Product name (controls reindex sort order via post_title ASC).
	 * @param int    $menu_order Initial menu_order value.
	 * @return int
	 */
	private function create_product( string $name, int $menu_order ): int {
		$product = new \WC_Product_Simple();
		$product->set_name( $name );
		$product->set_menu_order( $menu_order );
		$product->save();

		return $product->get_id();
	}
}
