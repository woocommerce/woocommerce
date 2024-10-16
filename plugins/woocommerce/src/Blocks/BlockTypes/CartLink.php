<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * CartLink class.
 */
class CartLink extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'cart-link';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$link_text          = array_key_exists( 'content', $attributes ) ? esc_html( $attributes['content'] ) : esc_html__( 'Cart', 'woocommerce' );

		// Default "Cart" icon.
		$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><circle cx="12.667" cy="24.667" r="2"/><circle cx="23.333" cy="24.667" r="2"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.285 10.036a1 1 0 0 1 .776-.37h15.272a1 1 0 0 1 .99 1.142l-1.333 9.333A1 1 0 0 1 24 21H12a1 1 0 0 1-.98-.797L9.083 10.87a1 1 0 0 1 .203-.834m2.005 1.63L12.814 19h10.319l1.047-7.333z"/><path fill-rule="evenodd" clip-rule="evenodd" d="M5.667 6.667a1 1 0 0 1 1-1h2.666a1 1 0 0 1 .984.82l.727 4a1 1 0 1 1-1.967.359l-.578-3.18H6.667a1 1 0 0 1-1-1"/></svg>';

		if ( isset( $attributes['cartIcon'] ) ) {
			if ( 'bag' === $attributes['cartIcon'] ) {
				$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.444 14.222a.89.89 0 0 1 .89.89 2.667 2.667 0 0 0 5.333 0 .889.889 0 1 1 1.777 0 4.444 4.444 0 1 1-8.888 0c0-.492.398-.89.888-.89M11.24 6.683a1 1 0 0 1 .76-.35h8a1 1 0 0 1 .76.35l4 4.666A1 1 0 0 1 24 13H8a1 1 0 0 1-.76-1.65zm1.22 1.65L10.174 11h11.652L19.54 8.333z" fill="currentColor"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7 12a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1zm2 1v11.333h14V13z" fill="currentColor"/></svg>';
			} elseif ( 'bag-alt' === $attributes['cartIcon'] ) {
				$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.556 12.333a.89.89 0 0 1-.89-.889c0-.707-.28-3.385-.78-3.885a2.667 2.667 0 0 0-3.772 0c-.5.5-.78 3.178-.78 3.885a.889.889 0 1 1-1.778 0c0-1.178.468-4.309 1.301-5.142a4.445 4.445 0 0 1 6.286 0c.833.833 1.302 3.964 1.302 5.142a.89.89 0 0 1-.89.89" fill="currentColor"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 12a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1zm2 1v11.333h13V13z" fill="currentColor"/></svg>';
			}
		}

		return sprintf(
			'<a %1$s>%2$s<span class="wc-block-cart-link__text">%3$s</span></a>',
			get_block_wrapper_attributes(
				array(
					'class' => 'wc-block-cart-link ' . esc_attr( $classes_and_styles['classes'] ),
					'style' => $classes_and_styles['styles'],
					'href'  => esc_url( wc_get_cart_url() ),
				)
			),
			$icon,
			$link_text
		);
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
