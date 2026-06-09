<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateChangeSummary;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateAutoApplier;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSelectiveApplier;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use WC_Email;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * API Controller for managing WooCommerce email templates via extending the post type API.
 *
 * @internal
 */
class EmailApiController {

	/**
	 * The WooCommerce transactional email post manager.
	 *
	 * @var WCTransactionalEmailPostsManager|null
	 */
	private ?WCTransactionalEmailPostsManager $post_manager = null;

	/**
	 * The WooCommerce transactional email posts generator.
	 *
	 * @var WCTransactionalEmailPostsGenerator|null
	 */
	private ?WCTransactionalEmailPostsGenerator $posts_generator = null;

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 */
	final public function init(): void {
		$this->post_manager    = WCTransactionalEmailPostsManager::get_instance();
		$this->posts_generator = new WCTransactionalEmailPostsGenerator();
	}

	/**
	 * Returns the data from wp_options table for the given post.
	 *
	 * @param array $post_data - Post data.
	 * @return array - The email data.
	 */
	public function get_email_data( $post_data ): array {
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_data['id'] );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		// When the email type is not found, it means that the email type is not supported.
		if ( ! $email ) {
			return array(
				'subject'         => null,
				'subject_full'    => null,
				'subject_partial' => null,
				'preheader'       => null,
				'default_subject' => null,
				'email_type'      => null,
				'recipient'       => null,
				'cc'              => null,
				'bcc'             => null,
			);
		}

