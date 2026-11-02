<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Emails;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailScratchpadRefresher;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
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
	 * Option storing the WC version for which `woo_email` rewrite rules were last flushed.
	 *
	 * Flushing rebuilds WordPress's persisted permalink routing table (the
	 * `rewrite_rules` option) so it includes the `woo_email` rules; email post
	 * permalinks (used by the listing Preview action) 404 until that happens
	 * once after the post type is registered. Rebuilding is expensive, so it
	 * runs only when this option doesn't match the current WC version.
	 *
	 * @var string
	 *
	 * @since 11.1.0
	 */
	const REWRITE_FLUSH_OPTION = 'woocommerce_email_editor_rewrites_flushed';

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
	 * Scratchpad refresher instance.
	 *
	 * @var WCEmailScratchpadRefresher
	 */
	private $scratchpad_refresher;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->email_template_generator = new WCTransactionalEmailPostsGenerator();
		$this->scratchpad_refresher     = new WCEmailScratchpadRefresher();
	}

	/**
	 * Perform the initialization.
	 *
	 * @deprecated 11.1.0 The template generator no longer needs priming; email posts are created lazily. No-op, will be removed in a future version.
	 * @return void
	 */
	public function initialize_template_generator() {
		wc_deprecated_function( __METHOD__, '11.1.0' );
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
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
	 * Returns the post for the given email type, creating a draft with the
	 * file template content when none exists yet. This follows the WordPress
	 * Site Editor pattern: posts are only created when the user opens the editor,
	 * and only become the rendering source once published.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function recreate_email_post( WP_REST_Request $request ) {
		$email_id     = $request->get_param( 'email_id' );
		$post_manager = WCTransactionalEmailPostsManager::get_instance();

		$this->maybe_flush_rewrite_rules();

		// A mapped, non-trashed post already exists (the email was saved before).
		$existing_post = $post_manager->get_email_post( $email_id );
		if ( $existing_post && 'trash' !== $existing_post->post_status ) {
			return array(
				// translators: %s: WooCommerce transactional email ID.
				'message' => sprintf( __( 'Email post already exists for %s.', 'woocommerce' ), $email_id ),
				'post_id' => (string) $existing_post->ID,
			);
		}

		$email = $post_manager->get_email_by_id( (string) $email_id );
		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_rest_email_post_generation_failed',
				// translators: %s: WooCommerce transactional email ID.
				sprintf( __( 'Error generating email post. Email type "%s" is not registered.', 'woocommerce' ), $email_id ),
				array( 'status' => 500 )
			);
		}

		// Reuse an existing editing scratchpad. When it was never edited, refresh
		// its content in place so it reflects the current file template (the post
		// ID stays stable — another admin may have the editor open on it).
		$scratchpad = $this->find_unpublished_post_for_email_type( $email_id );
		if ( $scratchpad ) {
			$this->scratchpad_refresher->maybe_refresh( $scratchpad, $email );

			return array(
				// translators: %s: WooCommerce transactional email ID.
				'message' => sprintf( __( 'Email draft already exists for %s.', 'woocommerce' ), $email_id ),
				'post_id' => (string) $scratchpad->ID,
			);
		}

		try {
			$post_id = $this->email_template_generator->create_draft( $email );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'woocommerce_rest_email_post_generation_failed',
				// translators: %s: Error message.
				sprintf( __( 'Error generating email post. Error: %s.', 'woocommerce' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		return array(
			// translators: %s: WooCommerce transactional email ID.
			'message' => sprintf( __( 'Email draft created for %s.', 'woocommerce' ), $email_id ),
			'post_id' => (string) $post_id,
		);
	}

	/**
	 * Find an existing unpublished post (editing scratchpad) for the given email type.
	 *
	 * Scratchpads are created as drafts. The `auto-draft` status is matched as
	 * well so the lookup keeps working when scratchpad creation switches to
	 * auto-drafts (planned once the Gutenberg auto-draft title blanking is
	 * fixed upstream) — including during the transition, when a site can hold
	 * a mix of both.
	 *
	 * @param string $email_id The email type identifier.
	 * @return \WP_Post|null The post if found, null otherwise.
	 */
	private function find_unpublished_post_for_email_type( string $email_id ): ?\WP_Post {
		$posts = get_posts(
			array(
				'post_type'      => Integration::EMAIL_POST_TYPE,
				'post_status'    => array( 'auto-draft', 'draft' ),
				'meta_key'       => WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Scoped to a handful of unpublished woo_email posts.
				'meta_value'     => $email_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Scoped to a handful of unpublished woo_email posts.
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);

		return $posts[0] ?? null;
	}

	/**
	 * Flush rewrite rules once per WC version so `woo_email` permalinks work.
	 *
	 * Replaces the flush that used to happen during bulk post generation.
	 */
	private function maybe_flush_rewrite_rules(): void {
		$wc_version = (string) Constants::get_constant( 'WC_VERSION' );
		if ( get_option( self::REWRITE_FLUSH_OPTION ) === $wc_version ) {
			return;
		}

		flush_rewrite_rules();
		update_option( self::REWRITE_FLUSH_OPTION, $wc_version, false );
	}
}
