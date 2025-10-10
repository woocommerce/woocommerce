<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
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
		register_rest_route(
			$this->get_rest_api_namespace(),
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'create' ),
					'permission_callback' => fn ( WP_REST_Request $request ) => $this->authorize( $request ),
					'args'                => $this->get_args( 'create' ),
					'schema'              => $this->get_schema(),
				),
			)
		);

		register_rest_route(
			$this->get_rest_api_namespace(),
			$this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'delete' ),
					'permission_callback' => fn ( WP_REST_Request $request ) => $this->authorize( $request ),
					'args'                => $this->get_args( 'delete' ),
					'schema'              => $this->get_schema(),
				),
			)
		);
	}

	/**
	 * Creates a push token record.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$push_token = new PushToken();
		$push_token->set_user_id( get_current_user_id() );
		$push_token->set_token( $request->get_param( 'token' ) );
		$push_token->set_platform( $request->get_param( 'platform' ) );
		$push_token->set_device_uuid( $request->get_param( 'device_uuid' ) );
		$push_token->set_origin( $request->get_param( 'origin' ) );

		$data_store = wc_get_container()->get( PushTokensDataStore::class );

		try {
			$existing_token = $data_store->get_by_token_or_device_id( $push_token );

			if ( $existing_token ) {
				$push_token->set_id( $existing_token->get_id() );
				$data_store->update( $push_token );
			} else {
				$data_store->create( $push_token );
			}
		} catch ( Exception $e ) {
			return $this->convert_exception_to_wp_error( $e );
		}

		return new WP_REST_Response(
			array( 'id' => $push_token->get_id() ),
			WP_Http::CREATED
		);
	}

	/**
	 * Deletes a push token record.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete( WP_REST_Request $request ) {
		$push_token = new PushToken();
		$push_token->set_id( (int) $request->get_param( 'id' ) );

		$data_store = wc_get_container()->get( PushTokensDataStore::class );

		try {
			$data_store->delete( $push_token );
		} catch ( Exception $e ) {
			return $this->convert_exception_to_wp_error( $e );
		}

		return new WP_REST_Response( null, WP_Http::NO_CONTENT );
	}

	/**
	 * Validates the token.
	 *
	 * @param string          $token The token string.
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function validate_token( string $token, WP_REST_Request $request ) {
		if (
			$request->get_param( 'platform' ) === PushToken::PLATFORM_IOS
			&& ! preg_match( '/^[A-Fa-f0-9]{64}$/', $token )
		) {
			return new WP_Error( 'rest_invalid_param', 'Invalid push token format.' );
		}

		if (
			$request->get_param( 'platform' ) === PushToken::PLATFORM_ANDROID
			&& (
				! preg_match( '/^[A-Za-z0-9=:\_\-\+\/]+$/', $token )
				|| strlen( $token ) > 4096
			)
		) {
			return new WP_Error( 'rest_invalid_param', 'Invalid push token format.' );
		}

		if ( $request->get_param( 'platform' ) === PushToken::PLATFORM_BROWSER ) {
			$token_object = json_decode( $token, true );
			$endpoint     = $token_object['endpoint'] ?? null;

			if (
				json_last_error()
				|| ! $endpoint
				|| ! isset( $token_object['keys']['auth'] )
				|| ! isset( $token_object['keys']['p256dh'] )
				|| ! wp_http_validate_url( $endpoint )
				|| ( wp_parse_url( $endpoint, PHP_URL_SCHEME ) !== 'https' )
			) {
				return new WP_Error( 'rest_invalid_param', 'Invalid push token format.' );
			}
		}

		return true;
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
	private function authorize( WP_REST_Request $request ) {
		if (
			! get_current_user_id()
			|| ! wc_get_container()->get( PushNotifications::class )->should_be_enabled()
		) {
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
		$slug = array(
			WP_Http::NOT_FOUND   => 'rest_invalid_push_token',
			WP_Http::BAD_REQUEST => 'rest_invalid_argument',
		);

		$slug = $slug[ $e->getCode() ] ?? 'rest_internal_error';

		return new WP_Error( $slug, $e->getMessage(), array( 'status' => $e->getCode() ) );
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
				'description' => __( 'Push Token ID', 'woocommerce' ),
				'type'        => 'integer',
				'required'    => true,
				'context'     => array( 'delete' ),
				'minimum'     => 1,
			),
			'origin'      => array(
				'description' => __( 'Origin', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => array( 'create' ),
				'enum'        => PushToken::ORIGINS,
			),
			'device_uuid' => array(
				'description' => __( 'Device UUID', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => array( 'create' ),
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
