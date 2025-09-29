<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Assets_Manager;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Template;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for rendering the email editor page.
 */
class PageRenderer {
	/**
	 * Template registry instance.
	 *
	 * @var Templates_Registry
	 */
	private Templates_Registry $template_registry;

	/**
	 * Assets manager instance.
	 *
	 * @var Assets_Manager
	 */
	private Assets_Manager $assets_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$editor_container        = Email_Editor_Container::container();
		$this->template_registry = $editor_container->get( Templates_Registry::class );

		$assets_manager = $editor_container->get( Assets_Manager::class );
		$assets_manager->set_assets_path( WC_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'email-editor/' );
		$assets_manager->set_assets_url( WC()->plugin_url() . '/' . WC_ADMIN_DIST_JS_FOLDER . 'email-editor/' );
		$this->assets_manager = $assets_manager;
	}

	/**
	 * Render the email editor page.
	 */
	public function render() {
		$post_id     = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
		$template_id = isset( $_GET['template'] ) ? sanitize_text_field( wp_unslash( $_GET['template'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
		$post_type   = $template_id ? 'wp_template' : Integration::EMAIL_POST_TYPE;
		$post_id     = $template_id ? $template_id : $post_id;

		$edited_item = $this->get_edited_item( $post_id, $post_type );

		if ( ! $edited_item ) {
			return;
		}

		add_filter( 'woocommerce_email_editor_script_localization_data', array( $this, 'update_localized_data' ) );
		// Load the email editor integration script.
		// The JS file is located in plugins/woocommerce/client/admin/client/wp-admin-scripts/email-editor-integration/index.ts.
		WCAdminAssets::register_script( 'wp-admin-scripts', 'email-editor-integration', true );
		WCAdminAssets::register_style( 'email-editor-integration', 'style', true );

		$this->assets_manager->load_editor_assets( $edited_item, 'wc-admin-email-editor-integration' );
		$this->assets_manager->render_email_editor_html();

		remove_filter(
			'woocommerce_email_editor_script_localization_data',
			array( $this, 'update_localized_data' ),
			10
		);
	}

	/**
	 * Update localized script data.
	 *
	 * @param array $localized_data Original localized data.
	 * @return array
	 */
	public function update_localized_data( array $localized_data ): array {
		// Fetch all email types from WooCommerce including those added by other plugins.
		$wc_emails   = \WC_Emails::instance();
		$email_types = $wc_emails->get_emails();
		$email_types = array_values(
			array_map(
				function ( $email ) {
					return array(
						'value' => $email->id,
						'label' => $email->title,
						'id'    => get_class( $email ),
					);
				},
				$email_types
			)
		);

		$localized_data['email_types'] = $email_types;
		// Modify email editor settings.
		$localized_data['editor_settings']['isFullScreenForced']     = true;
		$localized_data['editor_settings']['displaySendEmailButton'] = false;

		return $localized_data;
	}

	/**
	 * Check if the post can be edited in the email editor.
	 *
	 * @param int|string $id   The post ID.
	 * @param string     $type The post type.
	 * @return \WP_Post|\WP_Block_Template|null Edited item or null if the item is not found.
	 */
	private function get_edited_item( $id, string $type ) {
		// When we pass template we need to verify that the template is registered in the email template registry.
		if ( 'wp_template' === $type ) {
			$wp_template = get_block_template( $id );
			if ( ! $wp_template ) {
				return null;
			}
			$email_template = $this->template_registry->get_by_slug( $wp_template->slug );
			return $email_template instanceof Template ? $wp_template : null;
		}

		// For post we need to verify that the post is of the email type.
		$post = get_post( $id );
		if ( $post instanceof \WP_Post && $type === $post->post_type ) {
			return $post;
		}

		return null;
	}
}
