<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\QueryBuilder;
use WC_Unit_Test_Case;
use WP_Block;

/**
 * Tests the query passed from Product Filter blocks to their count provider.
 */
class ProductFilterCollectionCountsTest extends WC_Unit_Test_Case {

	/**
	 * Restore the registered attribute taxonomy, including when fixture creation fails.
	 */
	public function tearDown(): void {
		if ( taxonomy_exists( 'pa_material' ) ) {
			unregister_taxonomy( 'pa_material' );
		}
		parent::tearDown();
	}

	/**
	 * @testdox Should retain saved taxonomy constraints only for explicitly local Product Collections.
	 * @testWith ["product_cat", "local", "or", true, 1]
	 *           ["product_cat", "local", "or", false, 1]
	 *           ["product_cat", "inherited", "or", true, 0]
	 *           ["product_cat", "standalone", "or", true, 0]
	 *           ["product_cat", "generic", "or", true, 0]
	 *           ["product_cat", "generic-without-marker", "or", true, 0]
	 *           ["pa_material", "local", "or", true, 1]
	 *           ["pa_material", "local", "or", false, 1]
	 *           ["pa_material", "local", "and", true, 2]
	 *           ["pa_material", "local", "AND", true, 2]
	 *           ["pa_material", "inherited", "or", true, 0]
	 *           ["pa_material", "standalone", "and", true, 0]
	 *           ["pa_material", "generic", "or", true, 0]
	 *           ["pa_material", "generic-without-marker", "or", true, 0]
	 *
	 * @param string $taxonomy Taxonomy whose counts are requested.
	 * @param string $context_type Collection context to render in.
	 * @param string $query_type Shopper attribute operator.
	 * @param bool   $has_selection Whether the shopper selected a term.
	 * @param int    $expected_clause_count Number of own-taxonomy constraints retained for counts.
	 */
	public function test_count_query_preserves_local_collection_boundary( string $taxonomy, string $context_type, string $query_type, bool $has_selection, int $expected_clause_count ): void {
		$is_attribute = 'pa_material' === $taxonomy;
		$attributes   = array(
			'taxonomy'  => $taxonomy,
			'sortOrder' => 'name-asc',
		);

		if ( $is_attribute ) {
			$attribute_id = wc_create_attribute(
				array(
					'name' => 'Material',
					'slug' => 'material',
				)
			);
			$this->assertIsInt( $attribute_id );
			register_taxonomy( 'pa_material', array( 'product' ) );
			$attributes = array(
				'attributeId' => $attribute_id,
				'queryType'   => $query_type,
				'sortOrder'   => 'name-asc',
				'hideEmpty'   => false,
			);
		}

		$own_param = $is_attribute ? 'filter_material' : 'categories';
		set_query_var( $own_param, $has_selection ? 'shopper-selection' : '' );
		set_query_var( 'query_type_material', $query_type );
		set_query_var( 'tags', 'other-facet' );

		$query_block   = new WP_Block(
			array( 'blockName' => 'core/post-template' ),
			array(
				'query' => array(
					'postType' => 'product',
					'taxQuery' => array( $taxonomy => array( 101, 102 ) ),
				),
			)
		);
		$saved_query   = build_query_vars_from_query_block( $query_block, 1 );
		$saved_clauses = self::get_taxonomy_clauses( $saved_query['tax_query'], $taxonomy );
		$this->assertCount( 1, $saved_clauses, 'WordPress must build the saved taxonomy constraint.' );
		$saved_clause = $saved_clauses[0];
		$this->assertSame( array( 101, 102 ), $saved_clause['terms'] );

		$builder    = new QueryBuilder();
		$query_vars = $builder->get_final_frontend_query( array( 'name' => '' ), $saved_query );

		$query_vars[ $own_param ]          = $has_selection ? 'shopper-selection' : '';
		$query_vars['query_type_material'] = $query_type;
		$query_vars['tags']                = 'other-facet';

		$initial_clauses = self::get_taxonomy_clauses( $query_vars['tax_query'], $taxonomy );
		$this->assertCount( $has_selection ? 2 : 1, $initial_clauses, 'Fixture must combine distinct saved and shopper constraints.' );
		$this->assertContains( $saved_clause, $initial_clauses );

		$query_context = array(
			'postType'                 => 'product',
			'inherit'                  => 'inherited' === $context_type,
			'isProductCollectionBlock' => 'generic' !== $context_type,
		);
		if ( 'generic-without-marker' === $context_type ) {
			unset( $query_context['isProductCollectionBlock'] );
		}
		$context = array( 'filterParams' => array() );
		if ( 'standalone' !== $context_type ) {
			$context['query'] = $query_context;
		}

		global $wp_query;
		$wp_query->query_vars = $query_vars;
		add_filter(
			'query_loop_block_query_vars',
			static function () use ( $query_vars ) {
				return $query_vars;
			},
			PHP_INT_MAX
		);

		$captured_query = null;
		add_filter(
			'woocommerce_pre_product_filter_data',
			static function ( $counts, $filter_type, $count_query ) use ( &$captured_query, $is_attribute ) {
				if ( ( $is_attribute ? 'attribute' : 'taxonomy' ) === $filter_type ) {
					$captured_query = $count_query;
					return array();
				}
				return $counts;
			},
			10,
			3
		);

		$block = new WP_Block(
			array(
				'blockName'    => $is_attribute ? 'woocommerce/product-filter-attribute' : 'woocommerce/product-filter-taxonomy',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			$context
		);
		$block->render();

		$this->assertIsArray( $captured_query, 'Rendering must reach the actual count provider.' );
		$own_clauses = self::get_taxonomy_clauses( $captured_query['tax_query'], $taxonomy );
		$this->assertCount( $expected_clause_count, $own_clauses, 'Local counts retain saved constraints, plus shopper AND selections.' );
		if ( 'local' === $context_type ) {
			$this->assertContains( $saved_clause, $own_clauses, 'The retained clause must be the saved boundary, not the shopper selection.' );
		}
		$other_clauses = self::get_taxonomy_clauses( $captured_query['tax_query'], 'product_tag' );
		$this->assertCount( 1, $other_clauses, 'The other facet selection must remain applied.' );
		$this->assertSame( array( 'other-facet' ), $other_clauses[0]['terms'] );
		if ( $is_attribute && 'and' === strtolower( $query_type ) ) {
			$this->assertSame( 'shopper-selection', $captured_query[ $own_param ], 'AND selections must still constrain counts through QueryClauses.' );
		} else {
			$this->assertArrayNotHasKey( $own_param, $captured_query, 'OR counts must exclude the current shopper selection.' );
		}
	}

	/**
	 * Collect the leaf clauses for one taxonomy without depending on group depth.
	 *
	 * @param array  $queries Taxonomy query groups.
	 * @param string $taxonomy Taxonomy to collect.
	 * @return array
	 */
	private static function get_taxonomy_clauses( array $queries, string $taxonomy ): array {
		$clauses = array();
		foreach ( $queries as $query ) {
			if ( ! is_array( $query ) ) {
				continue;
			}
			if ( ( $query['taxonomy'] ?? null ) === $taxonomy ) {
				$clauses[] = $query;
			} else {
				$clauses = array_merge( $clauses, self::get_taxonomy_clauses( $query, $taxonomy ) );
			}
		}
		return $clauses;
	}
}
