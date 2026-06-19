<?php
/**
 * Server-side rendering of the `woocommerce/coming-soon` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\Jetpack\Constants;

/**
 * Server renderer for the `woocommerce/coming-soon` block.
 *
 * @since 11.0.0
 */
final class WooCommerce_Block_Library_Coming_Soon {
	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	public function render( array $attributes, string $content, $block ): string {
		unset( $block );

		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->enqueue_assets( $attributes );
		}

		return $content;
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array $attributes Block attributes.
	 */
	private function enqueue_assets( array $attributes ): void {
		if ( isset( $attributes['style']['color']['background'] ) ) {
			wp_add_inline_style(
				'wc-block-library',
				':root{--woocommerce-coming-soon-color: ' . esc_html( $attributes['style']['color']['background'] ) . '}'
			);
		} elseif ( isset( $attributes['color'] ) ) {
			// Deprecated: To support coming soon templates created before WooCommerce 9.8.0.
			wp_add_inline_style(
				'wc-block-library',
				':root{--woocommerce-coming-soon-color: ' . esc_html( $attributes['color'] ) . '}'
			);
			wp_enqueue_style(
				'woocommerce-coming-soon',
				WC()->plugin_url() . '/assets/css/coming-soon-entire-site-deprecated' . ( is_rtl() ? '-rtl' : '' ) . '.css',
				array(),
				Constants::get_constant( 'WC_VERSION' )
			);
		}
	}

	/**
	 * Enqueue coming soon deprecated styles in site editor to support
	 * coming soon templates created before WooCommerce 9.8.0.
	 */
	public function enqueue_block_assets(): void {
		if ( ! is_admin() ) {
			return;
		}

		$current_screen = get_current_screen();
		if ( $current_screen instanceof WP_Screen && 'site-editor' !== $current_screen->base ) {
			return;
		}

		$post_id = isset( $_REQUEST['postId'] ) ? wc_clean( wp_unslash( $_REQUEST['postId'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'woocommerce/woocommerce//coming-soon' !== $post_id ) {
			return;
		}

		$block_template = get_block_template( $post_id );
		if ( ! $block_template ) {
			return;
		}

		$parsed_blocks = parse_blocks( $block_template->content );
		foreach ( $parsed_blocks as $block ) {
			if ( isset( $block['blockName'] ) && 'woocommerce/coming-soon' === $block['blockName'] ) {
				// Color attribute is deprecated in WooCommerce 9.8.0.
				if ( isset( $block['attrs']['color'] ) && ! empty( $block['attrs']['color'] ) ) {
					wp_enqueue_style(
						'woocommerce-coming-soon',
						WC()->plugin_url() . '/assets/css/coming-soon-entire-site-deprecated' . ( is_rtl() ? '-rtl' : '' ) . '.css',
						array(),
						Constants::get_constant( 'WC_VERSION' )
					);
					break;
				}
			}
		}
	}
}

/**
 * Registers the `woocommerce/coming-soon` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_coming_soon(): void {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/coming-soon' ) ) {
		return;
	}

	$renderer = new WooCommerce_Block_Library_Coming_Soon();
	add_action( 'enqueue_block_assets', array( $renderer, 'enqueue_block_assets' ), 10, 0 );

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => array( $renderer, 'render' ),
		)
	);
}

add_action( 'init', 'register_block_woocommerce_coming_soon' );
