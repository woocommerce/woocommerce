<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\v1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * Order class.
 */
class Order extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'order';

	/**
	 * Order controller class instance.
	 *
	 * @var OrderController
	 */
	protected $order_controller;

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema Controller instance.
	 * @param AbstractSchema   $schema Schema class for this route.
	 */
	public function __construct( SchemaController $schema_controller, AbstractSchema $schema ) {
		parent::__construct( $schema_controller, $schema );

		$this->order_controller = new OrderController();
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/order/(?P<id>[\d]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
				'args'                => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Check if authorized to get the order.
	 *
	 * @throws RouteException If the order is not found or the order key is invalid.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return boolean|WP_Error
	 */
	public function is_authorized( \WP_REST_Request $request ) {
		$order_id      = absint( $request['id'] );
		$order_key     = wc_clean( wp_unslash( $request->get_param( 'key' ) ) );
		$billing_email = wc_clean( wp_unslash( $request->get_param( 'billing_email' ) ) );

		try {
			// In this context, pay_for_order capability checks that the current user ID matches the customer ID stored
			// within the order, or if the order was placed by a guest.
			// See https://github.com/woocommerce/woocommerce/blob/abcedbefe02f9e89122771100c42ff588da3e8e0/plugins/woocommerce/includes/wc-user-functions.php#L458.
			if ( ! current_user_can( 'pay_for_order', $order_id ) ) {
				throw new RouteException( 'woocommerce_rest_invalid_user', __( 'This order belongs to a different customer. Please log in to the correct account.', 'woo-gutenberg-products-block' ), 403 );
			}

			if ( get_current_user_id() === 0 ) {
				$this->order_controller->validate_order_key( $order_id, $order_key );
				$this->order_controller->validate_billing_email( $order_id, $billing_email );
			}
		} catch ( RouteException $error ) {
			return new \WP_Error(
				$error->getErrorCode(),
				$error->getMessage(),
				array( 'status' => $error->getCode() )
			);
		}

		return true;
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$order_id = absint( $request['id'] );
		return rest_ensure_response( $this->schema->get_item_response( $this->order_controller->get_order( $order_id ) ) );
	}
}
