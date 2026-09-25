<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\FilterData;
use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;
use Automattic\WooCommerce\Internal\ProductFilters\Params;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

/**
 * Unit tests for FilterData::normalize_query_vars().
 *
 * @covers \Automattic\WooCommerce\Internal\ProductFilters\FilterData
 */
class FilterDataNormalisationTest extends \WC_Unit_Test_Case {

	/**
	 * Taxonomy param map the stubbed Params returns, so normalisation does not depend on
	 * which taxonomies happen to be registered when the real Params cache is warmed.
	 */
	private const TAXONOMY_PARAMS = array(
		'product_cat'   => 'categories',
		'product_tag'   => 'tags',
		'product_brand' => 'wc_brands',
	);

	/**
	 * Full effective parameter map returned by the Params stub.
	 *
	 * @var array
	 */
	private $cache_params = array(
		'price'    => array( 'min_price', 'max_price' ),
		'taxonomy' => self::TAXONOMY_PARAMS,
	);

	/**
	 * The private method under test, exposed via reflection.
	 *
	 * @var \ReflectionMethod
	 */
	private $normalize;

	/**
	 * A FilterData instance to invoke the method on.
	 *
	 * @var FilterData
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$query_clauses           = $this->createMock( QueryClausesGenerator::class );
		$taxonomy_hierarchy_data = $this->createMock( TaxonomyHierarchyData::class );

		$params = $this->createMock( Params::class );
		$params->method( 'get_param' )->willReturnCallback(
			static function ( string $type ): array {
				return 'taxonomy' === $type ? self::TAXONOMY_PARAMS : array();
			}
		);
		$params->method( 'get_params' )->willReturnCallback(
			function (): array {
				return $this->cache_params;
			}
		);

		$this->sut = new FilterData( $query_clauses, $taxonomy_hierarchy_data, $params );

		$reflection      = new \ReflectionClass( FilterData::class );
		$this->normalize = $reflection->getMethod( 'normalize_query_vars' );
		$this->normalize->setAccessible( true );
	}

	/**
	 * Invoke normalize_query_vars() with the given array.
	 *
	 * @param array $query_vars Input query vars.
	 * @return array Normalised copy.
	 */
	private function normalize( array $query_vars ): array {
		return $this->normalize->invoke( $this->sut, $query_vars );
	}

	/**
	 * @testdox Cache keys depend on all filter params, not only taxonomy names or registration order.
	 */
	public function test_cache_key_uses_complete_parameter_map(): void {
		$key_method = new \ReflectionMethod( $this->sut, 'get_transient_key' );
		$query_vars = array( 'post_type' => 'product' );
		$initial    = $key_method->invoke( $this->sut, $query_vars, 'price' );

		$this->cache_params             = array_reverse( $this->cache_params, true );
		$this->cache_params['taxonomy'] = array_reverse( $this->cache_params['taxonomy'], true );
		$this->assertSame( $initial, $key_method->invoke( $this->sut, $query_vars, 'price' ) );

		$this->cache_params['price'][0] = 'custom_min_price';
		$this->assertNotSame( $initial, $key_method->invoke( $this->sut, $query_vars, 'price' ) );
	}

	/**
	 * @testdox Keys are sorted alphabetically regardless of insertion order.
	 */
	public function test_ksort_normalises_key_order(): void {
		$a = $this->normalize(
			array(
				'z_key' => 'val',
				'a_key' => 'val',
			)
		);
		$b = $this->normalize(
			array(
				'a_key' => 'val',
				'z_key' => 'val',
			)
		);

		$this->assertSame( $a, $b );
		$this->assertSame( array_keys( $a ), array( 'a_key', 'z_key' ) );
	}

	/**
	 * @testdox filter_ values: comma items are sorted, trimmed and lowercased.
	 */
	public function test_filter_values_are_normalised(): void {
		$result = $this->normalize( array( 'filter_color' => ' Blue , Red , green ' ) );

		$this->assertSame( 'blue,green,red', $result['filter_color'] );
	}

	/**
	 * @testdox Equivalent filter_ values in different orders produce the same output.
	 */
	public function test_filter_values_different_order_produces_same_result(): void {
		$a = $this->normalize( array( 'filter_color' => 'red,blue' ) );
		$b = $this->normalize( array( 'filter_color' => 'blue,red' ) );

		$this->assertSame( $a['filter_color'], $b['filter_color'] );
	}

