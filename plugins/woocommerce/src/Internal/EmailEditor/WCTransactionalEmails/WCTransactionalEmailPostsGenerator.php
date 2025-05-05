<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Class WCTransactionalEmailPostsGenerator
 *
 * Handles the generation of WooCommerce transactional email templates.
 * This class is responsible for initializing and managing default email templates,
 * as well as generating new templates when required.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 */
class WCTransactionalEmailPostsGenerator {
	/**
	 * WooCommerce Email Template Manager instance.
	 *
	 * @var WCTransactionalEmailPostsManager
	 */
	private $template_manager;

	/**
	 * Default templates.
	 *
	 * @var array<string, \WC_Email>
	 */
	private $default_templates = array();

	/**
	 * Transient name.
	 *
	 * @var string
	 */
	private $transient_name = 'wc_email_editor_initial_templates_generated';

	/**
	 * Constructor.
	 *
	 * Initializes the WCTransactionalEmailPostsGenerator by setting up the template manager.
	 */
	public function __construct() {
		$this->template_manager = WCTransactionalEmailPostsManager::get_instance();
	}

	/**
	 * Initialize the email template generator.
	 *
	 * This function initializes the email template generator by loading the default templates
	 * and generating initial email templates if needed.
	 *
	 * @internal
	 */
	public function initialize() {
		if ( Constants::get_constant( 'WC_VERSION' ) === get_transient( $this->transient_name ) ) {
			// if templates are already generated, we don't need to run this function again.
			return true;
		}

		$this->init_default_transactional_emails();
		$this->generate_initial_email_templates();
	}

	/**
	 * Initialize the default WooCommerce Transactional Emails.
	 *
	 * This function initializes the default templates for the core transactional emails.
	 * It fetches all the emails from WooCommerce and filters them to include only the core transactional emails.
	 */
	public function init_default_transactional_emails() {
		$core_transactional_emails = WCTransactionalEmails::get_transactional_emails();

		$wc_emails = \WC_Emails::instance();
		/**
		 * WooCommerce Transactional Emails instance.
		 *
		 * @var \WC_Email[]
		 */
		$email_types = $wc_emails->get_emails();

		// Filter the emails to include only the core transactional emails.
		$email_types = array_filter(
			$email_types,
			function ( $email ) use ( $core_transactional_emails ) {
				return in_array( $email->id, $core_transactional_emails, true );
			}
		);

		$this->default_templates = array_reduce(
			$email_types,
			function ( $acc, $email ) {
				$acc[ $email->id ] = $email;
				return $acc;
			},
			array()
		);
	}

	/**
	 * Get the email template for the given email.
	 *
	 * Looks for the initial email block content in plugins/woocommerce/templates/emails/block.
	 *
	 * @param \WC_Email $email The email object.
	 * @return string The email template.
	 */
	public function get_email_template( $email ) {
		$template_name = str_replace( 'plain', 'block', $email->template_plain );

		try {
			$template_html = wc_get_template_html(
				$template_name,
				array()
			);
		} catch ( \Exception $e ) {
			$template_html = '';
		}

		// wc_get_template_html does not throw an error when the template is not found.
		// We need to check if the template is not found by checking the template_html content.
		$has_template_error =
			StringUtil::contains( $template_html, 'No such file or directory', false ) ||
			StringUtil::contains( $template_html, 'Failed to open stream', false ) ||
			StringUtil::contains( $template_html, 'Warning: include', false );

		if ( is_wp_error( $template_html ) || empty( $template_html ) || $has_template_error ) {
			$default_template_name = 'emails/block/default-block-content.php';
			$template_html         = wc_get_template_html(
				$default_template_name,
				array()
			);
		}

		return $template_html;
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
		$core_transactional_emails = WCTransactionalEmails::get_transactional_emails();

		$templates_to_generate = array();
		foreach ( $core_transactional_emails as $email_type ) {
			if ( empty( $this->template_manager->get_email_template_post_id( $email_type ) ) ) {
				$templates_to_generate[] = $email_type;
			}
		}

		if ( empty( $templates_to_generate ) ) {
			return;
		}

		$result = $this->generate_email_templates( $templates_to_generate );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		set_transient( $this->transient_name, Constants::get_constant( 'WC_VERSION' ), MONTH_IN_SECONDS );
		return true;
	}

	/**
	 * Generate email template if it doesn't exist.
	 *
	 * This function generates an email template if it doesn't exist.
	 *
	 * @param string $email_type The email type.
	 * @return int The post ID of the generated template.
	 * @throws \Exception When post creation fails.
	 */
	public function generate_email_template_if_not_exists( $email_type ) {
		$email_data = $this->default_templates[ $email_type ];

		if ( $this->template_manager->get_email_template_post_id( $email_type ) || empty( $email_data ) ) {
			return $this->template_manager->get_email_template_post_id( $email_type );
		}

		return $this->generate_single_template( $email_type, $email_data );
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

		$core_emails = array_filter(
			$this->default_templates,
			function ( $email_id ) use ( $templates_to_generate ) {
				return in_array( $email_id, $templates_to_generate, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( empty( $core_emails ) ) {
			return false;
		}

		// Start transaction.
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $core_emails as $email_type => $email_data ) {
				$this->generate_single_template( $email_type, $email_data );
			}

			$wpdb->query( 'COMMIT' );
			return true;

		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'email_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Generate a single email template.
	 *
	 * This function generates a single email template post and sets its postmeta association.
	 *
	 * @param string    $email_type    The email type.
	 * @param \WC_Email $email_data The transactional email data.
	 * @return int The post ID of the generated template.
	 * @throws \Exception When post creation fails.
	 */
	private function generate_single_template( $email_type, $email_data ) {
		$email_enabled = $email_data->is_enabled() || $email_data->is_manual();
		$post_data     = array(
			'post_type'    => Integration::EMAIL_POST_TYPE,
			'post_status'  => $email_enabled ? 'publish' : 'draft',
			'post_name'    => $email_type,
			'post_title'   => $email_data->get_title(),
			'post_excerpt' => $email_data->get_description(),
			'post_content' => $this->get_email_template( $email_data ),
			'meta_input'   => array(
				'_wp_page_template' => ( new WooEmailTemplate() )->get_slug(),
			),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( esc_html( $post_id->get_error_message() ) );
		}

		$this->template_manager->save_email_template_post_id( $email_type, $post_id );

		return $post_id;
	}
}
