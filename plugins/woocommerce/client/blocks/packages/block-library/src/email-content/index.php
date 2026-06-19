<?php
/**
 * Server-side rendering of the `woocommerce/email-content` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;

/**
 * Renders the email content preview for the editor.
 *
 * @since 11.0.0
 *
 * @param array $attributes Block attributes.
 * @return string Rendered email content preview.
 */
function render_block_woocommerce_email_content_preview( array $attributes ): string {
	$email_preview = wc_get_container()->get( EmailPreview::class );
	$type_param    = EmailPreview::DEFAULT_EMAIL_TYPE;

	if ( isset( $attributes['postId'] ) ) {
		$email_type_class_name = WCTransactionalEmailPostsManager::get_instance()->get_email_type_class_name_from_post_id( $attributes['postId'] );
		$type_param            = ! empty( $email_type_class_name ) ? $email_type_class_name : $type_param;
	} elseif ( isset( $attributes['emailType'] ) ) {
		$type_param = sanitize_text_field( wp_unslash( $attributes['emailType'] ) );
	}

	try {
		return $email_preview->generate_placeholder_content( $type_param );
	} catch ( Exception $e ) {
		return esc_html__( 'There was an error rendering the email preview.', 'woocommerce' );
	}
}

/**
 * Renders the `woocommerce/email-content` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered email content block.
 */
function render_block_woocommerce_email_content( $attributes, $content, $block ): string {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['context'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['context'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return render_block_woocommerce_email_content_preview( $attributes );
	}

	return BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER;
}

/**
 * Registers the `woocommerce/email-content` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_email_content(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_email_content',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_email_content' );
