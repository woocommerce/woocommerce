<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Emails;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use WP_Error;
use WP_REST_Request;

/**
 * Controller for the REST endpoint for the new email listing page.
 */
class EmailListingRestController extends RestApiControllerBase {

	/**
	 * Email listing nonce.
	 *
	 * @var string
	 */
	const NONCE_KEY = 'email-listing-nonce';

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
	protected string $rest_base = 'settings/email/listing';

	/**
	 * Email template generator instance.
	 *
	 * @var WCTransactionalEmailPostsGenerator
	 */
	private $email_template_generator;

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-admin-email-listing';
	}

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->email_template_generator = new WCTransactionalEmailPostsGenerator();
	}

	/**
	 * Perform the initialization.
	 */
	public function initialize_template_generator() {
		$this->email_template_generator->init_default_transactional_emails();
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		$this->initialize_template_generator();

		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/recreate-email-post',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->recreate_email_post( $request ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_recreate_email_post(),
					'schema'              => $this->get_schema_with_message(),
				),
			)
		);
	}

	/**
	 * Get the accepted arguments for the POST recreate-email-post request.
	 *
	 * @return array[]
	 */
	private function get_args_for_recreate_email_post() {
		return array(
			'email_id' => array(
				'description'       => __( 'The email ID to recreate the post for.', 'woocommerce' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => fn( $email_id ) => $this->validate_email_id( $email_id ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get the schema for the POST recreate-email-post and save-transient requests.
	 *
	 * @return array[]
	 */
	private function get_schema_with_message() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'email-listing-with-message',
			'type'       => 'object',
			'properties' => array(
				'message' => array(
					'description' => __( 'A message indicating that the action completed successfully.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'post_id' => array(
					'description' => __( 'The post ID of the generated email post.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Validate the email ID.
	 *
	 * @param string $email_id The email ID to validate.
	 * @return bool|WP_Error True if the email ID is valid, otherwise a WP_Error object.
	 */
	private function validate_email_id( string $email_id ) {
		if ( ! in_array( $email_id, WCTransactionalEmails::get_transactional_emails(), true ) ) {
			return new \WP_Error(
				'woocommerce_rest_not_allowed_email_id',
				sprintf( 'The provided email ID "%s" is not allowed.', $email_id ),
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
	 * Handle the POST /settings/email/listing/recreate-email-post.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function recreate_email_post( WP_REST_Request $request ) {
		$email_id = $request->get_param( 'email_id' );

		$generated_post_id = '';

		try {
			$generated_post_id = $this->email_template_generator->generate_email_template_if_not_exists( $email_id );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_email_post_generation_failed',
				// translators: %s: Error message.
				sprintf( __( 'Error generating email post. Error: %s.', 'woocommerce' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		if ( $generated_post_id ) {
			return array(
				// translators: %s: WooCommerce transactional email ID.
				'message' => sprintf( __( 'Email post generated for %s.', 'woocommerce' ), $email_id ),
				'post_id' => (string) $generated_post_id,
			);
		}
		return new WP_Error(
			'woocommerce_rest_email_post_generation_error',
			__( 'Error unable to generate email post.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}
}