	/**
	 * @testdox Every taxonomy param reported by Params is normalised as a set.
	 */
	public function test_taxonomy_set_params_are_normalised(): void {
		foreach ( self::TAXONOMY_PARAMS as $taxonomy => $param ) {
			$a = $this->normalize( array( $param => ' Shirts , hats ' ) );
			$b = $this->normalize( array( $param => 'hats,Shirts' ) );

			$this->assertSame( 'hats,shirts', $a[ $param ], "Values of the {$taxonomy} param ({$param}) should be trimmed, lowercased and sorted." );
			$this->assertSame( $a[ $param ], $b[ $param ], "Equivalent {$param} values in different orders should normalise identically." );
		}
	}

	/**
	 * @testdox A taxonomy param absent from the Params map is left unchanged.
	 */
	public function test_unknown_taxonomy_set_param_is_not_normalised(): void {
		$result = $this->normalize( array( 'brands' => 'nike,adidas' ) );

		$this->assertSame( 'nike,adidas', $result['brands'], 'Only params reported by Params should be treated as unordered sets.' );
	}

	/**
	 * @testdox rating_filter is treated the same as filter_ keys.
	 */
	public function test_rating_filter_is_normalised(): void {
		$a = $this->normalize( array( 'rating_filter' => '5,3' ) );
		$b = $this->normalize( array( 'rating_filter' => '3,5' ) );

		$this->assertSame( $a['rating_filter'], $b['rating_filter'] );
		$this->assertSame( '3,5', $a['rating_filter'] );
	}

	/**
	 * @testdox query_type_ values are trimmed and lowercased.
	 */
	public function test_query_type_values_are_normalised(): void {
		$result = $this->normalize( array( 'query_type_color' => '  OR  ' ) );

		$this->assertSame( 'or', $result['query_type_color'] );
	}

	/**
	 * @testdox min_price is trimmed.
	 */
	public function test_min_price_is_trimmed(): void {
		$result = $this->normalize( array( 'min_price' => ' 10 ' ) );

		$this->assertSame( '10', $result['min_price'] );
	}

	/**
	 * @testdox max_price is trimmed.
	 */
	public function test_max_price_is_trimmed(): void {
		$result = $this->normalize( array( 'max_price' => ' 99 ' ) );

		$this->assertSame( '99', $result['max_price'] );
	}

	/**
	 * @testdox Non-filter keys are left unchanged.
	 */
	public function test_unrelated_keys_are_unchanged(): void {
		$result = $this->normalize(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
			)
		);

		$this->assertSame( 'product', $result['post_type'] );
		$this->assertSame( -1, $result['posts_per_page'] );
	}

	/**
	 * @testdox Combined normalisation: key order + filter value order produce the same hash.
	 */
	public function test_combined_normalisation_produces_same_hash(): void {
		$a = $this->normalize(
			array(
				'filter_color'     => 'red,blue',
				'min_price'        => ' 10 ',
				'query_type_color' => ' OR ',
				'post_type'        => 'product',
			)
		);

		$b = $this->normalize(
			array(
				'post_type'        => 'product',
				'query_type_color' => 'or',
				'min_price'        => '10',
				'filter_color'     => 'blue,red',
			)
		);

		$this->assertSame( $a, $b );
	}

	/**
	 * @testdox Non-string values are not modified.
	 */
	public function test_non_string_values_are_not_modified(): void {
		$result = $this->normalize( array( 'filter_color' => array( 'red', 'blue' ) ) );

		$this->assertSame( array( 'red', 'blue' ), $result['filter_color'] );
	}

	/**
	 * @testdox Empty tokens from malformed comma lists are removed.
	 */
	public function test_empty_tokens_are_removed(): void {
		$result = $this->normalize( array( 'filter_color' => 'red,,blue,' ) );

		$this->assertSame( 'blue,red', $result['filter_color'] );
	}

	/**
	 * @testdox Duplicate tokens are deduplicated.
	 */
	public function test_duplicate_tokens_are_deduplicated(): void {
		$a = $this->normalize( array( 'filter_color' => 'red,blue,red' ) );
		$b = $this->normalize( array( 'filter_color' => 'blue,red' ) );

		$this->assertSame( $a['filter_color'], $b['filter_color'] );
	}
}
