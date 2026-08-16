<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use WC_Product;
use WP_Query;

/**
 * Tests Product Collection preset queries against controlled products.
 */
class CollectionPresetsTest extends \WP_UnitTestCase {
	/**
	 * Product Collection controller using the real query builder and handlers.
	 *
	 * @var ProductCollectionMock
	 */
	private $block_instance;

	/**
	 * Fixture factory.
	 *
	 * @var FixtureData
	 */
	private $fixtures;

	/**
	 * Set up the real Product Collection query seam.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->block_instance = new ProductCollectionMock();
		$this->fixtures       = new FixtureData();
	}

	/**
	 * Presets and the exact collection query behavior each row exercises.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_collection_presets(): array {
		return array(
			'new arrivals'       => array( 'new-arrivals' ),
			'top rated'          => array( 'top-rated' ),
			'best sellers'       => array( 'best-sellers' ),
			'on sale'            => array( 'on-sale' ),
			'featured'           => array( 'featured' ),
			'related categories' => array( 'related-categories' ),
			'related tags'       => array( 'related-tags' ),
		);
	}

	/**
	 * @testdox $scenario preset returns the exact ordered controlled products
	 *
	 * @dataProvider provider_collection_presets
	 *
	 * @param string $scenario Preset scenario.
	 */
	public function test_collection_preset_result( string $scenario ): void {
		global $wp_filter;

		$products                         = array();
		$terms                            = array();
		$parsed_block                     = Utils::get_base_parsed_block();
		$expected_ids                     = array();
		$distractor_id                    = 0;
		$posts_clauses_before             = $wp_filter['posts_clauses'] ?? null;
		$on_sale_value_option             = '_transient_wc_products_onsale';
		$on_sale_timeout_option           = '_transient_timeout_wc_products_onsale';
		$on_sale_transient_value_before   = get_option( $on_sale_value_option, false );
		$on_sale_transient_timeout_before = get_option( $on_sale_timeout_option, false );

		try {
			switch ( $scenario ) {
				case 'new-arrivals':
					$recent                                    = $this->create_product(
						$products,
						array(
							'name'         => 'Preset recent target',
							'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
						)
					);
					$old                                       = $this->create_product(
						$products,
						array(
							'name'         => 'Preset old distractor',
							'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ),
						)
					);
					$expected_ids                              = array( $recent->get_id() );
					$distractor_id                             = $old->get_id();
					$parsed_block['attrs']['collection']       = 'woocommerce/product-collection/new-arrivals';
					$parsed_block['attrs']['query']['orderBy'] = 'date';
					$parsed_block['attrs']['query']['order']   = 'desc';
					$parsed_block['attrs']['query']['timeFrame'] = array(
						'operator' => 'in',
						'value'    => '-7 days',
					);
					break;

				case 'top-rated':
					$highest    = $this->create_product( $products, array( 'name' => 'Preset five-star target' ) );
					$second     = $this->create_product( $products, array( 'name' => 'Preset four-star target' ) );
					$distractor = $this->create_product( $products, array( 'name' => 'Preset one-star distractor' ) );
					$this->set_rating( $highest, 5.0, 5 );
					$this->set_rating( $second, 4.0, 4 );
					$this->set_rating( $distractor, 1.0, 1 );
					$expected_ids                              = array( $highest->get_id(), $second->get_id() );
					$distractor_id                             = $distractor->get_id();
					$parsed_block['attrs']['collection']       = 'woocommerce/product-collection/top-rated';
					$parsed_block['attrs']['query']['orderBy'] = 'rating';
					$parsed_block['attrs']['query']['order']   = 'desc';
					$parsed_block['attrs']['query']['perPage'] = 2;
					break;

				case 'best-sellers':
					$highest    = $this->create_product( $products, array( 'name' => 'Preset highest-sales target' ) );
					$second     = $this->create_product( $products, array( 'name' => 'Preset second-sales target' ) );
					$distractor = $this->create_product( $products, array( 'name' => 'Preset low-sales distractor' ) );
					$this->set_total_sales( $highest, 30 );
					$this->set_total_sales( $second, 20 );
					$this->set_total_sales( $distractor, 1 );
					$expected_ids                              = array( $highest->get_id(), $second->get_id() );
					$distractor_id                             = $distractor->get_id();
					$parsed_block['attrs']['collection']       = 'woocommerce/product-collection/best-sellers';
					$parsed_block['attrs']['query']['orderBy'] = 'popularity';
					$parsed_block['attrs']['query']['order']   = 'desc';
					$parsed_block['attrs']['query']['perPage'] = 2;
					break;

				case 'on-sale':
					$on_sale                             = $this->create_product(
						$products,
						array(
							'name'          => 'Preset sale target',
							'regular_price' => '20',
							'sale_price'    => '10',
						)
					);
					$distractor                          = $this->create_product( $products, array( 'name' => 'Preset regular-price distractor' ) );
					$expected_ids                        = array( $on_sale->get_id() );
					$distractor_id                       = $distractor->get_id();
					$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/on-sale';
					$parsed_block['attrs']['query']['woocommerceOnSale'] = true;
					delete_transient( 'wc_products_onsale' );
					break;

				case 'featured':
					$featured = $this->create_product( $products, array( 'name' => 'Preset featured target' ) );
					$featured->set_featured( true );
					$featured->save();
					$distractor                                 = $this->create_product( $products, array( 'name' => 'Preset ordinary distractor' ) );
					$expected_ids                               = array( $featured->get_id() );
					$distractor_id                              = $distractor->get_id();
					$parsed_block['attrs']['collection']        = 'woocommerce/product-collection/featured';
					$parsed_block['attrs']['query']['featured'] = true;
					break;

				case 'related-categories':
				case 'related-tags':
					$taxonomy = 'related-categories' === $scenario ? 'product_cat' : 'product_tag';
					$term     = wp_insert_term( "Preset {$scenario}", $taxonomy );
					if ( is_wp_error( $term ) ) {
						throw new \RuntimeException( $term->get_error_message() );
					}
					$term_id = (int) $term['term_id'];
					$terms[] = array( $term_id, $taxonomy );

					$reference  = $this->create_product( $products, array( 'name' => "Preset {$scenario} reference" ) );
					$related    = $this->create_product( $products, array( 'name' => "Preset {$scenario} target" ) );
					$distractor = $this->create_product( $products, array( 'name' => "Preset {$scenario} distractor" ) );
					wp_set_object_terms( $reference->get_id(), array( $term_id ), $taxonomy );
					wp_set_object_terms( $related->get_id(), array( $term_id ), $taxonomy );
					$expected_ids                                       = array( $related->get_id() );
					$distractor_id                                      = $distractor->get_id();
					$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/related';
					$parsed_block['attrs']['query']['productReference'] = $reference->get_id();
					$parsed_block['attrs']['query']['relatedBy']        = array(
						'categories' => 'product_cat' === $taxonomy,
						'tags'       => 'product_tag' === $taxonomy,
					);
					break;

				default:
					$this->fail( "Unknown Product Collection preset scenario: {$scenario}" );
			}

			$fixture_ids                                = array_map(
				static function ( WC_Product $product ): int {
					return $product->get_id();
				},
				$products
			);
			$parsed_block['attrs']['query']['post__in'] = $fixture_ids;
			$query_args                                 = Utils::initialize_merged_query( $this->block_instance, $parsed_block );
			$query                                      = new WP_Query( $query_args );
			$actual_ids                                 = wp_list_pluck( $query->posts, 'ID' );

			$this->assertNotEmpty( $actual_ids, 'The preset must return a controlled product.' );
			$this->assertSame( $expected_ids, $actual_ids, 'The preset must return the exact ordered controlled products.' );
			$this->assertNotContains( $distractor_id, $actual_ids, 'The controlled distractor must be excluded.' );
		} finally {
			foreach ( array_reverse( $products ) as $product ) {
				$product->delete( true );
			}
			foreach ( array_reverse( $terms ) as $term ) {
				wp_delete_term( $term[0], $term[1] );
			}

			if ( null === $posts_clauses_before ) {
				unset( $wp_filter['posts_clauses'] );
			} else {
				$wp_filter['posts_clauses'] = $posts_clauses_before;
			}

			delete_transient( 'wc_products_onsale' );

			if ( false !== $on_sale_transient_value_before ) {
				add_option(
					$on_sale_value_option,
					$on_sale_transient_value_before,
					'',
					false === $on_sale_transient_timeout_before
				);
			}
			if ( false !== $on_sale_transient_timeout_before ) {
				add_option( $on_sale_timeout_option, $on_sale_transient_timeout_before, '', false );
			}
		}
	}

