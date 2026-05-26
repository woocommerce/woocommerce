<?php
/**
 * AbstractRoute class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute as StoreApiAbstractRoute;
use WP_Error;
use WP_REST_Request;

/**
 * Base class for POS Store API routes.
 *
 * POS routes are thin wrappers around a Store API route handler ("the
 * delegate"). The wrapper owns:
 *
 *   - Authorization (a POS capability check replacing the Store API's
 *     `__return_true` permission callback).
 *   - Optional request-time pre-flight (e.g. swapping in the customer user
 *     before delegating, when the route accepts a customer_id).
 *
 * The wrapper does NOT own:
 *
 *   - Cart/checkout pipeline logic (lives in the delegate, runs unchanged).
 *   - Extension hooks (fire from the pipeline regardless of which route
 *     invoked it — the value proposition of the wrapper-delegation pattern).
 *
 * Sub-classes typically need only a constructor that supplies the right
 * Store API delegate and may override {@see check_permission} or
 * {@see get_response} for route-specific behaviour.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
abstract class AbstractRoute {

	/**
	 * The Store API route handler we delegate to.
	 *
	 * @var StoreApiAbstractRoute
	 */
	protected StoreApiAbstractRoute $delegate;

	/**
	 * Capability required for any POS request. Override per-route if needed.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Constructor.
	 *
	 * @param StoreApiAbstractRoute $delegate Store API route handler this POS route wraps.
	 */
	public function __construct( StoreApiAbstractRoute $delegate ) {
		$this->delegate = $delegate;
	}

	/**
	 * Path under the POS namespace, e.g. '/cart/add-item'.
	 *
	 * Inherits the delegate's path so POS surfaces the same shape as web.
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->delegate->get_path();
	}

	/**
	 * Endpoint arguments.
	 *
	 * Starts from the delegate's args (so request schema, sanitization etc.
	 * stay in sync with web) and substitutes the POS permission and callback.
	 *
	 * @return array
	 */
	public function get_args(): array {
		$endpoints = $this->delegate->get_args();

		// The Store API uses a single endpoint definition per route at index 0,
		// but the array shape supports multiple endpoints — handle either.
		$is_list = isset( $endpoints[0] ) && is_array( $endpoints[0] );

		if ( $is_list ) {
			foreach ( $endpoints as &$endpoint ) {
				$endpoint['permission_callback'] = array( $this, 'check_permission' );
				$endpoint['callback']            = array( $this, 'get_response' );
			}
			unset( $endpoint );
		} else {
			$endpoints['permission_callback'] = array( $this, 'check_permission' );
			$endpoints['callback']            = array( $this, 'get_response' );
		}

		return $endpoints;
	}

	/**
	 * Default POS permission check.
	 *
	 * Override per-route for finer-grained capabilities; this default keeps the
	 * common case (manage_woocommerce) in one place.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( static::REQUIRED_CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'woocommerce_pos_rest_forbidden',
			__( 'Sorry, you are not allowed to access POS resources.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Default response: delegate to the Store API route handler.
	 *
	 * Subclasses can override to do POS-specific pre/post processing around
	 * the delegation (e.g. swap the customer user before delegating).
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function get_response( WP_REST_Request $request ) {
		return $this->delegate->get_response( $request );
	}
}
