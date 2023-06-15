<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes;

/**
 * Represents the description of a REST API controller class or endpoint.
 */
#[\Attribute]
class DescriptionAttribute {

	/**
	 * The description of a REST API controller class or endpoint.
	 * If it starts with "::", it's the name of a public static method
	 * in the class that will return a string with the description.
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param string $description Description of the class or endpoint, or "::" followed by the name of a public static method in the class that will return the description.
	 */
	public function __construct( string $description ) {
		$this->description = $description;
	}
}
