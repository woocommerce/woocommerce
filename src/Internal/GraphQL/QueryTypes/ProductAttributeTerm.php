<?php


namespace Automattic\WooCommerce\Internal\GraphQL\QueryTypes;

use Automattic\WooCommerce\Internal\GraphQL\ApiException;
use Automattic\WooCommerce\Internal\GraphQL\BaseQueryType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class for the ProductAttributeTerm query type.
 */
class ProductAttributeTerm extends BaseQueryType {

	/**
	 * Get the description of the query.
	 *
	 * @return string The description of the query.
	 */
	public function get_description() {
		return 'A term of a product attribute.';
	}

	/**
	 * Get the fields of the query.
	 *
	 * @return array The fields of the query.
	 */
	public function get_fields() {
		return array(
			'id'          => array(
				'type'        => Type::nonNull( Type::id() ),
				'description' => 'Unique identifier for the resource.',
				'resolve'     => function( $resolved_term ) {
					return $resolved_term['term_id'];
				},
			),
			'name'        => array(
				'type'        => Type::nonNull( Type::string() ),
				'description' => 'Term name',
			),
			'slug'        => array(
				'type'        => Type::string(),
				'description' => 'An alphanumeric identifier for the resource unique to its type.',
			),
			'description' => array(
				'type'        => Type::string(),
				'description' => 'HTML description of the resource.',
			),
			'menu_order'  => array(
				'type'        => Type::int(),
				'description' => 'Menu order, used to custom sort the resource.',
			),
			'count'       => array(
				'type'        => Type::int(),
				'description' => 'Number of published products for the resource.',
			),
			'taxonomy'    => array(
				'type'        => Type::string(),
				'description' => 'The taxonomy this term belongs to.',
			),
		);
	}

	/**
	 * Get the arguments for the query.
	 *
	 * @return array The arguments for the query.
	 */
	public function get_args() {
		return array(
			'id' => array(
				'type'        => Type::nonNull( Type::id() ),
				'description' => 'Unique identifier for the resource.',
			),
		);
	}

	/**
	 * Execute the query.
	 *
	 * @param array       $args Arguments for the query.
	 * @param mixed       $context Context passed from the caller, currently unused.
	 * @param ResolveInfo $info The resolve info passed from the GraphQL engine.
	 * @return array The result of the query execution.
	 * @throws ApiException Can't execute the query/error when executing the query.
	 */
	public function resolve( $args, $context, ResolveInfo $info ) {
		$term = get_term( $args['id'], '', ARRAY_A );
		if ( ! is_array( $term ) ) {
			throw new ApiException( "Can't get this term" );
		}

		return $term;
	}
}
