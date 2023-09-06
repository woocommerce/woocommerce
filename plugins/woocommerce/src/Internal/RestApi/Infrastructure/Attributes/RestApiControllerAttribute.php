<?php
/**
 * RestApiControllerAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes;

/**
 * Attribute that marks a class as a REST API controller.
 */
#[\Attribute]
class RestApiControllerAttribute {

	/**
	 * Root for the routes of the endpoints in the class.
	 * This will be prepended to the routes declared by RestApiEndpoint attributes on each method.
	 *
	 * @var string
	 */
	public string $root_path;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param string|null $root_path Root for the routes of the endpoints in the class.
	 */
	public function __construct( ?string $root_path ) {
		$this->root_path = $root_path ?? '';
	}
}
