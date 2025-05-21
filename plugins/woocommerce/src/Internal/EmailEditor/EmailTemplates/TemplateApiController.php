<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * API Controller for managing WooCommerce email templates via extending the post type API.
 *
 * @internal
 */
class TemplateApiController {
	/**
	 * Returns the sender settings for the given template.
	 *
	 * @param array $template_data - WP_Block_Template data.
	 * @return array
	 */
	public function get_template_data( $template_data ): array {
		$template_slug = $template_data['slug'] ?? null;
		if ( WooEmailTemplate::TEMPLATE_SLUG !== $template_slug ) {
			return array();
		}

		return array(
			'sender_settings' => array(
				'from_name'    => get_option( 'woocommerce_email_from_name' ),
				'from_address' => get_option( 'woocommerce_email_from_address' ),
			),
		);
	}

	/**
	 * Update WooCommerce specific data we store with Template.
	 *
	 * @param array              $data - WP_Block_Template data.
	 * @param \WP_Block_Template $template_post - WP_Block_Template object.
	 * @throws \InvalidArgumentException If the email address is invalid.
	 */
	public function save_template_data( array $data, \WP_Block_Template $template_post ): void {
		if ( WooEmailTemplate::TEMPLATE_SLUG === $template_post->slug && isset( $data['sender_settings'] ) ) {
			$new_from_name     = $data['sender_settings']['from_name'] ?? null;
			$current_from_name = get_option( 'woocommerce_email_from_name' );

			if ( null !== $new_from_name && $new_from_name !== $current_from_name ) {
				update_option( 'woocommerce_email_from_name', $new_from_name );
			}

			$new_from_address = $data['sender_settings']['from_address'] ?? null;
			// This validation matches HTML input type email validation.
			// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/email#validation.
			$email_validation_pattern = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';
			if ( null === $new_from_address || ! preg_match( $email_validation_pattern, $new_from_address ) ) {
				throw new \InvalidArgumentException( esc_html( __( 'Invalid email address provided for sender settings', 'woocommerce' ) ) );
			}

			$current_from_address = get_option( 'woocommerce_email_from_address' );
			if ( $new_from_address !== $current_from_address ) {
				update_option( 'woocommerce_email_from_address', $new_from_address );
			}
		}
	}

	/**
	 * Get the schema for the template data.
	 *
	 * @return array
	 */
	public function get_template_data_schema(): array {
		return Builder::object(
			array(
				'sender_settings' => Builder::object(
					array(
						'preheader'   => Builder::string(),
						'preview_url' => Builder::string(),
					)
				),
			)
		)->to_array();
	}
}
