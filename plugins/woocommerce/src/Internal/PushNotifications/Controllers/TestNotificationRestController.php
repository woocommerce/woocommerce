<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\TestNotification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use Automattic\WooCommerce\Internal\PushNotifications\Traits\AuthorizesPushNotificationRequests;
use Automattic\WooCommerce\Internal\PushNotifications\Traits\ConvertsExceptionsToWpError;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Exception;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Controller for the REST endpoint WPCOM calls to send a silent test
 * notification to one push token, through the same steps a new order takes.
 *
 * @since 11.3.0
 */
class TestNotificationRestController extends RestApiControllerBase {
	use AuthorizesPushNotificationRequests;
	use ConvertsExceptionsToWpError;

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
	protected string $rest_base = 'test-notifications';

	/**
	 * The pending notification store.
	 *
	 * @var PendingNotificationStore
	 */
	private PendingNotificationStore $pending_notification_store;

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param PendingNotificationStore $pending_notification_store The pending notification store.
	 * @param PushTokensDataStore      $data_store                 The push tokens data store.
	 *
	 * @since 11.3.0
	 */
	final public function init( PendingNotificationStore $pending_notification_store, PushTokensDataStore $data_store ): void {
		$this->pending_notification_store = $pending_notification_store;
		$this->data_store                 = $data_store;
	}

	/**
	 * Class identifier used by `woocommerce_rest_api_get_rest_namespaces`.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-push-notifications-test-notifications';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 11.3.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'create' ),
					'permission_callback' => array( $this, 'authorize_as_from_wpcom' ),
					'args'                => array(
						'test_id'  => array(
							'description'       => __( 'The ID WPCOM created for this test, returned by the app when it acknowledges the notification.', 'woocommerce' ),
							'type'              => 'integer',
							'required'          => true,
							'minimum'           => 1,
							'maximum'           => TestNotification::MAX_TEST_ID,
							'validate_callback' => 'rest_validate_request_arg',
						),
						'token_id' => array(
							'description'       => __( 'The ID of the push token to send the test to.', 'woocommerce' ),
							'type'              => 'integer',
							'required'          => true,
							'minimum'           => 1,
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);
	}

	/**
	 * Queues a test notification for the token, to be sent when the request ends.
	 *
	 * @since 11.3.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		$test_id  = (int) $request->get_param( 'test_id' );
		$token_id = (int) $request->get_param( 'token_id' );

		try {
			$token = $this->data_store->read( $token_id );
		} catch ( Exception $e ) {
			return $this->convert_exception_to_wp_error( $e );
		}

		$owner = get_userdata( (int) $token->get_user_id() );

		if ( ! $owner || ! array_intersect( $owner->roles, PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED ) ) {
			return new WP_Error(
				'woocommerce_push_notification_token_not_eligible',
				__( 'The push token belongs to a user who does not receive push notifications.', 'woocommerce' ),
				array( 'status' => WP_Http::UNPROCESSABLE_ENTITY )
			);
		}

		$notification = new TestNotification( $test_id, $token_id );

		if ( $notification->is_used() ) {
			return new WP_Error(
				'woocommerce_push_notification_test_id_used',
				__( 'A test notification with this ID has already been sent.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		$this->pending_notification_store->add( $notification );

		return new WP_REST_Response(
			array(
				'test_id'  => $test_id,
				'token_id' => $token_id,
			),
			WP_Http::ACCEPTED
		);
	}
}
