<?php
/**
 * Server-side rendering of the `woocommerce/classic-shortcode` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Server renderer for the `woocommerce/classic-shortcode` block.
 *
 * @since 11.0.0
 */
final class WooCommerce_Block_Library_Classic_Shortcode {
	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	public function render( array $attributes, string $content, $block ): string {
		unset( $content, $block );

		if ( ! isset( $attributes['shortcode'] ) ) {
			return '';
		}

		if ( class_exists( 'WC_Frontend_Scripts' ) ) {
			WC_Frontend_Scripts::load_scripts();
		}

		if ( 'cart' === $attributes['shortcode'] ) {
			return $this->render_cart( $attributes );
		}

		if ( 'checkout' === $attributes['shortcode'] ) {
			return $this->render_checkout( $attributes );
		}

		return "You're using the ClassicShortcode block";
	}

	/**
	 * Get the list of classes to apply to this block.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return string Space-separated list of classes.
	 */
	private function get_container_classes( array $attributes = array() ): string {
		$classes = array( 'woocommerce', 'wp-block-group' );

		if ( isset( $attributes['align'] ) ) {
			$classes[] = "align{$attributes['align']}";
		}

		return implode( ' ', $classes );
	}

	/**
	 * Render the cart shortcode.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block type output.
	 */
	private function render_cart( array $attributes ): string {
		ob_start();

		echo '<div class="' . esc_attr( $this->get_container_classes( $attributes ) ) . '">';
		WC_Shortcode_Cart::output( array() );
		echo '</div>';

		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Render the checkout shortcode.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block type output.
	 */
	private function render_checkout( array $attributes ): string {
		ob_start();

		echo '<div class="' . esc_attr( $this->get_container_classes( $attributes ) ) . '">';
		WC_Shortcode_Checkout::output( array() );
		echo '</div>';

		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}
}

/**
 * Registers the `woocommerce/classic-shortcode` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_classic_shortcode(): void {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/classic-shortcode' ) ) {
		return;
	}

	$renderer = new WooCommerce_Block_Library_Classic_Shortcode();
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => array( $renderer, 'render' ),
		)
	);
}

add_action( 'init', 'register_block_woocommerce_classic_shortcode' );
