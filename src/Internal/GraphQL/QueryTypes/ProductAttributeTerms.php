<?php


namespace Automattic\WooCommerce\Internal\GraphQL\QueryTypes;

use Automattic\WooCommerce\Internal\GraphQL\ApiException;
use Automattic\WooCommerce\Internal\GraphQL\BaseQueryListType;
use GraphQL\Type\Definition\Type;

/**
 * Class for the ProductAttributeTerms list query type.
 */
class ProductAttributeTerms extends BaseQueryListType {

	/**
	 * Get the type of the items that the returned list will contain.
	 *
	 * @return string The type of the items that the returned list will contain.
	 */
	public function get_item_type_class_name() {
		return ProductAttributeTerm::class;
	}

	/**
	 * Extra arguments for the query (other than "offset" and "count").
	 *
	 * @return array $extra_args Any extra arguments for the query.
	 */
	public function get_extra_args() {
		return array(
			'taxonomy' => array(
				'type'        => Type::string(),
				'description' => 'Return only the terms for the given taxonomy.',
			),
		);
	}

	/**
	 * Get the total count of available items.
	 *
	 * @param array $extra_args Any extra arguments passed to the query (other than "offset" and "count").
	 * @return int The total count of available items.
	 */
	public function resolve_total_count( $extra_args ) {
		return $this->resolve_terms(
			array(
				'hide_empty' => false,
				'fields'     => 'count',
			),
			$extra_args
		);
	}

	/**
	 * Resolve the query.
	 *
	 * @param int   $offset How many items to skip.
	 * @param int   $count How many items to return.
	 * @param array $extra_args Any extra arguments passed to the query (other than "offset" and "count").
	 * @param array $field_selection The part of the ResolveInfo object wupplied by the GraphQL engine that corresponds to the 'items' object.
	 * @return array The list of items resolved.
	 * @throws ApiException When resolve_terms doesn't retun an array.
	 */
	public function resolve_items( $offset, $count, $extra_args, $field_selection ) {
		$result = $this->resolve_terms(
			array(
				'hide_empty' => false,
				'fields'     => 'all',
				'offset'     => $offset,
				'number'     => $count,
			),
			$extra_args
		);
		if ( ! is_array( $result ) ) {
			throw new ApiException( "Can't get terms" );
		}
		return array_map(
			function( $term ) {
				return (array) $term;
			},
			$result
		);
	}

	/**
	 * Auxiliary method to fetch the terms according to the supplied arguments.
	 *
	 * @param array $args_for_get_terms Arguments for the get_terms call.
	 * @param array $extra_args Extra arguments supplied for the query.
	 * @return mixed The result of executing get_terms.
	 * @throws ApiException When get_terms returns an error.
	 */
	private function resolve_terms( $args_for_get_terms, $extra_args ) {
		if ( isset( $extra_args['taxonomy'] ) ) {
			$args_for_get_terms['taxonomy'] = $extra_args['taxonomy'];
		}

		$result = get_terms( $args_for_get_terms );
		if ( $result instanceof \WP_Error ) {
			throw new ApiException( $result->get_error_message() );
		}

		return $result;
	}
}
