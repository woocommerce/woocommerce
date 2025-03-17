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
	private $default_templates = [];

	public function __construct() {
		$this->template_manager = WCEmailTemplateManager::get_instance();
	}

	public function init() {
		$this->init_default_templates();
		$this->generate_initial_email_templates();
	}

	private function init_default_templates() {
		$this->default_templates = [
			'new_order' => [
				'title' => __('New Order', 'woocommerce'),
				'content' => $this->get_new_order_template(),
				'enabled' => true
			],
			'customer_processing_order' => [
				'title' => __('Processing Order', 'woocommerce'),
				'content' => $this->get_processing_order_template(),
				'enabled' => true
			],
			'customer_note' => [
				'title' => __('Customer Note', 'woocommerce'),
				'content' => $this->get_customer_note_template(),
				'enabled' => true
			],
			// other email types...
		];
	}

	public function generate_initial_email_templates() {
		if ( empty( $this->template_manager->get_email_template_post_id( 'new_order' ) ) ) {
			return $this->generate_email_templates();
		}

		return true;
	}

	public function generate_email_template_if_not_exists( $email_type ) {
		if ( ! $this->template_manager->template_exists( $email_type ) ) {
			$this->generate_single_template( $email_type, $this->default_templates[ $email_type ] );
		}
	}

	public function generate_email_templates() {
		global $wpdb;

		// Start transaction
		$wpdb->query('START TRANSACTION');

		try {
			foreach ($this->default_templates as $email_type => $template) {
				$this->generate_single_template($email_type, $template);
			}

			$wpdb->query('COMMIT');
			return true;

		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');
			return new \WP_Error('email_generation_failed', $e->getMessage());
		}
	}

	private function generate_single_template($email_type, $template_data) {
		// Skip if template already exists
		if ($this->template_manager->template_exists($email_type)) {
			return;
		}

		$post_data = [
			'post_type' => Integration::EMAIL_POST_TYPE,
			'post_status' => 'publish',
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

	private function get_new_order_template() {
		return '<!-- wp:heading -->
		<h2>' . __('New Order Received', 'woocommerce') . '</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>' . __('You have received a new order.', 'woocommerce') . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
		<div class="wp-block-woo-email-content">' . BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER . '</div>
		<!-- /wp:woo/email-content -->';
	}

	private function get_processing_order_template() {
		return '<!-- wp:heading -->
		<h2>' . __('Processing Order', 'woocommerce') . '</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>' . __('Your order is currently being processed.', 'woocommerce') . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
		<div class="wp-block-woo-email-content">' . BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER . '</div>
		<!-- /wp:woo/email-content -->';
	}

	private function get_customer_note_template() {
		return '<!-- wp:heading -->
		<h2>' . __('Customer Note', 'woocommerce') . '</h2>
		<!-- /wp:heading -->

		<!-- wp:woo/email-content {"lock":{"move":false,"remove":true}} -->
		<div class="wp-block-woo-email-content">' . BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER . '</div>
		<!-- /wp:woo/email-content -->';
	}
}
