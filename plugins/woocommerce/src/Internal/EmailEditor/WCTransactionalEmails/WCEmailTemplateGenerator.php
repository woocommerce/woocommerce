<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;

class WCEmailTemplateGenerator {
	/**
	 * WooCommerce Email Template Manager instance.
	 * @var WCEmailTemplateManager
	 */
	private $template_manager;

	/**
	 * Default templates.
	 * @var array
	 */
	private $default_templates = [];

	public function __construct() {
		$this->template_manager = WCEmailTemplateManager::get_instance();
	}

	/**
	 * Initialize the email template generator.
	 *
	 * This function initializes the email template generator by loading the default templates and generating initial email templates if needed.
	 */
	public function init() {
		$this->init_default_templates();
		$this->generate_initial_email_templates();
	}

	/**
	 * Initialize the default templates.
	 *
	 * This function initializes the default templates for the core transactional emails.
	 * It fetches all the emails from WooCommerce and filters them to include only the core transactional emails.
	 *
	 */
	private function init_default_templates() {
		$core_transactional_emails = WCTransactionalEmails::$core_transactional_emails;

		$wc_emails = \WC_Emails::instance();
		$email_types = $wc_emails->get_emails();

		// Filter the emails to include only the core transactional emails.
		$email_types = array_filter( $email_types, function( $email ) use ( $core_transactional_emails ) {
			return in_array( $email->id, $core_transactional_emails );
		} );

		$this->default_templates = array_reduce( $email_types, function( $acc, $email ) {
			$acc[ $email->id ] = [
				'title' => $email->title,
				'content' => $this->get_email_template( $email ),
				'enabled' => $email->is_enabled(),
			];
			return $acc;
		}, [] );
	}

	/**
	 * Get the email template for the given email.
	 *
	 * This is a temporary solution to get the initial email block content.
	 *
	 * @param \WC_Email $email The email object.
	 * @return string The email template.
	 */
	public function get_email_template( $email ) {
		return wc_get_template_html(
			 str_replace('plain', 'block', $email->template_plain),
			array(
				'order'              => $email->object,
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $email,
			)
		);
	}

	/**
	 * Generate initial email templates.
	 *
	 * This function generates the initial email templates for the core transactional emails.
	 * It checks if the templates are already generated and if not, it generates them.
	 *
	 * @return bool True if the templates are generated, false otherwise.
	 */
	public function generate_initial_email_templates() {

		if ( get_option( 'wc_email_editor_initial_templates_generated' ) ) {
			// if templates are already generated, we don't need to run this function again
			return true;
		}

		$core_transactional_emails = WCTransactionalEmails::$core_transactional_emails;

		$templates_to_generate = [];
		foreach ( $core_transactional_emails as $email_type ) {
			if ( empty( $this->template_manager->get_email_template_post_id( $email_type ) ) ) {
				$templates_to_generate[] = $email_type;
			}
		}

		if ( ! empty( $templates_to_generate ) ) {
			return $this->generate_email_templates( $templates_to_generate );
		}

		update_option( 'wc_email_editor_initial_templates_generated', true );
		return true;
	}

	/**
	 * Generate email template if it doesn't exist.
	 *
	 * This function generates an email template if it doesn't exist.
	 *
	 * @param string $email_type The email type.
	 */
	public function generate_email_template_if_not_exists( $email_type ) {
		$template_data = $this->default_templates[ $email_type ];

		if ( $this->template_manager->get_email_template_post_id( $email_type ) || empty( $template_data ) ) {
			return true;
		}

		return $this->generate_single_template( $email_type, $template_data );
	}

	/**
	 * Generate email templates.
	 *
	 * This function generates the email templates for the given email types.
	 *
	 * @param array $templates_to_generate The email types to generate.
	 */
	public function generate_email_templates( $templates_to_generate ) {
		global $wpdb;

		$templates = array_filter( $this->default_templates, function( $email_template_id ) use ( $templates_to_generate ) {
			return in_array( $email_template_id, $templates_to_generate );
		}, ARRAY_FILTER_USE_KEY);

		if ( empty( $templates ) ) {
			return true;
		}

		// Start transaction
		$wpdb->query('START TRANSACTION');

		try {
			foreach ( $templates as $email_type => $template) {
				$this->generate_single_template($email_type, $template);
			}

			$wpdb->query('COMMIT');
			return true;

		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');
			return new \WP_Error('email_generation_failed', $e->getMessage());
		}
	}

	/**
	 * Generate a single email template.
	 *
	 * This function generates a single email template post and sets its postmeta association.
	 *
	 * @param string $email_type The email type.
	 * @param array $template_data The template data.
	 * @return int The post ID of the generated template.
	 */
	private function generate_single_template($email_type, $template_data) {
		$post_data = [
			'post_type' => Integration::EMAIL_POST_TYPE,
			'post_status' => $template_data['enabled'] ? 'publish' : 'draft',
			'post_name' => $email_type,
			'post_title' => $template_data['title'],
			'post_content' => $template_data['content'],
			'meta_input' => [
				'_wc_email_enabled' => $template_data['enabled'],
				'_wc_email_type' => $email_type,
				'_wp_page_template' => ( new WooEmailTemplate() )->get_slug(),
			]
		];

		$post_id = wp_insert_post($post_data, true);

		if (is_wp_error($post_id)) {
			throw new \Exception($post_id->get_error_message());
		}

		$this->template_manager->save_email_template_post_id( $email_type, $post_id );

		return $post_id;
	}
}
