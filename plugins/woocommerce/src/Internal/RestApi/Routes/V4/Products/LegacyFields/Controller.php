<?php
/**
 * REST API Legacy Fields Controller
 *
 * Handles requests to the /products/legacy-fields endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Products\LegacyFields;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use Automattic\WooCommerce\Internal\Admin\DataForms\LegacyFieldCapture;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Products\LegacyFields\Schema\LegacyFieldsSchema;

defined( 'ABSPATH' ) || exit;

/**
 * REST API controller for capturing legacy field definitions from WC hooks.
 *
 * @since 9.9.0
 */
class Controller extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/legacy-fields';

	/**
	 * Schema instance.
	 *
	 * @var LegacyFieldsSchema
	 */
	protected $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param LegacyFieldsSchema $item_schema Schema class.
	 * @return void
	 * @internal
	 */
	final public function init( LegacyFieldsSchema $item_schema ): void {
		$this->item_schema = $item_schema;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'hooks' => array(
							'description'       => __( 'Hook names to capture field definitions from.', 'woocommerce' ),
							'type'              => 'array',
							'items'             => array(
								'type' => 'string',
							),
							'required'          => false,
							'default'           => array(),
							'sanitize_callback' => static function ( $value ) {
								if ( ! is_array( $value ) ) {
									return array();
								}
								return array_map( 'sanitize_text_field', $value );
							},
							'validate_callback' => static function ( $value ) {
								if ( ! is_array( $value ) ) {
									return new WP_Error(
										'rest_invalid_param',
										__( 'The hooks parameter must be an array.', 'woocommerce' ),
										array( 'status' => 400 )
									);
								}

								$allowed = LegacyFieldCapture::get_allowed_hooks();
								foreach ( $value as $hook ) {
									if ( ! in_array( sanitize_text_field( $hook ), $allowed, true ) ) {
										return new WP_Error(
											'rest_invalid_param',
											sprintf(
												/* translators: %s: hook name */
												__( 'Hook name "%s" is not allowed.', 'woocommerce' ),
												sanitize_text_field( $hook )
											),
											array( 'status' => 400 )
										);
									}
								}

								return true;
							},
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Check permissions for reading legacy fields.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
		}
		return true;
	}

	/**
	 * Get legacy field definitions.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$hooks = $request->get_param( 'hooks' );

		if ( empty( $hooks ) ) {
			return $this->prepare_item_for_response( array(), $request );
		}

		$fields = LegacyFieldCapture::capture( $hooks );
		return $this->prepare_item_for_response( $fields, $request );
	}

	/**
	 * Get the schema for the current resource.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Get the item response.
	 *
	 * @param mixed                                $item    Captured field definitions.
	 * @param \WP_REST_Request<array<string,mixed>> $request Request object.
	 * @return array
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $item, $request );
	}
}
