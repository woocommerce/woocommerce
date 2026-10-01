<?php
/**
 * AttributeCountQueryGenerator interface file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Optional attribute-count query capability for product query generators.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 * @since 11.3.0
 */
interface AttributeCountQueryGenerator extends QueryClausesGenerator {
	/**
	 * Build a count query matching this generator's attribute eligibility.
	 *
	 * @param array  $query_vars  The WP_Query arguments.
	 * @param string $taxonomy    Attribute taxonomy name.
	 * @param string $product_ids_placeholder Trusted SQL placeholder for eligible product IDs; embed it unchanged.
	 * @return string|null SQL returning term_count and term_count_id columns, or null for taxonomy counting.
	 */
	public function get_attribute_count_query( array $query_vars, string $taxonomy, string $product_ids_placeholder ): ?string;
}
