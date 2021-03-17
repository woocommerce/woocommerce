<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use Automattic\WooCommerce\Utilities\StringUtil;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Base class for the GraphQL input types for mutations.
 *
 * To create a new input type:
 *
 * 1. Add a new class inheriting this one in the "InputTypes" folder/namespace.
 *    The name of the class should be the same of the mutation plus "InputType".
 * 2. Add the class name to "$provides" in "GraphqlTypesServiceProvider"
 *    so that it can be resolved with the dependency injection container.
 */
abstract class BaseInputType extends InputObjectType {

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
		);

		parent::__construct( $config );
	}

	/**
	 * Get the GraphQL name of the input type.
	 *
	 * The default name is the one of the class without the "Type" suffix.
	 * Derived classes can override this but they shouldn't without a good reason.
	 *
	 * @return string The GraphQL name of the input type.
	 */
	public function get_name() {
		return $this->tryInferName();
	}

	/**
	 * Get the GraphQL description of the input type.
	 *
	 * By default the name is "Input type for the (class name without InputType suffix) mutation."
	 * Derived classes can override this if necessary.
	 *
	 * @return string The GraphQL description of the enumeration type.
	 */
	public function get_description() {
		$my_class_name = StringUtil::class_name_without_namespace( get_class( $this ) );
		$mutation_name = preg_replace( '~InputType$~', '', $my_class_name );
		return "Input type for the {$mutation_name} mutation.";
	}

	/**
	 * Get the fields of the input type.
	 *
	 * Example:
	 *
	 * array(
	 *   'name' => array(
	 *     'type' => Type::nonNull( Type::string() ),
	 *     'description' => 'Name of the object being created.',
	 *   ),
	 *   'kind' => array(
	 *     'type' => $this->container->get( KindEnumType::class ),
	 *     'description' => 'Kind of the object being created. Possible types: ' . $this->container->get( KindEnumType::class )->get_enum_value_names(),
	 *   )
	 * );
	 *
	 * See "Input object type" in https://webonyx.github.io/graphql-php/type-system/input-types/ for the full syntax.
	 *
	 * @return mixed
	 */
	abstract public function get_fields();
}
