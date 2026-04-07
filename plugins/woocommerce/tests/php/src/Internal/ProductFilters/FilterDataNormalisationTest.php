<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\FilterData;
use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

/**
 * Unit tests for FilterData::normalize_query_vars().
 *
 * @covers \Automattic\WooCommerce\Internal\ProductFilters\FilterData
 */
class FilterDataNormalisationTest extends \WC_Unit_Test_Case {

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

		$this->sut = new FilterData( $query_clauses, $taxonomy_hierarchy_data );

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
	 * @testdox Built-in taxonomy short-name params (categories, tags, brands) are normalised as sets.
	 */
	public function test_taxonomy_set_params_are_normalised(): void {
		$a = $this->normalize( array( 'categories' => 'shirts,hats' ) );
		$b = $this->normalize( array( 'categories' => 'hats,shirts' ) );

		$this->assertSame( $a['categories'], $b['categories'] );
		$this->assertSame( 'hats,shirts', $a['categories'] );

		$a = $this->normalize( array( 'tags' => 'sale,new' ) );
		$b = $this->normalize( array( 'tags' => 'new,sale' ) );
		$this->assertSame( $a['tags'], $b['tags'] );

		$a = $this->normalize( array( 'brands' => 'nike,adidas' ) );
		$b = $this->normalize( array( 'brands' => 'adidas,nike' ) );
		$this->assertSame( $a['brands'], $b['brands'] );
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
