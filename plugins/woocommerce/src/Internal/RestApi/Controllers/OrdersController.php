<?php
/**
 * OrdersController class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Controller;

use Automattic\WooCommerce\Container;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\AboutTitleAttribute as AboutTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\AboutTextAttribute as AboutText;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\SidebarPathAttribute as SidebarPath;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionTitleAttribute as DescriptionTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\HttpStatusCodeAttribute as HttpStatusCode;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\InputParameterAttribute as InputParameter;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\InputTypeAttribute as InputType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\OutputTypeAttribute as OutputType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\ControllerBase;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute as RestApiController;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute as RestApiEndpoint;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute as AllowedRoles;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\ResponseException;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Responses;
use Automattic\WooCommerce\Proxies\LegacyProxy;

// phpcs:disable Squiz.Commenting.ClassComment.Missing

#[RestApiController( 'orders' )]
#[SidebarPath('WooCommerce Orders/Orders')]
#[DescriptionTitle('Orders')]
#[Description( 'invoke::OrdersTexts::controller_subtitle' )]
#[AboutTitle('About WooCommerce Orders')]
#[AboutText( 'invoke::OrdersTexts::controller_about_text' )]
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
	#[DescriptionTitle( 'Get order' )]
	#[Description( 'Get the full details of an order. Refunds are not included.' )]
	#[InputParameter('id', 'Identifier of the order.', InputParameterLocation::Path, 'int', true )]
	#[HttpStatusCode(200, 'Ok')]
	#[HttpStatusCode(404, 'Order not found')]
	public static function get_order( \WP_Rest_Request $request, ?\WP_User $user ) {
		$order_id = $request->get_param( 'id' );
		$order    = self::get_existing_order( $order_id, $user );

		// TODO: "prepare_object_for_response" should be implemented here or in a proper serialization class.
		$old_orders_controller = self::$legacy_proxy->get_instance_of( 'WC_REST_Orders_Controller' );
		$order                 = $old_orders_controller->prepare_object_for_response( $order, $request );

		return $order;
	}

	#[RestApiEndpoint( 'GET', '(?<id>:int:)/notes' )]
	#[DescriptionTitle( 'Get the notes associated to a given order' )]
	#[Description( 'Retrieve the existing notes associated to a given order. Pagination is available.' )]
	#[InputParameter('id', 'Identifier of the order.', InputParameterLocation::Path, 'int', true )]
	#[InputParameter('page', 'Page number of the results to fetch.', InputParameterLocation::Query, 'int', false, 1)]
	#[InputParameter('per_page', 'The number of results per page (max 100).', InputParameterLocation::Query, 'int', false, 30)]
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
	#[DescriptionTitle( 'Add a note to an order' )]
	#[Description( 'Used to add a note to an order. Can\'t be used to update an existing order.' )]
	#[InputParameter('id', 'Identifier of the order.', InputParameterLocation::Path, 'int', true )]
	#[HttpStatusCode(204, 'Created.')]
	#[HttpStatusCode(404, 'Invalid order id.')]
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
	#[DescriptionTitle( 'Check if HPOS is active' )]
	#[Description( 'Returns `true` if the orders table is authoritative, `false` otherwise.' )]
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