		$form_fields = $email->get_form_fields();
		$enabled     = $email->get_option( 'enabled' );
		return array(
			'enabled'         => is_null( $enabled ) ? $email->is_enabled() : 'yes' === $enabled,
			'is_manual'       => $email->is_manual(),
			'subject'         => $email->get_option( 'subject' ),
			'subject_full'    => $email->get_option( 'subject_full' ), // For customer_refunded_order email type because it has two different subjects.
			'subject_partial' => $email->get_option( 'subject_partial' ),
			'preheader'       => $email->get_option( 'preheader' ),
			'default_subject' => $email->get_default_subject(),
			'email_type'      => $email_type,
			// Recipient is possible to set only for the specific type of emails. When the field `recipient` is set in the form fields, it means that the email type has a recipient field.
			'recipient'       => array_key_exists( 'recipient', $form_fields ) ? $email->get_option( 'recipient', get_option( 'admin_email' ) ) : null,
			'cc'              => $email->get_option( 'cc' ),
			'bcc'             => $email->get_option( 'bcc' ),
		);
	}

	/**
	 * Update WooCommerce specific option data by post name.
	 *
	 * @param array    $data - Data that are stored in the wp_options table.
	 * @param \WP_Post $post - WP_Post object.
	 * @return \WP_Error|null Returns WP_Error if email validation fails, null otherwise.
	 */
	public function save_email_data( array $data, \WP_Post $post ): ?\WP_Error {
		$error = $this->validate_email_data( $data );
		if ( is_wp_error( $error ) ) {
			return new \WP_Error( 'invalid_email_data', implode( ' ', $error->get_error_messages() ), array( 'status' => 400 ) );
		}

		if ( ! array_key_exists( 'subject', $data ) && ! array_key_exists( 'preheader', $data ) ) {
			return null;
		}
		$email_type = $this->post_manager->get_email_type_from_post_id( $post->ID );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return null; // not saving of type wc_email. Allow process to continue.
		}

		// Handle customer_refunded_order email type because it has two different subjects.
		if ( 'customer_refunded_order' === $email_type ) {
			if ( array_key_exists( 'subject_full', $data ) ) {
				$email->update_option( 'subject_full', $data['subject_full'] );
			}
			if ( array_key_exists( 'subject_partial', $data ) ) {
				$email->update_option( 'subject_partial', $data['subject_partial'] );
			}
		} elseif ( array_key_exists( 'subject', $data ) ) {
			$email->update_option( 'subject', $data['subject'] );
		}

		if ( array_key_exists( 'preheader', $data ) ) {
			$email->update_option( 'preheader', $data['preheader'] );
		}

		if ( array_key_exists( 'enabled', $data ) ) {
			$email->update_option( 'enabled', $data['enabled'] ? 'yes' : 'no' );
		}
		if ( array_key_exists( 'recipient', $data ) ) {
			$email->update_option( 'recipient', $data['recipient'] );
		}
		if ( array_key_exists( 'cc', $data ) ) {
			$email->update_option( 'cc', $data['cc'] );
		}
		if ( array_key_exists( 'bcc', $data ) ) {
			$email->update_option( 'bcc', $data['bcc'] );
		}

		return null;
	}

	/**
	 * Validate the email data.
	 *
	 * @param array $data - The email data.
	 * @return \WP_Error|null Returns WP_Error if email validation fails, null otherwise.
	 */
	private function validate_email_data( array $data ) {
		$error = new \WP_Error();

		// Validate 'recipient' email(s) field.
		$invalid_recipients = $this->filter_invalid_email_addresses( $data['recipient'] ?? '' );
		if ( ! empty( $invalid_recipients ) ) {
			$error_message = sprintf(
				// translators: %s will be replaced by comma-separated email addresses. For example, "invalidemail1@example.com,invalidemail2@example.com".
				__( 'One or more Recipient email addresses are invalid: “%s”. Please enter valid email addresses separated by commas.', 'woocommerce' ),
				implode( ',', $invalid_recipients )
			);
			$error->add( 'invalid_recipient_email_address', $error_message );
		}

		// Validate 'cc' email(s) field.
		$invalid_cc = $this->filter_invalid_email_addresses( $data['cc'] ?? '' );
		if ( ! empty( $invalid_cc ) ) {
			$error_message = sprintf(
				// translators: %s will be replaced by comma-separated email addresses. For example, "invalidemail1@example.com,invalidemail2@example.com".
				__( 'One or more CC email addresses are invalid: “%s”. Please enter valid email addresses separated by commas.', 'woocommerce' ),
				implode( ',', $invalid_cc )
			);
			$error->add( 'invalid_cc_email_address', $error_message );
		}

		// Validate 'bcc' email(s) field.
		$invalid_bcc = $this->filter_invalid_email_addresses( $data['bcc'] ?? '' );
		if ( ! empty( $invalid_bcc ) ) {
			$error_message = sprintf(
				// translators: %s will be replaced by comma-separated email addresses. For example, "invalidemail1@example.com,invalidemail2@example.com".
				__( 'One or more BCC email addresses are invalid: “%s”. Please enter valid email addresses separated by commas.', 'woocommerce' ),
				implode( ',', $invalid_bcc )
			);
			$error->add( 'invalid_bcc_email_address', $error_message );
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		return null;
	}

	/**
	 * Filter in invalid email addresses from a comma-separated string.
	 *
	 * @param string $comma_separated_email_addresses - A comma-separated string of email addresses.
	 * @return array - An array of invalid email addresses.
	 */
	private function filter_invalid_email_addresses( $comma_separated_email_addresses ) {
		$invalid_email_addresses = array();

		if ( empty( trim( $comma_separated_email_addresses ) ) ) {
			return $invalid_email_addresses;
		}

		foreach ( explode( ',', $comma_separated_email_addresses ) as $email_address ) {
			if ( ! filter_var( trim( $email_address ), FILTER_VALIDATE_EMAIL ) ) {
				$invalid_email_addresses[] = trim( $email_address );
			}
		}

		return $invalid_email_addresses;
	}

	/**
	 * Get the schema for the WooCommerce email post data.
	 *
	 * @return array
	 */
	public function get_email_data_schema(): array {
		return Builder::object(
			array(
				'subject'         => Builder::string()->nullable(),
				'subject_full'    => Builder::string()->nullable(), // For customer_refunded_order email type because it has two different subjects.
				'subject_partial' => Builder::string()->nullable(),
				'preheader'       => Builder::string()->nullable(),
				'default_subject' => Builder::string()->nullable(),
				'email_type'      => Builder::string()->nullable(),
				'recipient'       => Builder::string()->nullable(),
				'cc'              => Builder::string()->nullable(),
				'bcc'             => Builder::string()->nullable(),
			)
		)->to_array();
	}

	/**
	 * Get all WooCommerce emails.
	 *
	 * @return \WC_Email[]
	 */
	protected function get_emails(): array {
		return WC()->mailer()->get_emails();
	}

	/**
	 * Get the email object by ID.
	 *
	 * @param string $id - The email ID.
	 * @return \WC_Email|null - The email object or null if not found.
	 */
	private function get_email_by_type( ?string $id ): ?WC_Email {
		foreach ( $this->get_emails() as $email ) {
			if ( $email->id === $id ) {
				return $email;
			}
		}
		return null;
	}

	/**
	 * Register REST API routes for the email API controller.
	 */
	public function register_routes(): void {
		register_rest_route(
			'woocommerce-email-editor/v1',
			'/emails/(?P<id>\d+)/default-content',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_default_content_response' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'id' => array(
						'description'       => __( 'The ID of the woo_email post.', 'woocommerce' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
				'schema'              => array( $this, 'get_default_content_schema' ),
			)
		);

		register_rest_route(
			'woocommerce-email-editor/v1',
			'/emails/(?P<id>\d+)/reset',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reset_response' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'id' => array(
						'description'       => __( 'The ID of the woo_email post.', 'woocommerce' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
				'schema'              => array( $this, 'get_reset_schema' ),
			)
		);

		register_rest_route(
			'woocommerce-email-editor/v1',
			'/emails/(?P<id>\d+)/change-summary',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_change_summary_response' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'id' => array(
						'description'       => __( 'The ID of the woo_email post.', 'woocommerce' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
				'schema'              => array( $this, 'get_change_summary_schema' ),
			)
		);

		register_rest_route(
			'woocommerce-email-editor/v1',
			'/emails/(?P<id>\d+)/apply',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_response' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'id'      => array(
						'description'       => __( 'The ID of the woo_email post.', 'woocommerce' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'choices' => array(
						'description' => __( 'Per-conflict apply decisions: an array of {path, decision} entries. `decision` is `keep_yours` or `use_core`.', 'woocommerce' ),
						'type'        => 'array',
						'required'    => false,
						'default'     => array(),
					),
				),
				'schema'              => array( $this, 'get_apply_schema' ),
			)
		);

		register_rest_route(
			'woocommerce-email-editor/v1',
			'/emails/(?P<id>\d+)/undo',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'undo_response' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'id'          => array(
						'description'       => __( 'The ID of the woo_email post.', 'woocommerce' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'revision_id' => array(
						'description' => __( 'The revision_id returned by the prior /apply call.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				'schema'              => array( $this, 'get_undo_schema' ),
			)
		);
	}

	/**
	 * Get the schema for the default content endpoint response.
	 *
	 * @return array
	 */
	public function get_default_content_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'woo_email_default_content',
			'type'       => 'object',
			'properties' => array(
				'content' => array(
					'description' => __( 'The default block content for the email.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Return the default (plugin-distributed) block content for a woo_email post.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_default_content_response( WP_REST_Request $request ) {
		if ( ! ( $this->post_manager && $this->posts_generator ) ) {
			return new WP_Error(
				'woocommerce_email_editor_not_initialized',
				__( 'Email editor is not initialized.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$post_id    = (int) $request->get_param( 'id' );
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_id );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_email_not_found',
				__( 'No email found for the given post ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array( 'content' => $this->posts_generator->get_email_template( $email ) ),
			200
		);
	}

	/**
	 * Get the schema for the reset endpoint response.
	 *
	 * @return array
	 */
	public function get_reset_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'woo_email_reset',
			'type'       => 'object',
			'properties' => array(
				'content'     => array(
					'description' => __( 'The canonical block content written to the post.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'version'     => array(
					'description' => __( 'The core block template @version stamped on the post, or null when the email is not sync-enabled.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'readonly'    => true,
				),
				'source_hash' => array(
					'description' => __( 'sha1 of the canonical block content stamped on the post, or null when the email is not sync-enabled.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'readonly'    => true,
				),
				'synced_at'   => array(
					'description' => __( 'UTC timestamp when the post was stamped (Y-m-d H:i:s), or null when the email is not sync-enabled.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'readonly'    => true,
				),
				'status'      => array(
					'description' => __( 'The post-reset sync status (in_sync on success for sync-enabled emails, null otherwise).', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Reset a `woo_email` post to its current core template render and (when sync-enabled) stamp sync meta.
	 *
	 * Writes the canonical post content (byte-identical to what
	 * {@see WCTransactionalEmailPostsGenerator} would produce on a fresh recreate). For emails
	 * that are opted in to template sync (registered in {@see WCEmailTemplateSyncRegistry}),
	 * also stamps `_wc_email_template_version`, `_wc_email_template_source_hash`,
	 * `_wc_email_last_synced_at`, and `_wc_email_template_status = in_sync`. Meta writes are
	 * conditional on the post update succeeding, so a `wp_update_post` failure leaves the
	 * post — and any pre-existing meta — untouched.
	 *
	 * Non-sync-enabled emails (e.g. third-party templates without an `@version` header)
	 * still receive a successful content reset, just without the meta stamp. This mirrors
	 * the pre-RSM-148 behaviour where the standalone REST PUT performed the content reset
	 * and stamping was a separate side effect, preserving backward compatibility.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 10.8.0
	 */
	public function reset_response( WP_REST_Request $request ) {
		if ( ! ( $this->post_manager && $this->posts_generator ) ) {
			return new WP_Error(
				'woocommerce_email_editor_not_initialized',
				__( 'Email editor is not initialized.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$post_id    = (int) $request->get_param( 'id' );
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_id );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_email_not_found',
				__( 'No email found for the given post ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$result = WCEmailTemplateAutoApplier::apply_to_post(
			$email,
			$post_id,
			array( 'require_uncustomized' => false )
		);

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				'woocommerce_email_reset_failed',
				sprintf(
					/* translators: %s: underlying error message */
					__( 'Failed to reset email content: %s', 'woocommerce' ),
					$result->get_error_message()
				),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Get the schema for the change-summary endpoint response.
	 *
	 * @return array
	 */
	public function get_change_summary_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'woo_email_change_summary',
			'type'       => 'object',
			'properties' => array(
				'version_from'       => array(
					'description' => __( 'The template version stamped on the post (may be empty for pre-backfill posts).', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'version_to'         => array(
					'description' => __( 'The current core template version recorded in the sync registry.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'added_blocks'       => array(
					'description' => __( 'Blocks that would be added to the merchant post if the update were applied (in core, not in post). `name` is the post-alias-normalized block name (e.g. `core/heading`); `label` is its humanized form for display; `path` is the core-side index path through the parsed block tree.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'  => array( 'type' => 'string' ),
							'label' => array( 'type' => 'string' ),
							'path'  => array(
								'type'  => 'array',
								'items' => array( 'type' => array( 'integer', 'string' ) ),
							),
						),
					),
					'readonly'    => true,
				),
				'removed_blocks'     => array(
					'description' => __( 'Blocks that would be removed from the merchant post if the update were applied (in post, not in core). Same fields as `added_blocks`; `path` is the post-side index path.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'  => array( 'type' => 'string' ),
							'label' => array( 'type' => 'string' ),
							'path'  => array(
								'type'  => 'array',
								'items' => array( 'type' => array( 'integer', 'string' ) ),
							),
						),
					),
					'readonly'    => true,
				),
				'copy_changes'       => array(
					'description' => __( 'Block-level copy edits, truncated to 120 chars per side. `before` is the merchant\'s current text; `after` is the canonical core text. `path` is the post-side index path.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'block'      => array( 'type' => 'string' ),
							'before'     => array( 'type' => 'string' ),
							'after'      => array( 'type' => 'string' ),
							'occurrence' => array( 'type' => 'integer' ),
							'total'      => array( 'type' => 'integer' ),
							'path'       => array(
								'type'  => 'array',
								'items' => array( 'type' => array( 'integer', 'string' ) ),
							),
						),
					),
					'readonly'    => true,
				),
				'structural_changes' => array(
					'description' => __( 'Structural deltas (reorder / nest) between the two trees. `path` is omitted on `kind: "reorder"` entries.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'kind'        => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'path'        => array(
								'type'  => 'array',
								'items' => array( 'type' => array( 'integer', 'string' ) ),
							),
						),
					),
					'readonly'    => true,
				),
				'summary_lines'      => array(
					'description' => __( 'Pre-localized one-liners ready for direct render.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'readonly'    => true,
				),
				'is_fallback'        => array(
					'description' => __( 'True when the diff could not be produced and a generic message is returned instead.', 'woocommerce' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'cache_hit'          => array(
					'description' => __( 'Diagnostic flag indicating whether the response came from the transient cache.', 'woocommerce' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Return a localized summary of differences between the merchant's
	 * `woo_email` post and the canonical core render.
	 *
	 * Thin wrapper over {@see WCEmailTemplateChangeSummary::summarize()}. The
	 * 404 path mirrors {@see self::get_default_content_response()} — when the
	 * email type cannot be resolved from the post ID, the post is either
	 * non-existent or not a `woo_email`.
	 *
	 * The 200 path differs for valid posts that are NOT in
	 * {@see WCEmailTemplateSyncRegistry}: `default-content` returns the
	 * canonical content; `change-summary` returns a fallback payload with
	 * `is_fallback: true` and a generic release-notes line because no
	 * registered version is available to diff against. Consumers gating on
	 * `is_fallback` should treat that case as "no actionable summary,"
	 * regardless of HTTP status.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 10.9.0
	 */
	public function get_change_summary_response( WP_REST_Request $request ) {
		if ( ! ( $this->post_manager && $this->posts_generator ) ) {
			return new WP_Error(
				'woocommerce_email_editor_not_initialized',
				__( 'Email editor is not initialized.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$post_id    = (int) $request->get_param( 'id' );
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_id );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_email_not_found',
				__( 'No email found for the given post ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			WCEmailTemplateChangeSummary::summarize( $post_id ),
			200
		);
	}

	/**
	 * Get the schema for the apply endpoint response.
	 *
	 * @return array
	 */
	public function get_apply_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'woo_email_apply',
			'type'       => 'object',
			'properties' => array(
				'merged_content'     => array(
					'description' => __( 'The merged block content written to the post. May differ from the input `post_content` even when every choice was `keep_yours` — the namespace-alias migration (see `aliases_migrated`) rewrites legacy block names unconditionally.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'revision_id'        => array(
					'description' => __( 'A UUID identifying the pre-apply snapshot. Use as the revision_id on a subsequent /undo call.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'version_to'         => array(
					'description' => __( 'The core template version stamped on the post after applying.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'status'             => array(
					'description' => __( 'The post-apply status (always `applied` on success).', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'structural_skipped' => array(
					'description' => __( 'True when one or more structural deltas (nest / reorder) existed in the diff but were not applied. v1 punts structural changes; the merchant\'s structure is preserved.', 'woocommerce' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'aliases_migrated'   => array(
					'description' => __( 'List of deprecated block-name aliases rewritten to their canonical form during the apply (e.g. `["woo/email-content"]`). Empty when no migration was needed. Targeted to known deprecated aliases only.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get the schema for the undo endpoint response.
	 *
	 * @return array
	 */
	public function get_undo_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'woo_email_undo',
			'type'       => 'object',
			'properties' => array(
				'restored_content' => array(
					'description' => __( 'The pre-apply post content that was restored.', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'status'           => array(
					'description' => __( 'The post-undo status (always `restored` on success).', 'woocommerce' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Apply a partial set of core template changes to a `woo_email` post,
	 * driven by per-conflict merchant choices. Thin wrapper over
	 * {@see WCEmailTemplateSelectiveApplier::apply_selectively()}.
	 *
	 * The 404 path mirrors {@see self::get_change_summary_response()} — when
	 * the email type cannot be resolved from the post ID, the post is either
	 * non-existent or not a `woo_email`. 422 fires when the change-summary
	 * has no actionable diff (e.g. post outside the sync registry, or the
	 * inversion guard tripped).
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 10.9.0
	 */
	public function apply_response( WP_REST_Request $request ) {
		if ( ! ( $this->post_manager && $this->posts_generator ) ) {
			return new WP_Error(
				'woocommerce_email_editor_not_initialized',
				__( 'Email editor is not initialized.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$post_id    = (int) $request->get_param( 'id' );
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_id );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_email_not_found',
				__( 'No email found for the given post ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$choices = $request->get_param( 'choices' );
		if ( ! is_array( $choices ) ) {
			$choices = array();
		}

		$result = WCEmailTemplateSelectiveApplier::apply_selectively( $post_id, $choices );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Restore the pre-apply snapshot for a `woo_email` post. Thin wrapper
	 * over {@see WCEmailTemplateSelectiveApplier::undo()}.
	 *
	 * Returns 410 Gone when no snapshot exists for the given post or when
	 * the supplied `revision_id` doesn't match the latest snapshot —
	 * matches the design's single-step undo model.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 10.9.0
	 */
	public function undo_response( WP_REST_Request $request ) {
		if ( ! ( $this->post_manager && $this->posts_generator ) ) {
			return new WP_Error(
				'woocommerce_email_editor_not_initialized',
				__( 'Email editor is not initialized.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$post_id    = (int) $request->get_param( 'id' );
		$email_type = $this->post_manager->get_email_type_from_post_id( $post_id );
		$email      = $this->get_email_by_type( $email_type ?? '' );

		if ( ! $email ) {
			return new WP_Error(
				'woocommerce_email_not_found',
				__( 'No email found for the given post ID.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$revision_id = (string) $request->get_param( 'revision_id' );

		$result = WCEmailTemplateSelectiveApplier::undo( $post_id, $revision_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}
}
