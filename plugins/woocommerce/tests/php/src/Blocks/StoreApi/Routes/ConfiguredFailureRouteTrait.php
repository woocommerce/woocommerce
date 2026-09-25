<?php
/**
 * Shared fixture behavior for Store API routes that fail on demand.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Lets a fixture route throw a configured failure from its request handler.
 */
trait ConfiguredFailureRouteTrait {
	/**
	 * Failure thrown during route dispatch, or null to succeed.
	 *
	 * @var \Throwable|null
	 */
	private $failure;

	/**
	 * Create the fixture without production constructor dependencies.
	 *
	 * @param \Throwable|null $failure Failure thrown during route dispatch, or null to succeed.
	 */
	public function __construct( ?\Throwable $failure = null ) {
		$this->failure = $failure;
	}

	/**
	 * @return string
	 */
	public function get_path() {
		return '/configured-failure-fixture';
	}

	/**
	 * @return array
	 */
	public function get_args() {
		return array();
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	protected function requires_nonce( WP_REST_Request $request ) {
		unset( $request ); // Avoid parameter not used PHPCS errors.
		return false;
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 * @throws \Throwable The configured fixture failure, when one is set.
	 */
	protected function get_response_by_request_method( WP_REST_Request $request ) {
		unset( $request ); // Avoid parameter not used PHPCS errors.
		if ( $this->failure ) {
			throw $this->failure;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * @param WP_REST_Response $response Response object.
	 * @return WP_REST_Response
	 */
	protected function add_response_headers( WP_REST_Response $response ) {
		return $response;
	}
}