	/**
	 * Create and track a published, in-stock simple product.
	 *
	 * @param WC_Product[] $products Tracked products.
	 * @param array        $props    Product properties.
	 * @return WC_Product
	 */
	private function create_product( array &$products, array $props ): WC_Product {
		$product    = $this->fixtures->get_simple_product(
			wp_parse_args(
				$props,
				array(
					'regular_price' => '10',
					'status'        => 'publish',
					'stock_status'  => 'instock',
				)
			)
		);
		$products[] = $product;

		return $product;
	}

	/**
	 * Persist rating data used by the Product Collection rating order.
	 *
	 * @param WC_Product $product Product.
	 * @param float      $rating  Average rating.
	 * @param int        $count   Rating count.
	 */
	private function set_rating( WC_Product $product, float $rating, int $count ): void {
		$product->set_average_rating( $rating );
		$product->set_rating_counts( array( (int) $rating => $count ) );
		$product->set_review_count( $count );
		$product->save();
	}

	/**
	 * Persist total-sales data used by the Product Collection popularity order.
	 *
	 * @param WC_Product $product Product.
	 * @param int        $sales   Total sales.
	 */
	private function set_total_sales( WC_Product $product, int $sales ): void {
		$product->set_total_sales( $sales );
		$product->save();
	}
}
