<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Tests related to QueryClauses service.
 */
class QueryClausesTest extends AbstractProductFiltersTest {
	/**
	 * The system under test.
	 *
	 * @var QueryClauses
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();
		$this->sut = $container->get( QueryClauses::class );
	}

	/**
	 * @testdox Test the product query with post clauses containing price clauses.
	 *
	 * @testWith [{"min_price": 20}]
	 *           [{"max_price": 50}]
	 *           [{"min_price": 20,"max_price": 50}]
	 *
	 * @param array $price_range {
	 *     Price range array.
	 *
	 *     @type int|string $min_price Optional. Min price.
	 *     @type int|string $max_price Optional. Max Price.
	 * }
	 */
	public function test_price_clauses_with( $price_range ) {
		$price_range     = array_filter(
			wp_parse_args(
				$price_range,
				array(
					'min_price' => 0,
					'max_price' => 0,
				)
			)
		);
		$filter_callback = function ( $args ) use ( $price_range ) {
			return $this->sut->add_price_clauses( $args, $price_range );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $price_range ) {
					if ( $product->is_type( 'variable' ) ) {
						$product_price = $product->get_variation_price();
					} else {
						$product_price = $product->get_regular_price();
					}
					if ( ! empty( $price_range['min_price'] ) && ! empty( $price_range['max_price'] ) ) {
						return $product_price >= $price_range['min_price'] &&
							$product_price <= $price_range['max_price'];
					}
					if ( ! empty( $price_range['max_price'] ) ) {
						return $product_price <= $price_range['max_price'];
					}
					if ( ! empty( $price_range['min_price'] ) ) {
						return $product_price >= $price_range['min_price'];
					}
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * @testdox Test the product query with post clauses containing stock clauses.
	 *
	 * @testWith [["instock"]]
	 *           [["outofstock"]]
	 *           [["onbackorder"]]
	 *           [["instock","onbackorder"]]
	 *
	 * @param array $stock_statuses Stock statuses to be queried.
	 */
	public function test_stock_clauses_with( $stock_statuses ) {
		$filter_callback = function ( $args ) use ( $stock_statuses ) {
			return $this->sut->add_stock_clauses( $args, $stock_statuses );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $stock_statuses ) {
					return in_array( $product->get_stock_status(), $stock_statuses, true );
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * Test the product query with post clauses containing attribute clauses.
	 *
	 * @testWith ["pa_color",["not-exist-slug"],"or"]
	 *           ["pa_color",["red-slug"],"or"]
	 *           ["pa_color",["red-slug","not-exist-slug"],"or"]
	 *           ["pa_color",["red-slug","green-slug"],"or"]
	 *
	 * @todo Add tests for `and` query type once https://github.com/woocommerce/woocommerce/pull/44825 is merged.
	 *
	 * @param string   $taxonomy   Attribute taxonomy name.
	 * @param string[] $terms      Chosen terms' slug.
	 * @param string   $query_type Query type. Accepts 'and' or 'or'.
	 */
	public function test_attribute_clauses_with( $taxonomy, $terms, $query_type ) {
		$chosen_attributes = array(
			$taxonomy => array(
				'terms'      => $terms,
				'query_type' => $query_type,
			),
		);
		$filter_callback   = function ( $args ) use ( $chosen_attributes ) {
			return $this->sut->add_attribute_clauses( $args, $chosen_attributes );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $chosen_attributes ) {
					$product_attributes = $product->get_attributes();

					foreach ( $chosen_attributes as $taxonomy => $data ) {
						if ( ! in_array(
							$taxonomy,
							array_keys( $product_attributes ),
							true
						) ) {
							return false;
						}

						$slugs = $product_attributes[ $taxonomy ]->get_slugs();

						if ( 'or' === $data['query_type'] && empty( array_intersect( $data['terms'], $slugs ) ) ) {
							return false;
						}

						if ( 'and' === $data['query_type'] && array_diff( $data['terms'], $slugs ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * Test the product query with post clauses containing taxonomy clauses.
	 *
	 * @testWith ["product_cat", ["cat-1"]]
	 *           ["product_cat", ["cat-2"]]
	 *           ["product_cat", ["cat-1", "cat-2"]]
	 *           ["product_tag", ["tag-1"]]
	 *           ["product_tag", ["tag-2", "tag-3"]]
	 *
	 * @param string   $taxonomy Taxonomy name.
	 * @param string[] $terms    Chosen terms' slug.
	 */
	public function test_taxonomy_clauses_with( $taxonomy, $terms ) {
		$chosen_taxonomies = array(
			$taxonomy => $terms,
		);
		$filter_callback   = function ( $args ) use ( $chosen_taxonomies ) {
			return $this->sut->add_taxonomy_clauses( $args, $chosen_taxonomies );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $chosen_taxonomies ) {
					foreach ( $chosen_taxonomies as $taxonomy => $terms ) {
						$product_terms = wp_get_post_terms( $product->get_id(), $taxonomy, array( 'fields' => 'slugs' ) );

						if ( is_wp_error( $product_terms ) ) {
							return false;
						}

						// Check if product has any of the requested terms (OR logic).
						if ( empty( array_intersect( $terms, $product_terms ) ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * @testdox Should not truncate values at or below the default cap of 5.
	 */
	public function test_cap_filter_values_within_default_cap(): void {
		$method = $this->get_cap_filter_values_method();

		$result = $method->invoke( $this->sut, 'red,blue,green,yellow,orange', 'filter_color' );

		$this->assertCount( 5, $result );
		$this->assertSame( array( 'red', 'blue', 'green', 'yellow', 'orange' ), $result );
	}

	/**
	 * @testdox Should truncate values exceeding the default cap of 5.
	 */
	public function test_cap_filter_values_truncates_excess(): void {
		$method = $this->get_cap_filter_values_method();

		$result = $method->invoke( $this->sut, 'red,blue,green,yellow,orange,pink', 'filter_color' );

		$this->assertCount( 5, $result );
		$this->assertSame( array( 'red', 'blue', 'green', 'yellow', 'orange' ), $result );
	}

	/**
	 * @testdox Should respect a custom cap from the filter hook.
	 */
	public function test_cap_filter_values_respects_custom_cap(): void {
		add_filter( 'woocommerce_product_filter_max_values_per_parameter', fn() => 2 );
		$method = $this->get_cap_filter_values_method();

		$result = $method->invoke( $this->sut, 'red,blue,green,yellow', 'filter_color' );

		remove_all_filters( 'woocommerce_product_filter_max_values_per_parameter' );
		$this->assertSame( array( 'red', 'blue' ), $result );
	}

	/**
	 * @testdox Should pass the parameter name to the filter hook.
	 */
	public function test_cap_filter_values_passes_param_name_to_hook(): void {
		$received = null;
		add_filter(
			'woocommerce_product_filter_max_values_per_parameter',
			function ( $max, $param ) use ( &$received ) {
				$received = $param;
				return $max;
			},
			10,
			2
		);
		$method = $this->get_cap_filter_values_method();
		$method->invoke( $this->sut, 'instock,outofstock,onbackorder,pending,draft,archived', 'filter_stock_status' );

		remove_all_filters( 'woocommerce_product_filter_max_values_per_parameter' );
		$this->assertSame( 'filter_stock_status', $received );
	}

	/**
	 * @testdox Should disable the cap when the filter hook returns zero.
	 */
	public function test_cap_filter_values_disabled_when_hook_returns_zero(): void {
		add_filter( 'woocommerce_product_filter_max_values_per_parameter', fn() => 0 );
		$method = $this->get_cap_filter_values_method();

		$result = $method->invoke( $this->sut, 'a,b,c,d,e,f,g,h', 'filter_color' );

		remove_all_filters( 'woocommerce_product_filter_max_values_per_parameter' );
		$this->assertCount( 8, $result );
	}

	/**
	 * @testdox Should normalise, de-duplicate, and not count duplicates toward the cap.
	 */
	public function test_cap_filter_values_deduplicates_before_cap(): void {
		$method = $this->get_cap_filter_values_method();

		$result = $method->invoke( $this->sut, 'red,Red,RED,blue,green,yellow,orange', 'filter_color' );

		$this->assertCount( 5, $result, 'Red/Red/RED collapse to one before the cap is applied' );
	}

	/**
	 * @testdox Should return an empty array for an empty string.
	 */
	public function test_cap_filter_values_empty_string(): void {
		$method = $this->get_cap_filter_values_method();

		$result = $method->invoke( $this->sut, '', 'filter_color' );

		$this->assertSame( array(), $result );
	}

	/**
	 * Get the private cap_filter_values method via reflection.
	 *
	 * @return \ReflectionMethod
	 */
	private function get_cap_filter_values_method(): \ReflectionMethod {
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'cap_filter_values' );
		$method->setAccessible( true );
		return $method;
	}
}
