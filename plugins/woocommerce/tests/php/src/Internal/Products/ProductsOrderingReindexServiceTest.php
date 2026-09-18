<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Products;

use Automattic\WooCommerce\Internal\Products\ProductsOrderingReindexService;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductsOrderingReindexService class.
 */
final class ProductsOrderingReindexServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductsOrderingReindexService
	 */
	private ProductsOrderingReindexService $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductsOrderingReindexService();
	}

	/**
	 * @testdox reindex_products assigns sequential positions starting at 1, ordered by menu_order then post_title.
	 * @dataProvider reindex_products_data_provider
	 *
	 * @param array<array{name:string,menu_order:int}> $products       Products to create before reindexing.
	 * @param string[]                                 $expected_order Product names in expected ascending position order.
	 */
	public function test_reindex_products( array $products, array $expected_order ): void {
		$name_to_id = array();
		foreach ( $products as $product_data ) {
			$product = new \WC_Product_Simple();
			$product->set_name( $product_data['name'] );
			$product->set_menu_order( $product_data['menu_order'] );
			$product->save();
			$name_to_id[ $product_data['name'] ] = $product->get_id();
		}

		$result = $this->sut->reindex_products( 2 );

		$this->assertCount( count( $expected_order ), $result );
		foreach ( $expected_order as $index => $name ) {
			$this->assertSame(
				$index + 1,
				$result[ $name_to_id[ $name ] ],
				"Product '{$name}' should have position " . ( $index + 1 ) . '.'
			);
		}
	}

	/**
	 * Data provider for test_reindex_products.
	 *
	 * @return array
	 */
	public function reindex_products_data_provider(): array {
		return array(
			'empty catalog'                      => array(
				'products'       => array(),
				'expected_order' => array(),
			),
			'unindexed products sorted by title' => array(
				'products'       => array(
					array(
						'name'       => 'Gamma',
						'menu_order' => 0,
					),
					array(
						'name'       => 'Alpha',
						'menu_order' => 0,
					),
					array(
						'name'       => 'Beta',
						'menu_order' => 0,
					),
				),
				'expected_order' => array( 'Alpha', 'Beta', 'Gamma' ),
			),
			'already sequentially indexed'       => array(
				'products'       => array(
					array(
						'name'       => 'First',
						'menu_order' => 1,
					),
					array(
						'name'       => 'Second',
						'menu_order' => 2,
					),
					array(
						'name'       => 'Third',
						'menu_order' => 3,
					),
				),
				'expected_order' => array(),
			),
			'sparse positions compacted'         => array(
				'products'       => array(
					array(
						'name'       => 'First',
						'menu_order' => 1,
					),
					array(
						'name'       => 'Second',
						'menu_order' => 5,
					),
					array(
						'name'       => 'Third',
						'menu_order' => 10,
					),
				),
				'expected_order' => array( 'First', 'Second', 'Third' ),
			),
			'collisions resolved by title'       => array(
				'products'       => array(
					array(
						'name'       => 'Beta',
						'menu_order' => 1,
					),
					array(
						'name'       => 'Alpha',
						'menu_order' => 1,
					),
					array(
						'name'       => 'Gamma',
						'menu_order' => 2,
					),
				),
				'expected_order' => array( 'Alpha', 'Beta', 'Gamma' ),
			),
		);
	}
}
