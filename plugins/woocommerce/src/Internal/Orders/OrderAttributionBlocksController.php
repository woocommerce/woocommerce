<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;
use WP_Error;

/**
 * Class OrderAttributionBlocksController
 *
 * @since 8.5.0
 */
class OrderAttributionBlocksController implements RegisterHooksInterface {

	use ScriptDebug;

	/**
	 * Instance of the features controller.
	 *
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * ExtendSchema instance.
	 *
	 * @var ExtendSchema
	 */
	private $extend_schema;

	/**
	 * Instance of the order attribution controller.
	 *
	 * @var OrderAttributionController
	 */
	private $order_attribution_controller;

	/**
	 * Bind dependencies on init.
	 *
	 * @internal
	 *
	 * @param ExtendSchema               $extend_schema                 ExtendSchema instance.
	 * @param FeaturesController         $features_controller           Features controller.
	 * @param OrderAttributionController $order_attribution_controller Instance of the order attribution controller.
	 */
	final public function init(
		ExtendSchema $extend_schema,
		FeaturesController $features_controller,
		OrderAttributionController $order_attribution_controller
	) {
		$this->extend_schema                = $extend_schema;
		$this->features_controller          = $features_controller;
		$this->order_attribution_controller = $order_attribution_controller;
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function register() {
		// Bail if the feature is not enabled.
		if ( ! $this->features_controller->feature_is_enabled( 'order_attribution' ) ) {
			return;
		}

		$this->extend_api();
	}

	/**
	 * Extend the Store API.
	 *
	 * @return void
	 */
	private function extend_api() {
		$this->extend_schema->register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'woocommerce/order-attribution',
				'schema_callback' => $this->get_schema_callback(),
			)
		);
		// Update order based on extended data.
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order, $request ) {
				$extensions = $request->get_param( 'extensions' );
				$params     = $extensions['woocommerce/order-attribution'] ?? array();

				if ( empty( $params ) ) {
					return;
				}

				/**
				 * Run an action to save order attribution data.
				 *
				 * @since 8.5.0
				 *
				 * @param WC_Order $order  The order object.
				 * @param array    $params Unprefixed order attribution data.
				 */
				do_action( 'woocommerce_order_save_attribution_data', $order, $params );
			},
			10,
			2
		);
	}

	/**
	 * Get the schema callback.
	 *
	 * @return callable
	 */
	private function get_schema_callback() {
		return function() {
			$schema      = array();
			$field_names = $this->order_attribution_controller->get_field_names();

			$validate_callback = function( $value ) {
				if ( ! is_string( $value ) && null !== $value ) {
					return new WP_Error(
						'api-error',
						sprintf(
							/* translators: %s is the property type */
							esc_html__( 'Value of type %s was posted to the order attribution callback', 'woocommerce' ),
							gettype( $value )
						)
					);
				}

				return true;
			};

			$sanitize_callback = function( $value ) {
				return sanitize_text_field( $value );
			};

			foreach ( $field_names as $field_name ) {
				$schema[ $field_name ] = array(
					'description' => sprintf(
						/* translators: %s is the field name */
						__( 'Order attribution field: %s', 'woocommerce' ),
						esc_html( $field_name )
					),
					'type'        => array( 'string', 'null' ),
					'context'     => array(),
					'arg_options' => array(
						'validate_callback' => $validate_callback,
						'sanitize_callback' => $sanitize_callback,
					),
				);
			}

			return $schema;
		};
	}
}
