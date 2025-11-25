<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\WooContentProcessor;
use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;

/**
 * EmailContent class.
 */
class EmailContent extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'email-content';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Renders the block preview for the editor.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block output.
	 */
	protected function render_preview( $attributes ) {
		/**
		 * Email preview instance for rendering dummy content.
		 *
		 * @var EmailPreview $email_preview - email preview instance
		 */
		$email_preview = wc_get_container()->get( EmailPreview::class );

		$type_param = EmailPreview::DEFAULT_EMAIL_TYPE;
		if ( isset( $attributes['emailType'] ) ) {
			$type_param = sanitize_text_field( wp_unslash( $attributes['emailType'] ) );
		}

		try {
			return $email_preview->generate_placeholder_content( $type_param );
		} catch ( \Exception $e ) {
			// Catch other potential errors during content generation.
			return esc_html__( 'There was an error rendering the email preview.', 'woocommerce' );
		}
	}

	/**
	 * Renders Woo content placeholder to be replaced by content during sending.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['context'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['context'] ) ) ) {
			// Block is being rendered for ServerSideRender editor preview.
			return $this->render_preview( $attributes );
		}

		return BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER;
	}
}
