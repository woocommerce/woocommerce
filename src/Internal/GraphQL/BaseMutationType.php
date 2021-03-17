<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use Automattic\WooCommerce\Utilities\StringUtil;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Base class for mutation types.
 *
 * To create a new mutation:
 *
 * 1. Add a new class inheriting this one in the "MutationTypes" folder/namespace.
 * 2. Add the class name to "get_object_type_classes" in "RootMutationType".
 * 3. Add the class name to "$provides" in "GraphqlTypesServiceProvider"
 *    so that it can be resolved with the dependency injection container.
 */
abstract class BaseMutationType extends ObjectType {

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
	 * Get the GraphQL name of the mutation type.
	 *
	 * The default name is the one of the class without the "Type" suffix.
	 * Derived classes can override this but they shouldn't without a good reason.
	 *
	 * @return string The GraphQL name of the mutation type.
	 */
	public function get_name() {
		return $this->tryInferName();
	}

	/**
	 * Get the definition of the GraphQL arguments that the mutation will accept for its execution.
	 *
	 * By default only one argument will be accepted, its name will be "input"
	 * and its type will be defined by a class with the same name plus the "InputType" suffix,
	 * derived classes can add more arguments or replace the "input" argument as appropriate.
	 *
	 * See "Field arguments" in https://webonyx.github.io/graphql-php/type-system/object-types/ for the full syntax.
	 *
	 * @return array Definition of the GraphQL arguments that the mutation accepts for its execution.
	 */
	public function get_args() {
		$my_class_name   = StringUtil::class_name_without_namespace( get_class( $this ) );
		$input_type_name = $my_class_name . 'InputType';
		$input_type      = Main::resolve_type( $input_type_name );

		return array(
			'input' => $input_type,
		);
	}

	/**
	 * Get the definition GraphQL fields of the mutation type
	 * (the fields of the object that will be returned when the mutation completes successfully).
	 *
	 * By default only the "id" field of the created object will be returned,
	 * derived classes can override this as needed.
	 *
	 * See "Mutations" in https://webonyx.github.io/graphql-php/type-system/input-types/ for the full syntax.
	 *
	 * @return array The definition fields of the object returned by the mutation.
	 */
	public function get_fields() {
		return array(
			'id' => array(
				'type'        => Type::nonNull( Type::int() ),
				'description' => 'The unique identifier of the resource created.',
			),
		);
	}

	/**
	 * Get the GraphQL description of the mutation type.
	 *
	 * @return string The GraphQL description of the mutation type.
	 */
	abstract public function get_description();

	/**
	 * Execute the operation defined for the mutation.
	 *
	 * The method will receive an array with argument values consistent with the definition returned by "get_args"
	 * (for the default implementation it will only have one key, "input", which will in turn hold an array of data)
	 * and must return an object/array with public properties/keys consistent with the definition returned by "get_fields"
	 * (for the default implementation this will have one single property/key, "id", holding a number).
	 *
	 * Values for individual fields in the output can alternatively be obtained from the generated object if they define
	 * a "resolve" callback, e.g.:
	 *
	 * 'id' => array(
	 *   'type' => Type::nonNull( Type::id() ),
	 *   'description' => 'Unique identifier for the resource created.',
	 *   'resolve' => function( $output_from_execute ) {
	 *      return $output_from_execute['item_id'];
	 *   },
	 * ),
	 *
	 * If the operation can't be completed the method must throw an ApiException. For authorization errors
	 * it can be just "throw ApiException::Unauthorized()".
	 *
	 * @param array       $args Arguments for the mutation operation.
	 * @param mixed       $context Context for the mutation operation, currently unused.
	 * @param ResolveInfo $info The resolve info passed from the GraphQL engine.
	 * @return array The result of the mutation execution.
	 */
	abstract public function execute( $args, $context, ResolveInfo $info);
}
