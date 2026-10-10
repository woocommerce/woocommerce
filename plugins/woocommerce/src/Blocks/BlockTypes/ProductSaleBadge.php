<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductSaleBadge class.
 */
class ProductSaleBadge extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-sale-badge';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '3';

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return array( 'query', 'queryId', 'postId' );
	}

	/**
	 * Include and render the block.
	 *
	 * @param array     $attributes Block attributes. Default empty array.
	 * @param string    $content    Block content. Default empty string.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return null;
		}

		$is_on_sale = $product->is_on_sale();

		if ( ! $is_on_sale ) {
			return null;
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

		$classname = StyleAttributesUtils::get_classes_by_attributes( $attributes, array( 'extra_classes' ) );

		$align = isset( $attributes['align'] ) ? $attributes['align'] : '';

		$sale_text     = $attributes['saleText'] ?? '';
		$sale_text     = '' === $sale_text ? __( 'Sale', 'woocommerce' ) : $sale_text;
		$badge_content = $attributes['badgeContent'] ?? 'text';

		if ( in_array( $badge_content, array( 'amount', 'percentage' ), true ) ) {
			$amount                  = 0;
			$percentage              = 0;
			$has_different_discounts = false;

			if ( $product instanceof \WC_Product_Variable ) {
				$prices    = $product->get_variation_prices( 'amount' === $badge_content );
				$discounts = array();
				foreach ( $prices['price'] as $variation_id => $price ) {
					$regular     = (float) $prices['regular_price'][ $variation_id ];
					$discount    = max( 0, $regular - (float) $price );
					$discounts[] = 'amount' === $badge_content ? $discount : ( $regular > 0 ? $discount / $regular : 0 );
					if ( $regular <= 0 || (float) $price >= $regular ) {
						continue;
					}
					$amount     = max( $amount, $regular - (float) $price );
					$percentage = max( $percentage, ( $regular - (float) $price ) / $regular );
				}
				$has_different_discounts = count( array_unique( $discounts ) ) > 1;
			} else {
				$regular = (float) $product->get_regular_price();
				$price   = (float) $product->get_price();
				if ( 'amount' === $badge_content ) {
					$regular = wc_get_price_to_display( $product, array( 'price' => $regular ) );
					$price   = wc_get_price_to_display( $product, array( 'price' => $price ) );
				}
				if ( $regular > 0 && $price < $regular ) {
					$amount     = $regular - $price;
					$percentage = $amount / $regular;
				}
			}

			if ( $amount > 0 ) {
				if ( 'percentage' === $badge_content && round( $percentage * 100 ) < 1 ) {
					return '';
				}
				$value = 'percentage' === $badge_content
					/* translators: %s: discount percentage. %% is the percent sign. */
					? sprintf( __( '%s%%', 'woocommerce' ), round( $percentage * 100 ) )
					: html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );

				if ( $has_different_discounts ) {
					/* translators: %s: largest discount across variations. */
					$sale_text = sprintf( __( 'Up to %s', 'woocommerce' ), $value );
				} else {
					$sale_text = ( $attributes['prefix'] ?? '' ) . $value . ( $attributes['suffix'] ?? '' );
				}
			}
		}

		/**
		 * Filters the product sale badge text.
		 *
		 * @hook woocommerce_sale_badge_text
		 * @since 10.0.0
		 *
		 * @param string $sale_text The sale badge text.
		 * @param \WC_Product $product The product object.
		 * @return string The filtered sale badge text.
		 */
		$sale_text = apply_filters( 'woocommerce_sale_badge_text', $sale_text, $product );

		/* translators: %s: sale badge text. */
		$screen_reader_text     = sprintf( __( 'Product on sale: %s', 'woocommerce' ), $sale_text );
		$is_interactive         = $product instanceof \WC_Product_Variable
			&& ! isset( $block->context['query']['isProductCollectionBlock'] )
			&& ! isset( $block->context['isDescendantOfGroupedProductSelector'] );
		$interactive_attributes = '';
		if ( $is_interactive ) {
			wp_enqueue_script_module( 'woocommerce/product-elements' );
			wp_interactivity_config(
				'woocommerce/product-elements',
				array(
					/* translators: %s: discount percentage. %% is the percent sign. */
					'saleBadgePercentageTemplate'   => sprintf( __( '%s%%', 'woocommerce' ), '%s' ),
					/* translators: %s: sale badge text. */
					'saleBadgeScreenReaderTemplate' => sprintf( __( 'Product on sale: %s', 'woocommerce' ), '%s' ),
				)
			);
			wp_interactivity_state(
				'woocommerce/product-elements',
				array(
					'saleBadgeText'             => static function () {
						return wp_interactivity_get_context()['saleBadgeText'];
					},
					'saleBadgeScreenReaderText' => static function () {
						return wp_interactivity_get_context()['saleBadgeScreenReaderText'];
					},
					'isSaleBadgeHidden'         => false,
				)
			);
			$interactive_attributes = ' data-wp-interactive="woocommerce/product-elements" data-wp-bind--hidden="state.isSaleBadgeHidden" aria-live="polite" aria-atomic="true" ' . wp_interactivity_data_wp_context(
				array(
					'saleBadgeText'             => $sale_text,
					'saleBadgeScreenReaderText' => $screen_reader_text,
					'badgeContent'              => $attributes['badgeContent'] ?? 'text',
					'prefix'                    => $attributes['prefix'] ?? '',
					'suffix'                    => $attributes['suffix'] ?? '',
				)
			);
		}

		$output  = '<div class="wp-block-woocommerce-product-sale-badge ' . esc_attr( $classname ) . '"' . $interactive_attributes . '>';
		$output .= sprintf( '<div class="wc-block-components-product-sale-badge %1$s wc-block-components-product-sale-badge--align-%2$s" style="%3$s">', esc_attr( $classes_and_styles['classes'] ), esc_attr( $align ), esc_attr( $classes_and_styles['styles'] ) );
		$output .= '<span' . ( $is_interactive ? ' data-wp-text="state.saleBadgeText"' : '' ) . ' class="wc-block-components-product-sale-badge__text" aria-hidden="true">' . esc_html( $sale_text ) . '</span>';

		$output .= '<span' . ( $is_interactive ? ' data-wp-text="state.saleBadgeScreenReaderText"' : '' ) . ' class="screen-reader-text">' . esc_html( $screen_reader_text ) . '</span>';
		$output .= '</div></div>';

		return $output;
	}
}
