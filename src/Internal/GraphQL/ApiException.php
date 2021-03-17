<?php


namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Error\ClientAware;

/**
 * ApiException class.
 *
 * Queries and mutations should throw an exception of this type when something prevents the completion of the
 * operation. The supplied message will be returned in the response, and the HTTP status of the response
 * will depend on the specified category (note that the category must be one of the keys of
 * Main::$status_codes_by_error_category)
 *
 * For authorization errors the query/mutation code can do just "throw ApiException::Unauthorized()".
 *
 * @package Automattic\WooCommerce\Internal\GraphQL
 */
class ApiException extends \Exception implements ClientAware {

	/**
	 * The category of the error.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * ApiException constructor.
	 *
	 * @param string         $message The error message to be included in the response.
	 * @param string         $category The error category. Must be one of the keys of Main::$status_codes_by_error_category.
	 * @param int            $code The error code, unused by the GraphQL infrastructure.
	 * @param Throwable|null $previous The previous exception, unused by the GraphQL infrastructure.
	 */
	public function __construct( $message = '', $category = 'request', $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->category = $category;
	}

	/**
	 * This must be true for the error message to be included in the response.
	 *
	 * @return bool True, so the error message will be included in the response.
	 */
	public function isClientSafe() {
		return true;
	}

	/**
	 * Returns the category of the error.
	 *
	 * @return string The category of the error.
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * Returns a new ApiException object with the "authorization" category.
	 *
	 * @param null|string $message The error message to be included in the response.
	 * @return ApiException The new instance of the exception.
	 */
	public static function Unauthorized( $message = null ) {
		return new ApiException( is_null( $message ) ? "You don't have permission for the requested operation" : $message, 'authorization' );
	}
}
