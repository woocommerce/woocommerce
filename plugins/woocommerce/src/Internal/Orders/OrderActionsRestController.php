<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WC_Data_Exception;
use WC_Email;
use WC_Order;
use WP_Error;
use WP_REST_Request, WP_REST_Response, WP_REST_Server;

/**
 * Controller for the REST endpoint to run actions on orders.
 *
 * This first version only supports sending the order details to the customer (`send_order_details`).
 */
class OrderActionsRestController extends RestApiControllerBase {
	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'order-actions';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/actions/email_templates',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_email_templates' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_schema_for_email_templates' ),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/actions/send_email',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'send_email' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_order_actions( 'send_email', WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_schema_for_order_actions' ),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/actions/send_order_details',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'send_order_details' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_order_actions( 'send_order_details', WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_schema_for_order_actions' ),
			)
		);
	}

	/**
	 * Validate the order ID that is part of the endpoint URL.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 *
	 * @return int|WP_Error
	 */
	private function validate_order_id( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'id' );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'woocommerce_rest_not_found', __( 'Order not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $order_id;
	}

	/**
	 * Handle a request for one of the provided REST API endpoints.
	 *
	 * @param WP_REST_Request $request     The incoming HTTP REST request.
	 * @param string          $method_name The name of the class method to execute.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	protected function run( WP_REST_Request $request, string $method_name ) {
		$order_id = $this->validate_order_id( $request );

		if ( is_wp_error( $order_id ) ) {
			return $order_id;
		}

		return parent::run( $request, $method_name );
	}

	/**
	 * Permission check for REST API endpoint.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True if the current user has the capability, otherwise a WP_Error object.
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$order_id = $this->validate_order_id( $request );

		if ( is_wp_error( $order_id ) ) {
			return $order_id;
		}

		return $this->check_permission( $request, 'read_shop_order', $order_id );
	}

	/**
	 * Get the accepted arguments for the POST request.
	 *
	 * @param string $action_slug The endpoint slug for the order action.
	 *
	 * @return array[]
	 */
	private function get_args_for_order_actions( string $action_slug ): array {
		$args = array(
			'email'              => array(
				'description'       => __( 'Email address to send the order details to.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'email',
				'context'           => array( 'edit' ),
				'required'          => false,
				'validate_callback' => 'rest_validate_request_arg',
			),
			'force_email_update' => array(
				'description'       => __( 'Whether to update the billing email of the order, even if it already has one.', 'woocommerce' ),
				'type'              => 'boolean',
				'context'           => array( 'edit' ),
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		if ( 'send_email' === $action_slug ) {
			$args['template_id'] = array(
				'description'       => __( 'The ID of the template to use for sending the email.', 'woocommerce' ),
				'type'              => 'string',
				'enum'              => $this->get_template_id_enum(),
				'context'           => array( 'edit' ),
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		return $args;
	}

	/**
	 * Get the schema for the email_templates action.
	 *
	 * @return array
	 */
	public function get_schema_for_email_templates(): array {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => __( 'Email Template', 'woocommerce' ),
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique ID string for the email template.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => $this->get_template_id_enum(),
					'context'     => array( 'view', 'embed' ),
				),
				'title'       => array(
					'description' => __( 'The display name of the email template.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'description' => array(
					'description' => __( 'A description of the purpose of the email template.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the schema for all order actions that don't have a separate schema.
	 *
	 * @return array
	 */
	public function get_schema_for_order_actions(): array {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => __( 'Order Actions', 'woocommerce' ),
			'type'       => 'object',
			'properties' => array(
				'message' => array(
					'description' => __( 'A message indicating that the action completed successfully.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the list of possible template ID values.
	 *
	 * Note that this gets the IDs of all email templates. This does not mean all of these templates are available to
	 * send through the API endpoint.
	 *
	 * @return string[]
	 */
	private function get_template_id_enum(): array {
		$enum = array();

		if ( is_array( WC()->mailer()->emails ) ) {
			$enum = array_map(
				function ( $template ) {
					if ( ! $template instanceof WC_Email || empty( $template->id ) ) {
						return null;
					}

					return $template->id;
				},
				WC()->mailer()->emails,
				array() // Strip off the associative array keys.
			);
		}

		return array_filter( $enum );
	}

	/**
	 * Determine which email templates are available for the given order.
	 *
	 * @param WC_Order $order The order in question.
	 *
	 * @return WC_Email[]
	 */
	private function get_available_email_templates( WC_Order $order ): array {
		$all_email_templates = WC()->mailer()->emails;
		$order_status        = $order->get_status( 'edit' );

		$unavailable_statuses = array(
			OrderStatus::AUTO_DRAFT,
			OrderStatus::DRAFT,
			OrderStatus::NEW,
			OrderStatus::TRASH,
		);

		if ( ! $order->get_billing_email() || in_array( $order_status, $unavailable_statuses, true ) ) {
			return array();
		}

		$valid_template_classes = array(
			'WC_Email_Customer_Invoice',
		);
		if ( $this->order_is_partially_refunded( $order ) ) {
			$valid_template_classes[] = 'WC_Email_Customer_Refunded_Order';
		}

		switch ( $order_status ) {
			case OrderStatus::COMPLETED:
				$valid_template_classes[] = 'WC_Email_Customer_Completed_Order';
				break;
			case OrderStatus::FAILED:
				$valid_template_classes[] = 'WC_Email_Customer_Failed_Order';
				break;
			case OrderStatus::ON_HOLD:
				$valid_template_classes[] = 'WC_Email_Customer_On_Hold_Order';
				break;
			case OrderStatus::PROCESSING:
				$valid_template_classes[] = 'WC_Email_Customer_Processing_Order';
				break;
			case OrderStatus::REFUNDED:
				$valid_template_classes[] = 'WC_Email_Customer_Refunded_Order';
				break;
		}

		/**
		 * Filter the list of valid email templates for a given order.
		 *
		 * Note that the email class must also exist in WC_Emails::$emails.
		 *
		 * When adding a custom email template to this list, a callback must also be added to trigger the sending
		 * of the email. See the `woocommerce_rest_order_actions_email_send` action hook.
		 *
		 * @since 9.8.0
		 *
		 * @param string[] $valid_template_classes Array of email template class names that are valid for a given order.
		 * @param WC_Order $order                  The order.
		 */
		$valid_template_classes = apply_filters(
			'woocommerce_rest_order_actions_email_valid_template_classes',
			$valid_template_classes,
			$order
		);

		$valid_template_classes = array_filter( array_unique( $valid_template_classes ), 'is_string' );
		$valid_templates        = array_fill_keys( $valid_template_classes, '' );

		return array_intersect_key( $all_email_templates, $valid_templates );
	}

	/**
	 * Retrieve an email template class using its ID, if it is available.
	 *
	 * @param string     $template_id         The ID of the desired email template class.
	 * @param array|null $available_templates Optional. An array of available email template classes in the same
	 *                                        associative format as WC_Emails::$emails. If not provided, all classes
	 *                                        in WC_Emails::$emails will be considered available.
	 *
	 * @return WC_Email|null The email template class if it is available, otherwise null.
	 */
	private function get_email_template_by_id( string $template_id, ?array $available_templates = null ): ?WC_Email {
		if ( is_null( $available_templates ) ) {
			$available_templates = WC()->mailer()->emails;
		}

		$matching_templates = array_filter(
			$available_templates,
			fn( $template ) => $template->id === $template_id
		);

		if ( empty( $matching_templates ) ) {
			return null;
		}

		return reset( $matching_templates );
	}

	/**
	 * Callback to run for GET wc/v3/orders/(?P<id>[\d]+)/actions/email_templates.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 *
	 * @return array
	 */
	protected function get_email_templates( WP_REST_Request $request ): array {
		$order = wc_get_order( $request->get_param( 'id' ) );

		$available_templates = $this->get_available_email_templates( $order );
		$templates           = array();

		foreach ( $available_templates as $template ) {
			$templates[] = array(
				'id'          => $template->id,
				'title'       => $template->get_title(),
				'description' => $template->get_description(),
			);
		}

		usort(
			$templates,
			fn( $a, $b ) => strcmp( $a['id'], $b['id'] )
		);

		$schema            = $this->get_schema_for_email_templates();
		$context           = $request->get_param( 'context' ) ?? 'view';
		$filtered_response = array_map(
			function ( $template ) use ( $schema, $context ) {
				return rest_filter_response_by_context( $template, $schema, $context );
			},
			$templates
		);

		return $filtered_response;
	}

	/**
	 * Callback to run for POST wc/v3/orders/(?P<id>[\d]+)/actions/send_email.
	 *
	 * @param WP_REST_Request $request The incoming HTTP REST request.
	 *
	 * @return array|WP_Error
	 */
	protected function send_email( WP_REST_Request $request ) {
		$order       = wc_get_order( $request->get_param( 'id' ) );
		$email       = $request->get_param( 'email' );
		$force       = wp_validate_boolean( $request->get_param( 'force_email_update' ) );
		$template_id = $request->get_param( 'template_id' );
		$messages    = array();

		if ( $email ) {
			$message = $this->maybe_update_billing_email( $order, $email, $force );
			if ( is_wp_error( $message ) ) {
				return $message;
			}
			$messages[] = $message;
		}

		if ( ! is_email( $order->get_billing_email() ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_email',
				__( 'Order does not have an email address.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$available_templates = $this->get_available_email_templates( $order );
		$template            = $this->get_email_template_by_id( $template_id, $available_templates );

		if ( is_null( $template ) ) {
			return new WP_Error(
				'woocommerce_rest_invalid_email_template',
				sprintf(
					// translators: %s is a string ID for an email template.
					__( '%s is not a valid template for this order.', 'woocommerce' ),
					esc_html( $template_id )
				),
				array( 'status' => 400 )
			);
		}

		switch ( $template_id ) {
			// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
			case 'customer_completed_order':
				/** This action is documented in includes/class-wc-emails.php */
				do_action( 'woocommerce_order_status_completed_notification', $order->get_id(), $order );
				break;
			case 'customer_failed_order':
				/** This action is documented in includes/class-wc-emails.php */
				do_action( 'woocommerce_order_status_failed_notification', $order->get_id(), $order );
				break;
			case 'customer_on_hold_order':
				/** This action is documented in includes/class-wc-emails.php */
				do_action( 'woocommerce_order_status_pending_to_on-hold_notification', $order->get_id(), $order ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				break;
			case 'customer_processing_order':
				/** This action is documented in includes/class-wc-emails.php */
				do_action( 'woocommerce_order_status_pending_to_processing_notification', $order->get_id(), $order );
				break;
			case 'customer_refunded_order':
				if ( $this->order_is_partially_refunded( $order ) ) {
					/** This action is documented in includes/class-wc-emails.php */
					do_action( 'woocommerce_order_partially_refunded_notification', $order->get_id() );
				} else {
					/** This action is documented in includes/class-wc-emails.php */
					do_action( 'woocommerce_order_fully_refunded_notification', $order->get_id() );
				}
				break;
			// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment

			case 'customer_invoice':
				return $this->send_order_details( $request );

			default:
				/**
				 * Action to trigger sending a custom order email template from a REST API request.
				 *
				 * The email template must first be made available for the associated order.
				 * See the `woocommerce_rest_order_actions_email_valid_template_classes` filter hook.
				 *
				 * @since 9.8.0
				 *
				 * @param int    $order_id    The ID of the order.
				 * @param string $template_id The ID of the template specified in the API request.
				 */
				do_action( 'woocommerce_rest_order_actions_email_send', $order->get_id(), $template_id );
				break;
		}

		$user_agent = esc_html( $request->get_header( 'User-Agent' ) );
		$messages[] = sprintf(
			// translators: 1. The name of an email template; 2. Email address; 3. User-agent that requested the action.
			esc_html__( 'Email template "%1$s" sent to %2$s, via %3$s.', 'woocommerce' ),
			esc_html( $template->get_title() ),
			esc_html( $order->get_billing_email() ),
			$user_agent ? $user_agent : 'REST API'
		);

		$messages = array_filter( $messages );
		foreach ( $messages as $message ) {
			$order->add_order_note( $message, false, true );
		}

		return array(
			'message' => implode( ' ', $messages ),
		);
	}

	/**
	 * Handle the POST /orders/{id}/actions/send_order_details.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	protected function send_order_details( WP_REST_Request $request ) {
		$order    = wc_get_order( $request->get_param( 'id' ) );
		$email    = $request->get_param( 'email' );
		$force    = wp_validate_boolean( $request->get_param( 'force_email_update' ) );
		$messages = array();

		if ( $email ) {
			$message = $this->maybe_update_billing_email( $order, $email, $force );
			if ( is_wp_error( $message ) ) {
				return $message;
			}
			$messages[] = $message;
		}

		if ( ! is_email( $order->get_billing_email() ) ) {
			return new WP_Error(
				'woocommerce_rest_missing_email',
				__( 'Order does not have an email address.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->customer_invoice( $order );

		$user_agent = esc_html( $request->get_header( 'User-Agent' ) );
		$messages[] = sprintf(
			// translators: %1$s is the customer email, %2$s is the user agent that requested the action.
			esc_html__( 'Order details sent to %1$s, via %2$s.', 'woocommerce' ),
			esc_html( $order->get_billing_email() ),
			$user_agent ? $user_agent : 'REST API'
		);

		$messages = array_filter( $messages );
		foreach ( $messages as $message ) {
			$order->add_order_note( $message, false, true );
		}

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

		return array(
			'message' => implode( ' ', $messages ),
		);
	}

	/**
	 * Update the billing email of an order when certain conditions are met.
	 *
	 * If the order does not already have a billing email, it will be updated. If it does have one, but `$force` is set
	 * to `true`, it will be updated. Otherwise this will return an error. This can also return an error if the given
	 * email address is not valid.
	 *
	 * @param WC_Order $order The order to update.
	 * @param string   $email The email address to maybe add to the order.
	 * @param bool     $force Optional. True to update the order even if it already has a billing email. Default false.
	 *
	 * @return string|WP_Error A message upon success, otherwise an error.
	 */
	private function maybe_update_billing_email( WC_Order $order, string $email, ?bool $force = false ) {
		$existing_email = $order->get_billing_email( 'edit' );

		if ( $existing_email === $email ) {
			return '';
		}

		if ( $existing_email && true !== $force ) {
			return new WP_Error(
				'woocommerce_rest_order_billing_email_exists',
				__( 'Order already has a billing email.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		try {
			$order->set_billing_email( $email );
			$order->save();
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error(
				$e->getErrorCode(),
				$e->getMessage()
			);
		}

		return sprintf(
			// translators: %s is an email address.
			__( 'Billing email updated to %s.', 'woocommerce' ),
			esc_html( $email )
		);
	}

	/**
	 * Check if a given order has any partial refunds.
	 *
	 * Based on heuristics in the `wc_create_refund()` function.
	 *
	 * @param WC_Order $order An order object.
	 *
	 * @return bool
	 */
	private function order_is_partially_refunded( WC_Order $order ): bool {
		$remaining_amount = $order->get_remaining_refund_amount();
		$remaining_items  = $order->get_remaining_refund_items();
		$refunds          = $order->get_refunds();
		$last_refund      = reset( $refunds );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This filter is documented in includes/wc-order-functions.php */
		$partially_refunded = apply_filters(
			'woocommerce_order_is_partially_refunded',
			count( $refunds ) > 0 && ( $remaining_amount > 0 || ( $order->has_free_item() && $remaining_items > 0 ) ),
			$order->get_id(),
			$last_refund ? $last_refund->get_id() : 0
		);

		return (bool) $partially_refunded;
	}
}
