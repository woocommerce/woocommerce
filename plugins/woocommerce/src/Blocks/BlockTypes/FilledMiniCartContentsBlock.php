<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;

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
		// Manually enqueue the store-notices store.
		wp_enqueue_script_module( '@woocommerce/stores/store-notices' );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		$notices = BlocksSharedState::get_cart_error_notices( $consent );

		$context = wp_json_encode(
			array(
				'notices' => $notices,
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

		// Error icon SVG path - matches ICON_PATHS.error in store-notices.ts.
		$error_icon_path    = 'M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z';
		$dismiss_aria_label = __( 'Dismiss this notice', 'woocommerce' );

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div
				class="wc-block-components-notices"
				data-wp-interactive="woocommerce/store-notices"
				data-wp-context="<?php echo esc_attr( $context ); ?>"
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
							aria-label="<?php echo esc_attr( $dismiss_aria_label ); ?>"
							data-wp-on--click="actions.removeNotice"
						>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z" />
							</svg>
						</button>
					</div>
			</template>
			<?php foreach ( $notices as $notice ) : ?>
				<div
					class="wc-block-components-notice-banner is-error is-dismissible"
					role="alert"
					data-wp-each-child
				>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
						<path d="<?php echo esc_attr( $error_icon_path ); ?>"></path>
					</svg>
					<div class="wc-block-components-notice-banner__content">
						<span>
							<?php echo wp_kses_post( $notice['notice'] ); ?>
						</span>
					</div>
					<button
						class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained"
						aria-label="<?php echo esc_attr( $dismiss_aria_label ); ?>"
						data-wp-on--click="actions.removeNotice"
					>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
							<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z" />
						</svg>
					</button>
				</div>
			<?php endforeach; ?>
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
