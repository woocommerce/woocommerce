<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * FilledMiniCartContentsBlock class.
 */
class FilledMiniCartContentsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'filled-mini-cart-contents-block';

	/**
	 * Render the markup for the Filled Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_filled_mini_cart_contents( $attributes, $content, $block );
		}

		return $content;
	}

	/**
	 * Render the experimental interactivity API powered Filled Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_filled_mini_cart_contents( $attributes, $content, $block ) {
		$context = wp_json_encode(
			array(
				'notices' => array(),
			),
			JSON_NUMERIC_CHECK
				| JSON_HEX_TAG
				| JSON_HEX_APOS
				| JSON_HEX_QUOT
				| JSON_HEX_AMP
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wp-interactive'  => 'woocommerce/mini-cart',
				'data-wp-context'      => 'woocommerce/store-notices::' . $context,
				'data-wp-bind--hidden' => 'state.cartIsEmpty',
			)
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div
				class="wc-block-components-notices"
				data-wp-interactive="woocommerce/store-notices"
			>
				<template 
					data-wp-each--notice="context.notices"
					data-wp-each-key="context.notice.id"
				>
					<div
						class="wc-block-components-notice-banner"
						data-wp-class--is-error="state.isError"
						data-wp-class--is-success ="state.isSuccess"
						data-wp-class--is-info="state.isInfo"
						data-wp-class--is-dismissible="context.notice.dismissible"
						data-wp-bind--role="state.role"
					>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
							<path data-wp-bind--d="state.iconPath"></path>
						</svg>
						<div class="wc-block-components-notice-banner__content">
							<span data-wp-init="callbacks.renderNoticeContent"></span>
						</div>
						<button
							data-wp-bind--hidden="!context.notice.dismissible"
							class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained"
							aria-label="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce' ); ?>"
							data-wp-on--click="actions.removeNotice"
						>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z" />
							</svg>
						</button>
					</div>
				</template>
			</div>
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
