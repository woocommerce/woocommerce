<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Internal\EmailEditor\Integration;

class WCEmailTemplateManager {
	private static $instance = null;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// Get email post by type
	public function get_email_post($email_type) {
		$args = [
			'post_type' => Integration::EMAIL_POST_TYPE,
			'name' => $email_type,
			'posts_per_page' => 1,
		];

		$query = new \WP_Query($args);
		return $query->posts[0] ?? null;
	}

	// Check if email template exists
	public function template_exists($email_type) {
		return null !== $this->get_email_post($email_type);
	}


	public function save_email_template_post_id( $email_type, $post_id ) {
		$option_name = 'woocommerce_email_templates_' . $email_type . '_post_id';
		update_option( $option_name, $post_id );
	}

	public function get_email_template_post_id( $email_type ) {
		$option_name = 'woocommerce_email_templates_' . $email_type . '_post_id';
		return get_option( $option_name );
	}
}
