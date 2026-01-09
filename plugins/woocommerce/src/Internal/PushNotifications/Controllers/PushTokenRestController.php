<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenNotFoundException;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use InvalidArgumentException;
use Exception;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Http;

/**
 * Controller for the REST endpoints associated with push notification device
 * tokens.
 */
class PushTokenRestController extends RestApiControllerBase {
	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-push-notifications';

	/**
	 * The REST base for the endpoints URL.
	 *
	 * @var string
	 */
	protected string $rest_base = '/push-tokens';

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return $this->route_namespace;
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		// Routes will registered here, can't omit method due to parent class
		// constraints.
	}

	/**
	 * Get the schema for the POST endpoint.
	 *
	 * @return array[]
	 */
	public function get_schema(): array {
		return array_merge(
			$this->get_base_schema(),
			array(
				'title'      => PushToken::POST_TYPE,
				'properties' => array_map(
					fn ( $item ) => array_diff_key( $item, array( 'validate_callback' => null ) ),
					$this->get_args()
				),
			)
		);
	}

	/**
	 * Checks user is authorized to access this endpoint.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function authorize( WP_REST_Request $request ) {
		if (
			! get_current_user_id()
			|| ! wc_get_container()->get( PushNotifications::class )->should_be_enabled()
		) {
			return false;
		}

		$has_valid_role = array_reduce(
			PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED,
			fn ( $carry, $role ) => $this->check_permission( $request, $role ) === true ? true : $carry,
			false
		);

		if ( ! $has_valid_role ) {
			return false;
		}

		if ( $request->has_param( 'id' ) ) {
			$push_token = new PushToken();
			$push_token->set_id( (int) $request->get_param( 'id' ) );

			try {
				wc_get_container()->get( PushTokensDataStore::class )->read( $push_token );
			} catch ( Exception $e ) {
				return $this->convert_exception_to_wp_error( $e );
			}

			if ( $push_token->get_user_id() !== get_current_user_id() ) {
				return new WP_Error(
					'rest_invalid_push_token',
					'Push token could not be found.',
					array( 'status' => WP_Http::NOT_FOUND )
				);
			}
		}

		return true;
	}

	/**
	 * Converts an exception to an instance of WP_Error.
	 *
	 * @param Exception $e The exception to convert.
	 * @return WP_Error
	 */
	private function convert_exception_to_wp_error( Exception $e ): WP_Error {
		$slug = match ( get_class( $e ) ) {
			PushTokenNotFoundException::class => 'rest_invalid_push_token',
			InvalidArgumentException::class => 'rest_invalid_argument',
			default => 'rest_internal_error',
		};

		$code = match ( get_class( $e ) ) {
			PushTokenNotFoundException::class => WP_Http::NOT_FOUND,
			InvalidArgumentException::class => WP_Http::BAD_REQUEST,
			default => WP_Http::INTERNAL_SERVER_ERROR,
		};

		return new WP_Error( $slug, $e->getMessage(), array( 'status' => $code ) );
	}

	/**
	 * Get the accepted arguments for the POST request.
	 *
	 * @param string $context The context to return args for.
	 * @return array
	 */
	private function get_args( ?string $context = null ): array {
		$args = array(
			'id'          => array(
				'description'       => __( 'Push Token ID', 'woocommerce' ),
				'type'              => 'integer',
				'required'          => true,
				'context'           => array( 'delete' ),
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'origin'      => array(
				'description' => __( 'Origin', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => array( 'create' ),
				'enum'        => PushToken::ORIGINS,
			),
			'device_uuid' => array(
				'description'       => __( 'Device UUID', 'woocommerce' ),
				'default'           => '',
				'type'              => 'string',
				'context'           => array( 'create' ),
				'validate_callback' => array( $this, 'validate_device_uuid' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'platform'    => array(
				'description' => __( 'Platform', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => array( 'create' ),
				'enum'        => PushToken::PLATFORMS,
			),
			'token'       => array(
				'description'       => __( 'Push Token', 'woocommerce' ),
				'type'              => 'string',
				'required'          => true,
				'context'           => array( 'create' ),
				'validate_callback' => array( $this, 'validate_token' ),
				'sanitize_callback' => 'wp_unslash',
			),
		);

		if ( $context ) {
			$args = array_filter(
				$args,
				fn ( $arg ) => in_array( $context, $arg['context'], true )
			);
		}

		return $args;
	}
}
