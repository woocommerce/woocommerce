<?php


namespace Automattic\WooCommerce\Internal\GraphQL\QueryTypes;

use Automattic\WooCommerce\Internal\GraphQL\ApiException;
use Automattic\WooCommerce\Internal\GraphQL\BaseQueryType;
use Automattic\WooCommerce\Internal\GraphQL\QueryTypes\ProductAttributeTerms;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Internal\GraphQL\EnumTypes\ProductAttributeOrderBy;

/**
 * Class for the ProductAttribute query type.
 */
class ProductAttribute extends BaseQueryType {

	/**
	 * Get the description of the query.
	 *
	 * @return string The description of the query.
	 */
	public function get_description() {
		return 'A product attribute.';
	}

	/**
	 * Get the fields of the query.
	 *
	 * @return array The fields of the query.
	 */
	public function get_fields() {
		$terms_args = $this->container->get( ProductAttributeTerms::class )->get_args();
		unset( $terms_args['taxonomy'] );

		$order_by_instance        = $this->container->get( ProductAttributeOrderBy::class );
		$order_by_comma_separated = $order_by_instance->get_comma_separated_value_names();
		$order_by_default         = $order_by_instance->get_enum_value_names();

		return array(
			'id'           => array(
				'type'        => Type::nonNull( Type::id() ),
				'description' => 'Unique identifier for the resource.',
			),
			'name'         => array(
				'type'        => Type::nonNull( Type::string() ),
				'description' => 'Attribute name.',
			),
			'slug'         => array(
				'type'        => Type::string(),
				'description' => 'An alphanumeric identifier for the resource unique to its type.',
			),
			'type'         => array(
				'type'        => Type::string(),
				'description' => 'Type of attribute. By default only `select` is supported.',
			),
			'order_by'     => array(
				'type'        => $this->container->get( ProductAttributeOrderBy::class ),
				'description' => 'Default sort order. Options: ' . $order_by_comma_separated . '. Default is `' . $order_by_default . '`.',
			),
			'has_archives' => array(
				'type'        => Type::boolean(),
				'description' => 'Enable/Disable attribute archives. Default is `false`.',
			),
			'terms'        => array(
				'type'        => $this->container->get( ProductAttributeTerms::class ),
				'description' => 'The terms for this attribute.',
				'args'        => $terms_args,
				'resolve'     => function( $resolved_attribute, $args, $context, ResolveInfo $info ) {
					$args['taxonomy'] = $resolved_attribute['slug'];
					return $this->container->get( ProductAttributeTerms::class )->resolve( $args, $context, $info );
				},
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
		$attribute = (array) wc_get_attribute( $args['id'] );
		if ( is_null( $attribute ) ) {
			throw new ApiException( "Can't get this term" );
		}

		if ( array_key_exists( 'terms', $info->getFieldSelection() ) ) {
			$terms              = get_terms(
				$attribute->slug,
				array(
					'hide_empty' => false,
					'fields'     => 'all',
					'count'      => true,
				)
			);
			$attribute['terms'] = array_map(
				function( $term ) {
					return (array) $term;
				},
				$terms
			);
		}

		return $attribute;
	}
}
