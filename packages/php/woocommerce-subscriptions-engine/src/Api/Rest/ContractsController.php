<?php
/**
 * REST controller for subscription engine contracts.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api\Rest
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api\Rest;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\RESTPermissions;

defined( 'ABSPATH' ) || exit;

/**
 * Contracts REST controller: `GET wc/v3/subscriptions-engine/contracts/{id}` returns the
 * stored contract facts as {@see ContractView} holds them, to store managers only.
 */
final class ContractsController extends WP_REST_Controller {

	private const REST_NAMESPACE = 'wc/v3';

	private const REST_BASE = 'subscriptions-engine/contracts';

	/**
	 * REST permissions.
	 *
	 * @var RESTPermissions
	 */
	private $rest_permissions;

	/**
	 * Build the controller.
	 */
	public function __construct() {
		$this->namespace        = self::REST_NAMESPACE;
		$this->rest_base        = self::REST_BASE;
		$this->rest_permissions = new RESTPermissions();
	}

	/**
	 * Wire route registration.
	 */
	public static function register_hooks(): void {
		add_action(
			'rest_api_init',
			static function (): void {
				( new self() )->register_routes();
			}
		);
	}

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the contract.', 'woocommerce-subscriptions-engine' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Store managers only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return $this->rest_permissions->require_admin_permission();
	}

	/**
	 * Get one contract with its items and addresses.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$contract = Contracts::get( Coercion::coerce_int( $request->get_param( 'id' ) ) );
		if ( null === $contract ) {
			return new WP_Error(
				'woocommerce_subscriptions_engine_contract_not_found',
				__( 'Contract not found.', 'woocommerce-subscriptions-engine' ),
				array( 'status' => 404 )
			);
		}

		return $this->prepare_item_for_response( $contract, $request );
	}

	/**
	 * The contract as response data. Dates are GMT, formatted like other WordPress REST dates.
	 *
	 * @param ContractView    $item    Contract.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'                   => $item->get_id(),
			'extension_slug'       => $item->get_extension_slug(),
			'status'               => $item->get_status(),
			'customer_id'          => $item->get_customer_id(),
			'currency'             => $item->get_currency(),
			'selling_plan_id'      => $item->get_selling_plan_id(),
			'origin_order_id'      => $item->get_origin_order_id(),
			'payment_method'       => $item->get_payment_method(),
			'payment_method_title' => $item->get_payment_method_title(),
			'payment_token_id'     => $item->get_payment_token_id(),
			'start_gmt'            => $this->format_date( $item->get_start_gmt() ),
			'next_payment_gmt'     => $this->format_date( $item->get_next_payment_gmt() ),
			'last_payment_gmt'     => $this->format_date( $item->get_last_payment_gmt() ),
			'last_attempt_gmt'     => $this->format_date( $item->get_last_attempt_gmt() ),
			'trial_end_gmt'        => $this->format_date( $item->get_trial_end_gmt() ),
			'end_gmt'              => $this->format_date( $item->get_end_gmt() ),
			'schedule_source'      => $item->get_schedule_source(),
			'billing_total'        => $item->get_billing_total(),
			'discount_total'       => $item->get_discount_total(),
			'shipping_total'       => $item->get_shipping_total(),
			'tax_total'            => $item->get_tax_total(),
			'items'                => $item->get_items() ?? array(),
			'addresses'            => $item->get_addresses() ?? array(),
		);

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, Coercion::coerce_string( $request->get_param( 'context' ), 'view' ) );

		return rest_ensure_response( $data );
	}

	/**
	 * Get the contract schema.
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$properties = array(
			'id'                   => array( 'integer', __( 'Unique identifier for the contract.', 'woocommerce-subscriptions-engine' ) ),
			'extension_slug'       => array( array( 'string', 'null' ), __( 'Slug of the extension that owns the contract.', 'woocommerce-subscriptions-engine' ) ),
			'status'               => array( 'string', __( 'Contract status slug.', 'woocommerce-subscriptions-engine' ) ),
			'customer_id'          => array( array( 'integer', 'null' ), __( 'Customer user ID.', 'woocommerce-subscriptions-engine' ) ),
			'currency'             => array( array( 'string', 'null' ), __( 'Currency code.', 'woocommerce-subscriptions-engine' ) ),
			'selling_plan_id'      => array( array( 'integer', 'null' ), __( 'Plan ID.', 'woocommerce-subscriptions-engine' ) ),
			'origin_order_id'      => array( array( 'integer', 'null' ), __( 'ID of the order the contract started from.', 'woocommerce-subscriptions-engine' ) ),
			'payment_method'       => array( array( 'string', 'null' ), __( 'Payment gateway ID.', 'woocommerce-subscriptions-engine' ) ),
			'payment_method_title' => array( array( 'string', 'null' ), __( 'Payment method title.', 'woocommerce-subscriptions-engine' ) ),
			'payment_token_id'     => array( array( 'integer', 'null' ), __( 'Payment token ID.', 'woocommerce-subscriptions-engine' ) ),
			'start_gmt'            => array( array( 'string', 'null' ), __( 'Start date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'next_payment_gmt'     => array( array( 'string', 'null' ), __( 'Next-due moment, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'last_payment_gmt'     => array( array( 'string', 'null' ), __( 'Last payment date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'last_attempt_gmt'     => array( array( 'string', 'null' ), __( 'Last payment attempt date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'trial_end_gmt'        => array( array( 'string', 'null' ), __( 'Trial end date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'end_gmt'              => array( array( 'string', 'null' ), __( 'End date, as GMT.', 'woocommerce-subscriptions-engine' ) ),
			'schedule_source'      => array( 'string', __( 'Who keeps the payment schedule.', 'woocommerce-subscriptions-engine' ) ),
			'billing_total'        => array( 'string', __( 'Recurring total.', 'woocommerce-subscriptions-engine' ) ),
			'discount_total'       => array( 'string', __( 'Recurring discount total.', 'woocommerce-subscriptions-engine' ) ),
			'shipping_total'       => array( 'string', __( 'Recurring shipping total.', 'woocommerce-subscriptions-engine' ) ),
			'tax_total'            => array( 'string', __( 'Recurring tax total.', 'woocommerce-subscriptions-engine' ) ),
			'items'                => array( 'array', __( 'Line items.', 'woocommerce-subscriptions-engine' ) ),
			'addresses'            => array( 'object', __( 'Billing and shipping addresses.', 'woocommerce-subscriptions-engine' ) ),
		);

		$schema_properties = array();
		foreach ( $properties as $key => $property ) {
			$schema_properties[ $key ] = array(
				'description' => $property[1],
				'type'        => $property[0],
				'context'     => array( 'view' ),
				'readonly'    => true,
			);
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'subscription_engine_contract',
			'type'       => 'object',
			'properties' => $schema_properties,
		);

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * A stored GMT datetime in the WordPress REST date format, or null.
	 *
	 * @param string|null $date_gmt Stored `Y-m-d H:i:s` GMT datetime.
	 */
	private function format_date( ?string $date_gmt ): ?string {
		return null === $date_gmt ? null : mysql_to_rfc3339( $date_gmt );
	}
}
