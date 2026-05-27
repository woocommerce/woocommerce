<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\TaxQuery;
use WC_Unit_Test_Case;

/**
 * Tests for the TaxQuery class.
 */
class TaxQueryTest extends WC_Unit_Test_Case {

	/**
	 * Data provider.
	 */
	public static function provide_get_sql_for_clause_scenarios(): array {
		global $wpdb;

		$term_id = get_term_by( 'slug', 'featured', 'product_visibility' )->term_taxonomy_id;

		return array(
			array(
				'clause'   => array(
					'taxonomy'         => 'product_visibility',
					'field'            => 'term_taxonomy_id',
					'terms'            => array( $term_id ),
					'operator'         => 'IN',
					'include_children' => false,
				),
				'expected' => array(
					'join'  => array( '' ),
					'where' => array( "{$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( $term_id ) )" ),
				),
			),
			array(
				'clause'   => array(
					'taxonomy'         => 'product_visibility',
					'field'            => 'term_taxonomy_id',
					'terms'            => array(),
					'operator'         => 'IN',
					'include_children' => false,
				),
				'expected' => array(
					'join'  => array( '' ),
					'where' => array( '0 = 1' ),
				),
			),
		);
	}

	/**
	 * @dataProvider provide_get_sql_for_clause_scenarios
	 *
	 * @param array $clause   Input clause.
	 * @param array $expected Expected result.
	 */
	public function test_get_sql_for_clause( array $clause, array $expected ): void {
		$query                    = new TaxQuery( array() );
		$query->primary_table     = 'wp_posts';
		$query->primary_id_column = 'ID';

		$this->assertSame( $expected, $query->get_sql_for_clause( $clause, array() ) );
	}
}
