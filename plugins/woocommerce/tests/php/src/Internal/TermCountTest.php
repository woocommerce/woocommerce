<?php
/**
 * TermCount tests.
 *
 * @package WooCommerce\Tests\Internal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\TermCount;
use RuntimeException;
use WC_Helper_Product;
use WC_Product;
use WC_Unit_Test_Case;

/**
 * Tests for TermCount.
 */
class TermCountTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var TermCount
	 */
	private TermCount $sut;

	/**
	 * Product IDs created by a test.
	 *
	 * @var list<int>
	 */
	private array $product_ids = array();

	/**
	 * Terms created by a test, in creation order.
	 *
	 * @var list<array{taxonomy: string, term_id: int}>
	 */
	private array $terms = array();

	/**
	 * Original hide-out-of-stock option value.
	 *
	 * @var mixed
	 */
	private $original_hide_out_of_stock_items;

	/**
	 * Sets up the test fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut                              = wc_get_container()->get( TermCount::class );
		$this->original_hide_out_of_stock_items = get_option( 'woocommerce_hide_out_of_stock_items', false );
	}

	/**
	 * Cleans up products, terms, options, and service state.
	 */
	public function tearDown(): void {
		foreach ( $this->product_ids as $product_id ) {
			WC_Helper_Product::delete_product( $product_id );
		}

		foreach ( array_reverse( $this->terms ) as $term ) {
			wp_delete_term( $term['term_id'], $term['taxonomy'] );
		}

		if ( false === $this->original_hide_out_of_stock_items ) {
			delete_option( 'woocommerce_hide_out_of_stock_items' );
		} else {
			update_option( 'woocommerce_hide_out_of_stock_items', $this->original_hide_out_of_stock_items );
		}

		delete_transient( 'wc_term_counts' );

		parent::tearDown();
	}

	/**
	 * @testdox Removing an attached out-of-stock relationship refreshes category, tag, brand, and ancestor counts when hidden out-of-stock products are enabled.
	 */
	public function test_removing_out_of_stock_visibility_recounts_all_product_taxonomies(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$fixture = $this->create_counted_product_fixture();
		$this->set_product_visibility( $fixture['product_id'], ProductStockStatus::OUT_OF_STOCK );

		$this->assert_fixture_counts( $fixture, 0 );

		wp_remove_object_terms( $fixture['product_id'], ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );

		$this->assert_fixture_counts( $fixture, 1 );
	}

	/**
	 * @testdox Removing an irrelevant product visibility term does not recount product terms.
	 */
	public function test_removing_irrelevant_visibility_term_does_not_recount(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$fixture = $this->create_counted_product_fixture();
		$this->set_product_visibility( $fixture['product_id'], 'featured' );

		$recount_attempts = $this->count_recount_attempts(
			static function () use ( $fixture ): void {
				wp_remove_object_terms( $fixture['product_id'], 'featured', 'product_visibility' );
			}
		);

		$this->assertSame( 0, $recount_attempts, 'An irrelevant visibility term should not trigger a recount.' );
	}

	/**
	 * @testdox Removing out-of-stock does not recount product terms when hidden out-of-stock products are disabled.
	 */
	public function test_removing_out_of_stock_does_not_recount_when_hiding_is_disabled(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$fixture = $this->create_counted_product_fixture();
		$this->set_product_visibility( $fixture['product_id'], ProductStockStatus::OUT_OF_STOCK );

		$recount_attempts = $this->count_recount_attempts(
			static function () use ( $fixture ): void {
				wp_remove_object_terms( $fixture['product_id'], ProductStockStatus::OUT_OF_STOCK, 'product_visibility' );
			}
		);

		$this->assertSame( 0, $recount_attempts, 'Out-of-stock should not affect counts when hiding it is disabled.' );
	}

	/**
	 * @testdox Relationship deletions in unrelated taxonomies do not recount product terms.
	 */
	public function test_unrelated_taxonomy_relationship_deletion_does_not_recount(): void {
		$fixture = $this->create_counted_product_fixture();

		$recount_attempts = $this->count_recount_attempts(
			function () use ( $fixture ): void {
				$this->sut->handle_deleted_term_relationships( $fixture['product_id'], array( $fixture['tag_tt_id'] ), 'product_tag' );
			}
		);

		$this->assertSame( 0, $recount_attempts, 'An unrelated taxonomy should not trigger a WooCommerce recount.' );
	}

	/**
	 * Creates a published product and records it for cleanup.
	 *
	 * @param array<string, mixed> $props Product properties.
	 * @return WC_Product
	 */
	private function create_product( array $props = array() ): WC_Product {
		$product             = WC_Helper_Product::create_simple_product( true, $props );
		$this->product_ids[] = $product->get_id();

		return $product;
	}

	/**
	 * Creates a term and records it for cleanup.
	 *
	 * @param string               $name     Term name.
	 * @param string               $taxonomy Taxonomy slug.
	 * @param array<string, mixed> $args     Term arguments.
	 * @return array{term_id: int, term_taxonomy_id: int}
	 */
	private function create_term( string $name, string $taxonomy, array $args = array() ): array {
		$term = wp_insert_term( $name . ' ' . wp_generate_uuid4(), $taxonomy, $args );

		if ( ! is_array( $term ) ) {
			throw new RuntimeException( 'Failed to create a term for the test fixture.' );
		}

		$this->terms[] = array(
			'taxonomy' => $taxonomy,
			'term_id'  => (int) $term['term_id'],
		);

		return array(
			'term_id'          => (int) $term['term_id'],
			'term_taxonomy_id' => (int) $term['term_taxonomy_id'],
		);
	}

	/**
	 * Creates a product assigned to category, tag, and brand hierarchies.
	 *
	 * @return array{product_id: int, category_parent_id: int, category_id: int, tag_id: int, tag_tt_id: int, brand_parent_id: int, brand_id: int}
	 */
	private function create_counted_product_fixture(): array {
		$category_parent = $this->create_term( 'Category parent', 'product_cat' );
		$category        = $this->create_term( 'Category', 'product_cat', array( 'parent' => $category_parent['term_id'] ) );
		$tag             = $this->create_term( 'Tag', 'product_tag' );
		$brand_parent    = $this->create_term( 'Brand parent', 'product_brand' );
		$brand           = $this->create_term( 'Brand', 'product_brand', array( 'parent' => $brand_parent['term_id'] ) );
		$product         = $this->create_product(
			array(
				'category_ids' => array( $category['term_id'] ),
				'tag_ids'      => array( $tag['term_id'] ),
			)
		);
		wp_set_object_terms( $product->get_id(), array( $brand['term_id'] ), 'product_brand' );
		wc_recount_all_terms( false );

		return array(
			'product_id'         => $product->get_id(),
			'category_parent_id' => $category_parent['term_id'],
			'category_id'        => $category['term_id'],
			'tag_id'             => $tag['term_id'],
			'tag_tt_id'          => $tag['term_taxonomy_id'],
			'brand_parent_id'    => $brand_parent['term_id'],
			'brand_id'           => $brand['term_id'],
		);
	}

	/**
	 * Assigns a product visibility term and establishes current counts.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $term_slug  Product visibility term slug.
	 */
	private function set_product_visibility( int $product_id, string $term_slug ): void {
		wp_set_object_terms( $product_id, $term_slug, 'product_visibility' );
		wc_recount_all_terms( false );
	}

	/**
	 * Asserts all term counts in a product fixture.
	 *
	 * @param array $fixture  Product fixture.
	 * @param int   $expected Expected count.
	 * @phpstan-param array{category_parent_id: int, category_id: int, tag_id: int, brand_parent_id: int, brand_id: int} $fixture
	 */
	private function assert_fixture_counts( array $fixture, int $expected ): void {
		$this->assertSame( $expected, $this->get_product_count( $fixture['category_parent_id'], 'product_cat' ) );
		$this->assertSame( $expected, $this->get_product_count( $fixture['category_id'], 'product_cat' ) );
		$this->assertSame( $expected, $this->get_product_count( $fixture['tag_id'], 'product_tag' ) );
		$this->assertSame( $expected, $this->get_product_count( $fixture['brand_parent_id'], 'product_brand' ) );
		$this->assertSame( $expected, $this->get_product_count( $fixture['brand_id'], 'product_brand' ) );
	}

	/**
	 * Gets a WooCommerce product count from term meta.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	private function get_product_count( int $term_id, string $taxonomy ): int {
		return (int) get_term_meta( $term_id, 'product_count_' . $taxonomy, true );
	}

	/**
	 * Counts WooCommerce recount attempts made by an operation.
	 *
	 * @param callable(): void $operation Operation to run.
	 * @return int
	 */
	private function count_recount_attempts( callable $operation ): int {
		$recount_attempts = 0;
		$track_recounts   = static function ( $should_recount ) use ( &$recount_attempts ) {
			++$recount_attempts;
			return $should_recount;
		};
		add_filter( 'woocommerce_product_recount_terms', $track_recounts );

		try {
			$operation();
		} finally {
			remove_filter( 'woocommerce_product_recount_terms', $track_recounts );
		}

		return $recount_attempts;
	}
}
