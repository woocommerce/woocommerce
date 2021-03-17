<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Base class for query types.
 *
 * To create a new query:
 *
 * 1. Add a new class inheriting this one in the "QueryTypes" folder/namespace.
 * 2. Add the class name to "get_object_type_classes" in "RootQueryType".
 * 3. Add the class name to "$provides" in "GraphqlTypesServiceProvider"
 *    so that it can be resolved with the dependency injection container.
 */
abstract class BaseQueryType extends ObjectType {

	/**
	 * An instance of the dependency injection container.
	 * Can be used by derived classes.
	 *
	 * @var \Psr\Container\ContainerInterface
	 */
	protected $container;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		$this->container = wc_get_container();

		$config = array(
			'name'        => $this->get_name(),
			'description' => $this->get_description(),
			'fields'      => $this->get_fields(),
			'args'        => $this->get_args(),
		);

		parent::__construct( $config );
	}

	/**
	 * Get the GraphQL name of the query type.
	 *
	 * The default name is the one of the class without the "Type" suffix.
	 * Derived classes can override this but they shouldn't without a good reason.
	 *
	 * @return string The GraphQL name of the query type.
	 */
	public function get_name() {
		return $this->tryInferName();
	}

	/**
	 * Get the definition of the GraphQL arguments that the query will accept for its resolution.
	 * By default no arguments will be accepted.
	 *
	 * Example:
	 *
	 * array(
	 *   'id' => array(
	 *     'type' => Type::nonNull( Type::id() ),
	 *     'description' => 'Unique identifier of the resource to be obtained.',
	 *   )
	 * );
	 *
	 * See "Field arguments" in https://webonyx.github.io/graphql-php/type-system/object-types/ for the full syntax.
	 *
	 * @return array Definition of the GraphQL arguments that the query accepts for its resolution.
	 */
	public function get_args() {
		return array();
	}

	/**
	 * Get the definition GraphQL fields of the query type
	 * (the fields of the object that will be returned when the query completes successfully).
	 *
	 * Example:
	 *
	 * array(
	 *   'id' => array(
	 *     'type' => Type::nonNull( Type::id() ),
	 *     'description' => 'Unique identifier of the resource.',
	 *   ),
	 *   'name' => array(
	 *     'type' => Type::nonNull( Type::string() ),
	 *     'description' => 'Name of the resource.',
	 *   ),
	 * );
	 *
	 * See "Field configuration options" in https://webonyx.github.io/graphql-php/type-system/object-types/ for the full syntax.
	 *
	 * @return array The definition fields of the object returned by the query.
	 */
	abstract public function get_fields();

	/**
	 * Get the GraphQL description of the query type.
	 *
	 * @return string The GraphQL description of the query type.
	 */
	abstract public function get_description();

	/**
	 * Resolve the query defined by the type.
	 *
	 * The method will receive an array with argument values consistent with the definition returned by "get_args"
	 * (for the default implementation it will just be an empty array)
	 * and must return an object/array with public properties/keys consistent with the definition returned by "get_fields".
	 *
	 * Values for individual fields in the output can alternatively be obtained from the generated object if they define
	 * a "resolve" callback, e.g.:
	 *
	 * 'id' => array(
	 *   'type' => Type::nonNull( Type::id() ),
	 *   'description' => 'Unique identifier of the resource.',
	 *   'resolve' => function( $output_from_resolve ) {
	 *      return $output_from_resolve['item_id'];
	 *   },
	 * ),
	 *
	 * If the query can't be completed the method must throw an ApiException. For authorization errors
	 * it can be just "throw ApiException::Unauthorized()".
	 *
	 * @param array       $args Arguments for the query operation.
	 * @param mixed       $context Context for the query operation, currently unused.
	 * @param ResolveInfo $info The resolve info passed from the GraphQL engine.
	 * @return array The result of the query execution.
	 */
	abstract public function resolve( $args, $context, ResolveInfo $info);
}
