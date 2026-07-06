<?php
/**
 * CartAddFee class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\POS\StoreApi\CustomFeesStore;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * POS cart/add-fee route — the "custom amount" a cashier adds at the register.
 *
 * There is no web counterpart to adapt (the Store API exposes no fee route),
 * so this is POS-owned surface: the fee spec is stored in the transaction
 * session ({@see CustomFeesStore}) and re-applied on every cart calculation
 * by {@see \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy}.
 * The response is the standard Store API cart, recalculated with the fee
 * included.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class CartAddFee extends AbstractCartRoute {

	use PosRouteTrait;

	/**
	 * Capability required to call this route.
	 */
	protected const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'pos-cart-add-fee';

	/**
	 * The route's schema — the shared Store API cart schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = CartSchema::IDENTIFIER;

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/cart/add-fee';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'name'      => array(
						'description'       => __( 'Fee label shown on the cart and order.', 'woocommerce' ),
						'type'              => 'string',
						'context'           => array( 'view', 'edit' ),
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'amount'    => array(
						'description' => __( 'Fee amount, excluding tax. Must be greater than zero.', 'woocommerce' ),
						'type'        => 'number',
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'taxable'   => array(
						'description' => __( 'Whether tax applies to the fee.', 'woocommerce' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'default'     => false,
					),
					'tax_class' => array(
						'description' => __( 'Tax class applied when the fee is taxable.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'default'     => '',
					),
				),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On invalid fee input.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$name   = trim( (string) $request['name'] );
		$amount = (float) $request['amount'];

		if ( '' === $name ) {
			throw new RouteException( 'woocommerce_pos_rest_invalid_fee_name', esc_html__( 'The fee name cannot be empty.', 'woocommerce' ), 400 );
		}

		if ( $amount <= 0 ) {
			throw new RouteException( 'woocommerce_pos_rest_invalid_fee_amount', esc_html__( 'The fee amount must be greater than zero.', 'woocommerce' ), 400 );
		}

		if ( ! WC()->session ) {
			throw new RouteException( 'woocommerce_pos_rest_no_session', esc_html__( 'No transaction session is available.', 'woocommerce' ), 500 );
		}

		( new CustomFeesStore( WC()->session ) )->add( $name, $amount, (bool) $request['taxable'], (string) $request['tax_class'] );

		// Recalculate so the response cart includes the fee (CustomFeesPolicy
		// applies stored fees inside the calculation).
		$cart = $this->cart_controller->get_cart_instance();
		$cart->calculate_totals();

		$response = rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_for_response() ) );
		$response->set_status( 201 );

		return $response;
	}
}
