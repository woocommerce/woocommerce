<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Emails;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;
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
	 * Creates an auto-draft email post for the given email type, or returns an existing
	 * published/auto-draft post if one already exists. This follows the WordPress Site Editor
	 * pattern where posts are only created when the user opens the editor.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function recreate_email_post( WP_REST_Request $request ) {
		$email_id = $request->get_param( 'email_id' );

		$post_manager = WCTransactionalEmailPostsManager::get_instance();

		// Check for existing published post first.
		$existing_post_id = $post_manager->get_email_template_post_id( $email_id );
		if ( $existing_post_id ) {
			$post = get_post( $existing_post_id );
			if ( $post instanceof \WP_Post ) {
				return array(
					'message' => sprintf(
						// translators: %s: WooCommerce transactional email ID.
						__( 'Email post already exists for %s.', 'woocommerce' ),
						$email_id
					),
					'post_id' => (string) $existing_post_id,
				);
			}
		}

		// Check for existing auto-draft.
		$existing_auto_draft = $this->find_auto_draft_for_email_type( $email_id );
		if ( $existing_auto_draft ) {
			return array(
				'message' => sprintf(
					// translators: %s: WooCommerce transactional email ID.
					__( 'Email auto-draft exists for %s.', 'woocommerce' ),
					$email_id
				),
				'post_id' => (string) $existing_auto_draft->ID,
			);
		}

		// Create a new auto-draft with file template content.
		try {
			$post_id = $this->create_auto_draft( $email_id );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_email_post_generation_failed',
				// translators: %s: Error message.
				sprintf( __( 'Error generating email post. Error: %s.', 'woocommerce' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		if ( $post_id ) {
			return array(
				// translators: %s: WooCommerce transactional email ID.
				'message' => sprintf( __( 'Email auto-draft created for %s.', 'woocommerce' ), $email_id ),
				'post_id' => (string) $post_id,
			);
		}

		return new WP_Error(
			'woocommerce_rest_email_post_generation_error',
			__( 'Error unable to generate email post.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Find an existing auto-draft post for the given email type.
	 *
	 * @param string $email_id The email type identifier.
	 * @return \WP_Post|null The auto-draft post if found, null otherwise.
	 */
	private function find_auto_draft_for_email_type( string $email_id ): ?\WP_Post {
		$posts = get_posts(
			array(
				'post_type'   => Integration::EMAIL_POST_TYPE,
				'post_status' => 'auto-draft',
				'meta_key'    => '_wc_email_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $email_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'numberposts' => 1,
			)
		);

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * Create an auto-draft email post with file template content.
	 *
	 * @param string $email_id The email type identifier.
	 * @return int The post ID of the created auto-draft.
	 * @throws \Exception When the email type is not found or post creation fails.
	 */
	private function create_auto_draft( string $email_id ): int {
		$file_content = WCTransactionalEmailPostsGenerator::get_file_template_content( $email_id );
		if ( null === $file_content ) {
			throw new \Exception(
				esc_html( sprintf( 'Could not load file template for email type: %s', $email_id ) )
			);
		}

		$emails = \WC()->mailer()->get_emails();
		$email  = null;
		foreach ( $emails as $e ) {
			if ( $e->id === $email_id ) {
				$email = $e;
				break;
			}
		}

		if ( ! $email ) {
			throw new \Exception(
				esc_html( sprintf( 'Email type not found: %s', $email_id ) )
			);
		}

		$post_data = array(
			'post_type'    => Integration::EMAIL_POST_TYPE,
			'post_status'  => 'auto-draft',
			'post_name'    => $email_id,
			'post_title'   => $email->get_title(),
			'post_excerpt' => $email->get_description(),
			'post_content' => $file_content,
			'meta_input'   => array(
				'_wp_page_template' => ( new WooEmailTemplate() )->get_slug(),
				'_wc_email_type'    => $email_id,
			),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( esc_html( $post_id->get_error_message() ) );
		}

		return $post_id;
	}
}
