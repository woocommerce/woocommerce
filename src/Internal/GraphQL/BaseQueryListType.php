<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Base class for queries that return a list of items.
 *
 * The arguments for the query will always be "offset" and "count" (more can be added via "get_extra_args")
 * and the returned object will always have two fields: "total_count" (a number)
 * and "items" (a list of items whose fields will be defined by the type specified by "get_item_type_class_name").
 *
 * Derived classes should be a name in plural, and the type name returned by "get_item_type_class_name"
 * should be the same name in singular, unless there's a good reason to follow a different approach
 * in some particular case.
 *
 * To create a new list query, proceed as when inheriting from BaseQueryType.
 */
abstract class BaseQueryListType extends BaseQueryType {

	/**
	 * Maximum amount of results that will be returned if no "count" argument is supplied.
	 */
	const MAX_RESULTS_PER_QUERY = 100;

	/**
	 * Holds the instance of the class defining the type of the objects returned.
	 *
	 * @var mixed
	 */
	protected $object_type_instance;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		$this->container = wc_get_container();

		$this->object_type_instance = $this->container->get( $this->get_item_type_class_name() );
		parent::__construct( $this->get_args() );
	}

	/**
	 * Get the GraphQL description of the query.
	 *
	 * By default it will be "A collection of (object type name)", derived classes can override this if necesessary.
	 *
	 * @return string The GraphQL description of the query.
	 */
	public function get_description() {
		$object_type_name = $this->container->get( $this->get_item_type_class_name() )->get_name();
		return 'A collection of ' . $object_type_name . '.';
	}

	/**
	 * Get the definition GraphQL fields of the query type
	 * (the fields of the object that will be returned when the query completes successfully).
	 * These are always "total_count" (a number) and "items" (a list of objects whose fields will be defined
	 * by the object specified by "get_item_type_class_name").
	 *
	 * See "get_fields" in "BaseQueryType" for an example.
	 * See "Field configuration options" in https://webonyx.github.io/graphql-php/type-system/object-types/ for the full syntax.
	 *
	 * @return array[]
	 */
	public function get_fields() {
		return array(
			'total_count' => array(
				'type'        => Type::int(),
				'description' => 'The total count.',
			),
			'items'       => array(
				'type'        => Type::listOf( $this->object_type_instance ),
				'description' => 'The items themselves.',
			),
		);
	}

	/**
	 * Get the definition of the GraphQL arguments that the query will accept for its resolution.
	 *
	 * The arguments will always include "offset" (how many items to skip from the data store before
	 * including items in the output) and "count" (the maximum number of items to return).
	 * Extra arguments can be added via "get_extra_args".
	 *
	 * @return array Definition of the GraphQL arguments that the query accepts for its resolution.
	 */
	public function get_args() {
		$args = array(
			'offset' => array(
				'type'         => Type::int(),
				'description'  => 'Specifies how many items to skip from the data store before including items in the output.',
				'defaultValue' => 0,
			),
			'count'  => array(
				'type'         => Type::int(),
				'description'  => 'Specifies the maximum number of items to return.',
				'defaultValue' => self::MAX_RESULTS_PER_QUERY,
			),
		);

		return array_merge( $args, $this->get_extra_args() );
	}

	/**
	 * Specifies any extra arguments that the query will accept besides "offset" and "count",
	 * none by default.
	 *
	 * See "get_args" in "BaseQueryType" for an example.
	 * See "Field arguments" in https://webonyx.github.io/graphql-php/type-system/object-types/ for the full syntax.
	 *
	 * @return array Extra arguments that the query will accept besides "offset" and "count".
	 */
	public function get_extra_args() {
		return array();
	}

	/**
	 * Resolve the query defined by the type.
	 *
	 * The actual resolution will be delegated to the "resolve_total_count" and "resolve_items" methods.
	 *
	 * @param array       $args Arguments for the query operation.
	 * @param mixed       $context Context for the query operation, currently unused.
	 * @param ResolveInfo $info The resolve info passed from the GraphQL engine.
	 * @return array The result of the query execution.
	 */
	public function resolve( $args, $context, ResolveInfo $info ) {
		$extra_args = $args;
		unset( $extra_args['offset'] );
		unset( $extra_args['count'] );

		$result = array();

		$field_selection = $info->getFieldSelection();
		if ( array_key_exists( 'total_count', $field_selection ) ) {
			$result['total_count'] = $this->resolve_total_count( $extra_args );
		}
		if ( array_key_exists( 'items', $field_selection ) ) {
			$result['items'] = $this->resolve_items(
				$args['offset'],
				$args['count'],
				$extra_args,
				$field_selection['items']
			);
		}

		return $result;
	}

	/**
	 * Get the name of the class defining the type of the items that the query will return
	 * (must be a query type class implementing BaseQueryType).
	 *
	 * That name should be the same as the class but in singular, unless there's a good reason
	 * to follow a different approach in some particular case.
	 *
	 * @return string Full (namespaced) name of the class defining the type of the items that the query will return.
	 */
	abstract public function get_item_type_class_name();

	/**
	 * Get the count of existing items of the type defined by "get_item_type_class_name".
	 * This method will be executed only if the query requests the "total_count" field.
	 *
	 * The method will receive an array with argument values consistent with the definition returned by "get_extra_args"
	 * (for the default implementation it will just be an empty array) and must return a count of items according
	 * to these arguments (or the absolute total of existing items if there are none).
	 *
	 * @param array $extra_args Any arguments for counting the existing items.
	 * @return int The count of existing items.
	 */
	abstract public function resolve_total_count( $extra_args );

	/**
	 * Resolve the query defined by the type.
	 * This method will be executed only if the query requests the "items" field.
	 *
	 * This works pretty much as BaseObjectType::resolve, except that it returns an array of objects/arrays
	 * (of the type defined by "get_item_type_class_name") according to the offset, count and extra arguments
	 * supplied.
	 *
	 * @param int   $offset How many items to skip from the data store before including items in the output.
	 * @param int   $count Maximum amount of items to return.
	 * @param array $extra_args Any extra arguments for the query, consistent with the definition specified by "get_extra_args".
	 * @param array $field_selection The result from executing $info->getFieldSelection()['items'] in "resolve".
	 * @return array An array of objects/arrays, each representing an item defined by the type specified by "get_item_type_class_name".
	 */
	abstract public function resolve_items( $offset, $count, $extra_args, $field_selection );
}
