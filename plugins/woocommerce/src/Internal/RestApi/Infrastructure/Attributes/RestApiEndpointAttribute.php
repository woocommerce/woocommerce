<?php
/**
 * RestApiEndpointAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes;

/**
 * Marks a public static method in a controller class as a REST API endpoint.
 */
#[\Attribute]
class RestApiEndpointAttribute
{
	/**
	 * The HTTP verb, or a comma-separated list of HTTP verbs, that the endpoint supports.
	 *
	 * @var string
	 */
	public $verb;

	/**
	 * The endpoint route. The actual route will be formed by  prepending it with the root route specified
	 * in the "root_path" argument of the RestApiController attribute applied to the class.
	 *
	 * @var string
	 */
	public $route;

	/**
	 * @param string $verb The HTTP verb, or a comma-separated list of HTTP verbs, that the endpoint supports.
	 * @param string $route The endpoint route. The actual route will be formed by  prepending it with the root route specified in the "root_path" argument of the RestApiController attribute applied to the class.
	 */
	public function __construct(string $verb, string $route)
	{
		$this->verb = $verb;
		$this->route = $route;
	}
}
