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
		$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12.6667" cy="24.6667" r="2" fill="currentColor"/>
					<circle cx="23.3333" cy="24.6667" r="2" fill="currentColor"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M9.28491 10.0356C9.47481 9.80216 9.75971 9.66667 10.0606 9.66667H25.3333C25.6232 9.66667 25.8989 9.79247 26.0888 10.0115C26.2787 10.2305 26.3643 10.5211 26.3233 10.8081L24.99 20.1414C24.9196 20.6341 24.4977 21 24 21H12C11.5261 21 11.1173 20.6674 11.0209 20.2034L9.08153 10.8701C9.02031 10.5755 9.09501 10.269 9.28491 10.0356ZM11.2898 11.6667L12.8136 19H23.1327L24.1803 11.6667H11.2898Z" fill="currentColor"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M5.66669 6.66667C5.66669 6.11438 6.1144 5.66667 6.66669 5.66667H9.33335C9.81664 5.66667 10.2308 6.01229 10.3172 6.48778L11.0445 10.4878C11.1433 11.0312 10.7829 11.5517 10.2395 11.6505C9.69614 11.7493 9.17555 11.3889 9.07676 10.8456L8.49878 7.66667H6.66669C6.1144 7.66667 5.66669 7.21895 5.66669 6.66667Z" fill="currentColor"/>
				</svg>';

		if ( isset( $attributes['cartIcon'] ) ) {
			if ( 'bag' === $attributes['cartIcon'] ) {
				$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M12.4444 14.2222C12.9354 14.2222 13.3333 14.6202 13.3333 15.1111C13.3333 15.8183 13.6143 16.4966 14.1144 16.9967C14.6145 17.4968 15.2927 17.7778 16 17.7778C16.7072 17.7778 17.3855 17.4968 17.8856 16.9967C18.3857 16.4966 18.6667 15.8183 18.6667 15.1111C18.6667 14.6202 19.0646 14.2222 19.5555 14.2222C20.0465 14.2222 20.4444 14.6202 20.4444 15.1111C20.4444 16.2898 19.9762 17.4203 19.1427 18.2538C18.3092 19.0873 17.1787 19.5555 16 19.5555C14.8212 19.5555 13.6908 19.0873 12.8573 18.2538C12.0238 17.4203 11.5555 16.2898 11.5555 15.1111C11.5555 14.6202 11.9535 14.2222 12.4444 14.2222Z" fill="currentColor"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M11.2408 6.68254C11.4307 6.46089 11.7081 6.33333 12 6.33333H20C20.2919 6.33333 20.5693 6.46089 20.7593 6.68254L24.7593 11.3492C25.0134 11.6457 25.0717 12.0631 24.9085 12.4179C24.7453 12.7727 24.3905 13 24 13H8.00001C7.60948 13 7.25469 12.7727 7.0915 12.4179C6.92832 12.0631 6.9866 11.6457 7.24076 11.3492L11.2408 6.68254ZM12.4599 8.33333L10.1742 11H21.8258L19.5401 8.33333H12.4599Z" fill="currentColor"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M7 12C7 11.4477 7.44772 11 8 11H24C24.5523 11 25 11.4477 25 12V25.3333C25 25.8856 24.5523 26.3333 24 26.3333H8C7.44772 26.3333 7 25.8856 7 25.3333V12ZM9 13V24.3333H23V13H9Z" fill="currentColor"/>
						</svg>';
			} elseif ( 'bag-alt' === $attributes['cartIcon'] ) {
				$icon = '<svg class="wc-block-mini-cart__icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M19.5556 12.3333C19.0646 12.3333 18.6667 11.9354 18.6667 11.4444C18.6667 10.7372 18.3857 8.05893 17.8856 7.55883C17.3855 7.05873 16.7073 6.77778 16 6.77778C15.2928 6.77778 14.6145 7.05873 14.1144 7.55883C13.6143 8.05893 13.3333 10.7372 13.3333 11.4444C13.3333 11.9354 12.9354 12.3333 12.4445 12.3333C11.9535 12.3333 11.5556 11.9354 11.5556 11.4444C11.5556 10.2657 12.0238 7.13524 12.8573 6.30175C13.6908 5.46825 14.8213 5 16 5C17.1788 5 18.3092 5.46825 19.1427 6.30175C19.9762 7.13524 20.4445 10.2657 20.4445 11.4444C20.4445 11.9354 20.0465 12.3333 19.5556 12.3333Z" fill="currentColor"/>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 12C7.5 11.4477 7.94772 11 8.5 11H23.5C24.0523 11 24.5 11.4477 24.5 12V25.3333C24.5 25.8856 24.0523 26.3333 23.5 26.3333H8.5C7.94772 26.3333 7.5 25.8856 7.5 25.3333V12ZM9.5 13V24.3333H22.5V13H9.5Z" fill="currentColor" />
						</svg>';
			}
		}

		return sprintf(
			'<a %1$s>%2$s%3$s</a>',
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
