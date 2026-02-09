<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\EmailPreview;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;

/**
 * Controller for the REST endpoint to send an email preview.
 */
class EmailPreviewRestController extends RestApiControllerBase {

	/**
	 * Email preview nonce.
	 *
	 * @var string
	 */
	const NONCE_KEY = 'email-preview-nonce';

	/**
	 * Holds the EmailPreview instance for rendering email previews.
	 *
	 * @var EmailPreview
	 */
	private EmailPreview $email_preview;

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-admin-email';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'settings/email';

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-admin-email';
	}

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->email_preview = wc_get_container()->get( EmailPreview::class );
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/send-preview',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->send_email_preview( $request ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_send_preview(),
					'schema'              => $this->get_schema_with_message(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/preview-subject',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn() => array(
						'subject' => $this->email_preview->get_subject(),
					),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_preview_subject(),
					'schema'              => $this->get_schema_for_preview_subject(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/save-transient',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->save_transient( $request ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_save_transient(),
					'schema'              => $this->get_schema_with_message(),
				),
			)
		);
	}

	/**
	 * Get the accepted arguments for the POST send-preview request.
	 *
	 * @return array[]
	 */
	private function get_args_for_send_preview() {
		return array(
			'type'  => array(
				'description'       => __( 'The email type to preview.', 'woocommerce' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => fn( $key ) => $this->validate_email_type( $key ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email' => array(
				'description'       => __( 'Email address to send the email preview to.', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'email',
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_email',
			),
		);
	}

	/**
	 * Get the accepted arguments for the GET preview-subject request.
	 *
	 * @return array[]
	 */
	private function get_args_for_preview_subject() {
		return array(
			'type' => array(
				'description'       => __( 'The email type to get subject for.', 'woocommerce' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => fn( $key ) => $this->validate_email_type( $key ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get the accepted arguments for the POST save-transient request.
	 *
	 * @return array[]
	 */
	private function get_args_for_save_transient() {
		return array(
			'key'   => array(
				'required'          => true,
				'type'              => 'string',
				'description'       => 'The key for the transient. Must be one of the allowed options.',
				'validate_callback' => function ( $key ) {
					if ( ! in_array( $key, EmailPreview::get_all_email_setting_ids(), true ) ) {
						return new \WP_Error(
							'woocommerce_rest_not_allowed_key',
							sprintf( 'The provided key "%s" is not allowed.', $key ),
							array( 'status' => 400 ),
						);
					}
					return true;
				},
				'sanitize_callback' => 'sanitize_text_field',
			),
			'value' => array(
				'required'          => true,
				'type'              => 'string',
				'description'       => 'The value to be saved for the transient.',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => function ( $value, $request ) {
					$key = $request->get_param( 'key' );
					if (
						'woocommerce_email_footer_text' === $key
						|| preg_match( '/_additional_content$/', $key )
					) {
						return wp_kses_post( trim( $value ) );
					}
					return sanitize_text_field( $value );
				},
			),
		);
	}

	/**
	 * Get the schema for the POST send-preview and save-transient requests.
	 *
	 * @return array[]
	 */
	private function get_schema_with_message() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'email-preview-with-message',
			'type'       => 'object',
			'properties' => array(
				'message' => array(
					'description' => __( 'A message indicating that the action completed successfully.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get the schema for the GET preview_subject request.
	 *
	 * @return array[]
	 */
	private function get_schema_for_preview_subject() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'email-preview-subject',
			'type'       => 'object',
			'properties' => array(
				'subject' => array(
					'description' => __( 'A subject for provided email type after filters are applied and placeholders replaced.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Validate the email type.
	 *
	 * @param string $email_type The email type to validate.
	 * @return bool|WP_Error True if the email type is valid, otherwise a WP_Error object.
	 */
	private function validate_email_type( string $email_type ) {
		try {
			$this->email_preview->set_email_type( $email_type );
		} catch ( \InvalidArgumentException $e ) {
			return new WP_Error(
				'woocommerce_rest_invalid_email_type',
				__( 'Invalid email type.', 'woocommerce' ),
				array( 'status' => 400 ),
			);
		}
		return true;
	}

	/**
	 * Permission check for REST API endpoint.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True if the current user has the capability, otherwise a WP_Error object.
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$nonce = $request->get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			return new WP_Error(
				'invalid_nonce',
				__( 'Invalid nonce.', 'woocommerce' ),
				array( 'status' => 403 ),
			);
		}
		return $this->check_permission( $request, 'manage_woocommerce' );
	}

	/**
	 * Handle the POST /settings/email/send-preview.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function send_email_preview( WP_REST_Request $request ) {
		$email_address = $request->get_param( 'email' );
		// Start output buffering to prevent partial renders with PHP notices or warnings.
		ob_start();
		try {
			$email_content = $this->email_preview->render();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			return new WP_Error(
				'woocommerce_rest_email_preview_not_rendered',
				__( 'There was an error rendering an email preview.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}
		ob_end_clean();
		$email_subject = $this->email_preview->get_subject();
		$email         = new \WC_Emails();
		$sent          = $email->send( $email_address, $email_subject, $email_content );

		if ( $sent ) {
			return array(
				// translators: %s: Email address.
				'message' => sprintf( __( 'Test email sent to %s.', 'woocommerce' ), $email_address ),
			);
		}
		return new WP_Error(
			'woocommerce_rest_email_preview_not_sent',
			__( 'Error sending test email. Please try again.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Handle the POST /settings/email/save-transient.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function save_transient( WP_REST_Request $request ) {
		$key    = $request->get_param( 'key' );
		$value  = $request->get_param( 'value' );
		$is_set = set_transient( $key, $value, HOUR_IN_SECONDS );
		if ( ! $is_set ) {
			return new WP_Error(
				'woocommerce_rest_transient_not_set',
				__( 'Error saving transient. Please try again.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}
		return array(
			// translators: %s: Email settings color key, e.g., "woocommerce_email_base_color".
			'message' => sprintf( __( 'Transient saved for key %s.', 'woocommerce' ), $key ),
		);
	}
}
