<?php
/**
 * OrdersController class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Controller;

use Automattic\WooCommerce\Container;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\ControllerBase;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute as RestApiController;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute as RestApiEndpoint;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute as AllowedRoles;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\DescriptionAttribute as Description;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\ResponseException;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Responses;
use Automattic\WooCommerce\Proxies\LegacyProxy;

// phpcs:disable Squiz.Commenting.ClassComment.Missing

#[RestApiController( 'orders' )]
#[Description( 'Handles WooCommerce orders.' )]
class OrdersController extends ControllerBase {

	/**
	 * The dependency injection container to use.
	 *
	 * @var Container
	 */
	private static Container $container;

	/**
	 * The legacy proxy instance to use.
	 *
	 * @var LegacyProxy
	 */
	private static LegacyProxy $legacy_proxy;

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod

	/**
	 * Runs before any request.
	 *
	 * @param \WP_Rest_Request $request The request.
	 * @param \WP_User|null    $user The user making the request.
	 * @return void
	 */
	public static function init( \WP_Rest_Request $request, ?\WP_User $user ): void {
		self::$container    = wc_get_container();
		self::$legacy_proxy = self::$container->get( LegacyProxy::class );
	}

	// phpcs:enable WooCommerce.Functions.InternalInjectionMethod

	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	#[RestApiEndpoint( 'GET', '(?<id>:int:)' )]
	#[Description( 'Get the details of an order.' )]
	public static function get_order( \WP_Rest_Request $request, ?\WP_User $user ) {
		$order_id = $request->get_param( 'id' );
		$order    = self::get_existing_order( $order_id, $user );

		// TODO: "prepare_object_for_response" should be implemented here or in a proper serialization class.
		$old_orders_controller = self::$legacy_proxy->get_instance_of( 'WC_REST_Orders_Controller' );
		$order                 = $old_orders_controller->prepare_object_for_response( $order, $request );

		return $order;
	}

	#[RestApiEndpoint( 'GET', '(?<id>:int:)/notes' )]
	#[Description( 'Get the notes of an order.' )]
	public static function get_order_notes( \WP_Rest_Request $request, ?\WP_User $user ) {
		$order_id = $request->get_param( 'id' );
		self::get_existing_order( $order_id, $user );

		// TODO: The logic to actually retrieving the notes should be moved to a different class.

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$args  = array(
			'post_id' => $order_id,
			'type'    => 'order_note',
		);
		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		return $notes;
	}

	#[RestApiEndpoint( 'POST', '(?<id>:int:)/notes' )]
	#[Description( 'Add a note to an order.' )]
	public static function add_order_note( \WP_Rest_Request $request, ?\WP_User $user ) {
		$order_id = $request->get_param( 'id' );
		$order    = self::get_existing_order( $order_id, $user );

		$note = $request['note'] ?? null;
		if ( $note === null ) {
			return Responses::invalid_request( 'missing_argument', "The 'note' argument is mandatory." );
		}

		// TODO: The logic to actually add the note should be moved to a different class.

		$note_id = $order->add_order_note( $note, $request['customer_note'] ?? false, $request['added_by_user'] ?? false );
		if ( ! $note_id ) {
			return Responses::internal_server_error();
		}

		return array( 'note_id' => $note_id );
	}

	#[RestApiEndpoint( 'GET', 'hpos_is_active' )]
	#[Description( 'Check if HPOS is active.' )]
	#[AllowedRoles( 'administrator' )]
	public static function get_hpos_is_active( \WP_Rest_Request $request, ?\WP_User $user ) {
		return self::$container->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing

	/**
	 * Get an existing order, or thrown the appropriate error if the order doesn't exist
	 * or if the current user isn't allowed to see it.
	 *
	 * @param int      $order_id The order id to check.
	 * @param \WP_User $user The user that is making the request.
	 * @return \WC_Order An WC_Order object.
	 */
	private static function get_existing_order( $order_id, $user ) {
		$order = self::$legacy_proxy->call_function( 'wc_get_order', $order_id );
		if ( $order === false ) {
			throw ResponseException::for_not_found( null, "The order doesn't exist" );
		}

		if ( $order->get_customer_id() !== $user->ID ) {
			self::ensure_user_can( $user, 'manage_woocommerce' );
		}

		return $order;
	}
}

// phpcs:enable Squiz.Commenting.ClassComment.Missing
