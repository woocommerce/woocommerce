<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Mini-Cart Contents class.
 *
 * @internal
 */
class MiniCartContents extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-contents';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 *
	 * @return array|string;
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
	 *
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		// The frontend script is a dependency of the Mini-Cart block so it's
		// already lazy-loaded.
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), [ 'wc-blocks-packages-style' ] );
	}

	/**
	 * Render experimental iAPI powered Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_mini_cart_contents( $attributes, $content, $block ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wp-interactive' => 'woocommerce/mini-cart-contents',
			)
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-components-drawer__close-wrapper">
				<button data-wp-on--click="woocommerce/mini-cart::actions.closeDrawer" class="wc-block-components-button wp-element-button wc-block-components-drawer__close contained" aria-label="Close" type="button">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
						<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
					</svg>
				</button>
			</div>
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the markup for the Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( is_admin() || WC()->is_rest_api_request() ) {
			// In the editor we will display the placeholder, so no need to
			// print the markup.
			return '';
		}

		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_iapi_mini_cart_contents( $attributes, $content, $block );
		}

		return $content;
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		parent::enqueue_assets( $attributes, $content, $block );
		$text_color = StyleAttributesUtils::get_text_color_class_and_style( $attributes );
		$bg_color   = StyleAttributesUtils::get_background_color_class_and_style( $attributes );

		$styles = array(
			array(
				'selector'   => array(
					'.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-checkout',
					'.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-checkout:hover',
					'.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-checkout:focus',
					'.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-cart.wc-block-components-button:hover',
					'.wc-block-mini-cart__footer .wc-block-mini-cart__footer-actions .wc-block-mini-cart__footer-cart.wc-block-components-button:focus',
					'.wc-block-mini-cart__shopping-button a:hover',
					'.wc-block-mini-cart__shopping-button a:focus',
				),
				'properties' => array(
					array(
						'property' => 'color',
						'value'    => $bg_color ? $bg_color['value'] : false,
					),
					array(
						'property' => 'border-color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
					array(
						'property' => 'background-color',
						'value'    => $text_color ? $text_color['value'] : false,
					),
				),
			),
		);

		$parsed_style = sprintf(
			':root { --drawer-width: %s; --neg-drawer-width: calc(var(--drawer-width) * -1); }',
			esc_html( $attributes['width'] )
		);

		foreach ( $styles as $style ) {
			$selector = is_array( $style['selector'] ) ? implode( ',', $style['selector'] ) : $style['selector'];

			$properties = array_filter(
				$style['properties'],
				function ( $property ) {
					return $property['value'];
				}
			);

			if ( ! empty( $properties ) ) {
				$parsed_style .= $selector . '{';
				foreach ( $properties as $property ) {
					$parsed_style .= sprintf( '%1$s:%2$s;', $property['property'], $property['value'] );
				}
				$parsed_style .= '}';
			}
		}

		wp_add_inline_style(
			'wc-blocks-style',
			$parsed_style
		);
	}

	/**
	 * Get list of Mini-Cart Contents block & its inner-block types.
	 *
	 * @return array;
	 */
	public static function get_mini_cart_block_types() {
		$block_types = [];

		$block_types[] = 'MiniCartContents';
		$block_types[] = 'EmptyMiniCartContentsBlock';
		$block_types[] = 'FilledMiniCartContentsBlock';
		$block_types[] = 'MiniCartFooterBlock';
		$block_types[] = 'MiniCartItemsBlock';
		$block_types[] = 'MiniCartProductsTableBlock';
		$block_types[] = 'MiniCartShoppingButtonBlock';
		$block_types[] = 'MiniCartCartButtonBlock';
		$block_types[] = 'MiniCartCheckoutButtonBlock';
		$block_types[] = 'MiniCartTitleBlock';
		$block_types[] = 'MiniCartTitleItemsCounterBlock';
		$block_types[] = 'MiniCartTitleLabelBlock';

		return $block_types;
	}
}
