<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;

// use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Internal\Orders\SourceAttributionController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Exception;
use WP_Error;

/**
 * Class SourceAttributionBlocksController
 *
 * @since x.x.x
 */
class SourceAttributionBlocksController implements RegisterHooksInterface {

	use ScriptDebug;

	// /**
	//  * Instance of the asset API.
	//  *
	//  * @var AssetApi
	//  */
	// private $asset_api;

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
	 * Instance of the source attribution controller.
	 *
	 * @var SourceAttributionController
	 */
	private $source_attribution_controller;

	/**
	 * Bind dependencies on init.
	 *
	 * @internal
	 *
	 * // @param AssetApi                    $asset_api                     Instance of the asset API.
	 * @param ExtendSchema                $extend_schema                 ExtendSchema instance.
	 * @param FeaturesController          $features_controller           Features controller.
	 * @param SourceAttributionController $source_attribution_controller Instance of the source attribution controller.
	 */
	final public function init(
		// AssetApi $asset_api,
		ExtendSchema $extend_schema,
		FeaturesController $features_controller,
		SourceAttributionController $source_attribution_controller
	) {
		// $this->asset_api                     = $asset_api;
		$this->extend_schema                 = $extend_schema;
		$this->features_controller           = $features_controller;
		$this->source_attribution_controller = $source_attribution_controller;
	}

	/**
	 * Hook into WP.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register() {
		// Bail if the feature is not enabled.
		if ( ! $this->features_controller->feature_is_enabled( 'order_source_attribution' ) ) {
			return;
		}

		$this->extend_api();

		// Bail early on admin requests to avoid asset registration.
		if ( is_admin() ) {
			return;
		}

		add_action(
			'init',
			function() {
				$this->register_assets();
			}
		);

		add_action(
			'wp_enqueue_scripts',
			function() {
				$this->enqueue_scripts();
			}
		);
	}

	/**
	 * Check if WCCom_Cookie_Terms is available.
	 *
	 * @return bool
	 */
	protected function is_wccom_cookie_terms_available() {
		return class_exists( WCCom_Cookie_Terms::class );
	}
	/**
	 * Register scripts.
	 */
	private function register_assets() {
		// $this->asset_api->register_script(
		// 	'wc-blocks-order-source-attribution',
		// 	'build/wc-blocks-order-source-attribution.js',
		// 	[ 'woocommerce-order-source-attribution-js' ]
		// );

		wp_register_script(
			'wc-blocks-order-source-attribution',
			plugins_url(
				"assets/js/frontend/order-source-attribution-blocks{$this->get_script_suffix()}.js",
				WC_PLUGIN_FILE
			),
			array( 'woocommerce-order-source-attribution-js', 'wp-data', 'wc-blocks-checkout' ),
			Constants::get_constant( 'WC_VERSION' ),
			true
		);
	}

	/**
	 * Enqueue the Order Source Attribution script.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function enqueue_scripts() {
		wp_enqueue_script( 'wc-blocks-order-source-attribution' );
	}

	/**
	 * Extend the Store API.
	 *
	 * @return void
	 */
	private function extend_api() {
		try {
			$this->extend_schema->register_endpoint_data(
				[
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => 'woocommerce/order-source-attribution',
					'schema_callback' => $this->get_schema_callback(),
				]
			);
			$this->extend_schema->register_update_callback(
				[
					'namespace' => 'woocommerce/order-source-attribution',
					'callback'  => function( $data ) {
						return array();
						// todo: Save the data to the order here.
					},
				]
			);
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			// Do nothing.
		}
	}

	/**
	 * Get the schema callback.
	 *
	 * @return callable
	 */
	private function get_schema_callback() {
		return function() {
			$schema = [];
			$fields = $this->source_attribution_controller->get_fields();

			$validate_callback = function( $value ) {
				if ( ! is_string( $value ) && null !== $value ) {
					return new WP_Error(
						'api-error',
						sprintf(
							/* translators: %s is the property type */
							esc_html__( 'Value of type %s was posted to the source attribution callback', 'woo-gutenberg-products-block' ),
							gettype( $value )
						)
					);
				}

				return true;
			};

			$sanitize_callback = function( $value ) {
				return sanitize_text_field( $value );
			};

			foreach ( $fields as $field ) {
				$schema[ $this->source_attribution_controller->get_prefixed_field( $field ) ] = [
					'description' => sprintf(
						/* translators: %s is the field name */
						__( 'Source attribution field: %s', 'woo-gutenberg-products-block' ),
						esc_html( $field )
					),
					'type'        => [ 'string', 'null' ],
					'context'     => [],
					'arg_options' => [
						'validate_callback' => $validate_callback,
						'sanitize_callback' => $sanitize_callback,
					],
				];
			}

			return $schema;
		};
	}
}
