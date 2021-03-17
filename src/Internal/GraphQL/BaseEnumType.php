<?php


namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Type\Definition\EnumType;

/**
 * Base class for the GraphQL enumeration types.
 *
  To create a new enumeration type:
 *
 * 1. Add a new class inheriting this one in the "EnumerationTypes" folder/namespace.
 *    The name of the class should be the same of the mutation plus "InputType".
 * 2. Add the class name to "$provides" in "GraphqlTypesServiceProvider"
 *    so that it can be resolved with the dependency injection container.
 */
abstract class BaseEnumType extends EnumType {

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
			'values'      => $this->get_enum_values(),
		);

		parent::__construct( $config );
	}

	/**
	 * Get the GraphQL name of the type.
	 *
	 * The default name is the one of the class without the "Type" suffix.
	 * Derived classes can override this but they shouldn't without a good reason.
	 *
	 * @return string The GraphQL name of the type.
	 */
	public function get_name() {
		return $this->tryInferName();
	}

	/**
	 * Returns a comma separated list of the value names, each enclosed in "`".
	 * Derived classes can use this for field descriptions.
	 *
	 * @return string A comma separated list of the value names, each enclosed in "`".
	 */
	public function get_comma_separated_value_names() {
		return '`' . implode( '`, `', $this->get_enum_value_names() ) . '`';
	}

	/**
	 * Get the names of the enumeration values.
	 *
	 * @return array The names of the enumeration values.
	 */
	public function get_enum_value_names() {
		$enum_values = $this->get_enum_values();
		$names       = array();

		foreach ( $enum_values as $key => $value ) {
			if ( is_string( $value ) ) {
				$names[] = $value;
			} elseif ( is_string( $key ) ) {
				$names[] = $key;
			} else {
				$names[] = $value['name'];
			}
		}

		return $names;
	}

	/**
	 * Get the GraphQL description of the enumeration type.
	 *
	 * @return string The GraphQL description of the enumeration type.
	 */
	abstract public function get_description();

	/**
	 * Get the definition of the enumeration values.
	 *
	 * Example:
	 *
	 * array(
	 *   'value_1' => array(
	 *      'description' => 'The meaning of the first value.',
	 *    ),
	 *   'value_2' => array(
	 *      'description' => 'The meaning of the second value.',
	 * );
	 *
	 * See https://webonyx.github.io/graphql-php/type-system/enum-types/ for the full syntax.
	 *
	 * @return array The definition of the enumeration values.
	 */
	abstract public function get_enum_values();
}
