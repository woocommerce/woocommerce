<?php
/**
 * REST API Emails Settings Controller
 *
 * Handles requests to the /settings/emails endpoints.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Emails;

use WP_Error;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Emails\Schema\EmailsSettingsSchema;
use WC_Emails;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Emails Settings Controller Class.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings/emails';

	/**
	 * Schema instance.
	 *
	 * @var EmailsSettingsSchema
	 */
	protected $schema;

	/**
	 * Initialize the controller.
	 *
	 * @param EmailsSettingsSchema $schema Schema class.
	 * @internal
	 */
	final public function init( EmailsSettingsSchema $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Collection endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'post_id' => array(
							'description' => __( 'Filter by template post ID.', 'woocommerce' ),
							'type'        => 'integer',
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Single item endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<email_id>[\w-]+)',
			array(
				'args'   => array(
					'email_id' => array(
						'description' => __( 'Email template ID.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Send test email endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<email_id>[\w-]+)/test',
			array(
				'args' => array(
					'email_id' => array(
						'description' => __( 'Email template ID.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test_email' ),
					'permission_callback' => array( $this, 'send_test_permissions_check' ),
					'args'                => array(
						'send_to' => array(
							'description'       => __( 'Recipient email address. Defaults to the site admin email.', 'woocommerce' ),
							'type'              => 'string',
							'format'            => 'email',
							'sanitize_callback' => 'sanitize_email',
						),
					),
				),
			)
		);
	}

	/**
	 * Check permissions for reading email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access email settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check permissions for reading a single email setting.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check permissions for updating email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to edit email settings.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get all email settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		try {
			$emails = WC_Emails::instance()->get_emails();
			$items  = array();

			foreach ( $emails as $email ) {
				$item = $this->schema->get_item_response( $email, $request );
				// Filter by post_id if provided.
				$post_id = $request->get_param( 'post_id' );
				if ( $post_id && (int) $item['post_id'] !== (int) $post_id ) {
					continue;
				}
				$items[] = $item;
			}

			return rest_ensure_response( $items );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_emails_settings_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get a single email setting.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$email_id = $request['email_id'];
		$email    = $this->get_email_by_id( $email_id );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_rest_email_not_found',
				__( 'Email template not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		try {
			$response = $this->schema->get_item_response( $email, $request );
			return rest_ensure_response( $response );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_email_settings_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Update a single email setting.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$email_id = $request['email_id'];
		$email    = $this->get_email_by_id( $email_id );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_rest_email_not_found',
				__( 'Email template not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$params = $request->get_json_params();

		if ( ! is_array( $params ) || empty( $params ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid or empty request body.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Check if the request contains a 'values' field with the flat key-value mapping.
		$values_to_update = array();
		if ( isset( $params['values'] ) && is_array( $params['values'] ) ) {
			$values_to_update = $params['values'];
		} else {
			// Fallback to the old format for backward compatibility.
			$values_to_update = $params;
		}

		// Validate and sanitize.
		$validated = $this->schema->validate_and_sanitize_settings( $email, $values_to_update );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Update options.
		$updated_fields = array();
		foreach ( $validated as $key => $value ) {
			$email->update_option( $key, $value );
			$updated_fields[] = $key;
		}

		// Reload emails after the update.
		WC_Emails::instance()->init();

		// Get updated email and return formatted response.
		$updated_email = $this->get_email_by_id( $email_id );
		if ( ! $updated_email ) {
			return new WP_Error(
				'woocommerce_rest_email_update_error',
				__( 'Failed to retrieve updated email settings.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		// Trigger action for settings update.
		if ( ! empty( $updated_fields ) ) {
			/**
			 * Fires when WooCommerce email settings are updated.
			 *
			 * @param array  $updated_fields Array of updated field IDs.
			 * @param string $rest_base      The REST base of the settings.
			 * @since 10.2.0
			 */
			do_action( 'woocommerce_settings_updated', $updated_fields, $this->rest_base );
		}

		try {
			$response = $this->schema->get_item_response( $updated_email, $request );
			return rest_ensure_response( $response );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_email_settings_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Check permissions for sending a test email.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function send_test_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to send test emails.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Send a test version of the email to a specified address.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function send_test_email( $request ) {
		$email_id = $request['email_id'];
		$email    = $this->get_email_by_id( $email_id );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_rest_email_not_found',
				__( 'Email template not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$send_to = ! empty( $request->get_param( 'send_to' ) )
			? sanitize_email( $request->get_param( 'send_to' ) )
			: get_bloginfo( 'admin_email' );

		if ( ! is_email( $send_to ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Invalid recipient email address.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$this->setup_test_email_object( $email );

		$email->setup_locale();

		/* translators: %s: original email subject */
		$subject = sprintf( __( '[Test] %s', 'woocommerce' ), $email->get_subject() );

		$sent = $email->send(
			$send_to,
			$subject,
			$email->get_content(),
			$email->get_headers(),
			$email->get_attachments()
		);

		$email->restore_locale();

		if ( ! $sent ) {
			return new WP_Error(
				'woocommerce_rest_email_send_test_failed',
				__( 'Failed to send test email. Please check your email delivery configuration.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'send_to' => $send_to,
				'subject' => $subject,
			)
		);
	}

	/**
	 * Populate the email object with sample data so it renders correctly in test sends.
	 *
	 * Most WC emails expect an order as their object; account-related emails expect a customer.
	 * When no sample data is available the email is still sent but may render with empty fields.
	 *
	 * @param \WC_Email $email Email instance to set up.
	 * @return void
	 */
	private function setup_test_email_object( \WC_Email $email ): void {
		if ( ! is_null( $email->object ) ) {
			return;
		}

		// Account emails need a WC_Customer object.
		if ( in_array( $email->id, array( 'customer_new_account', 'customer_reset_password' ), true ) ) {
			$user = wp_get_current_user();
			if ( $user->ID ) {
				$email->object = new \WC_Customer( $user->ID );
			}
			return;
		}

		// For all other emails (order-based), use the most recent order.
		$orders = wc_get_orders(
			array(
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => 'any',
			)
		);

		if ( empty( $orders ) ) {
			return;
		}

		$order         = $orders[0];
		$email->object = $order;

		if ( isset( $email->placeholders['{order_date}'] ) ) {
			$email->placeholders['{order_date}'] = wc_format_datetime( $order->get_date_created() );
		}
		if ( isset( $email->placeholders['{order_number}'] ) ) {
			$email->placeholders['{order_number}'] = $order->get_order_number();
		}
		if ( isset( $email->placeholders['{order_billing_full_name}'] ) ) {
			$email->placeholders['{order_billing_full_name}'] = $order->get_formatted_billing_full_name();
		}
	}

	/**
	 * Get the item response for a single email.
	 *
	 * @param mixed           $item    Email instance.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $item, WP_REST_Request $request ): array {
		return $this->schema->get_item_response( $item, $request );
	}

	/**
	 * Get email instance by ID.
	 *
	 * @param string $email_id Email ID.
	 * @return \WC_Email|null Email instance or null if not found.
	 */
	private function get_email_by_id( string $email_id ) {
		$emails = WC_Emails::instance()->get_emails();

		foreach ( $emails as $email ) {
			if ( $email->id === $email_id ) {
				return $email;
			}
		}

		return null;
	}

	/**
	 * Get the schema for the current resource.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return $this->schema->get_item_schema();
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		return $this->get_schema();
	}

	/**
	 * Get the endpoint args for item schema.
	 *
	 * @param string $method HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ): array {
		return rest_get_endpoint_args_for_schema( $this->get_item_schema(), $method );
	}
}
