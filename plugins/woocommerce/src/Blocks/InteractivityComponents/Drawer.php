<?php

namespace Automattic\WooCommerce\Blocks\InteractivityComponents;

/**
 * Drawer class. This is a component for reuse with Interactivity API.
 *
 * @package Automattic\WooCommerce\Blocks\InteractivityComponents
 */
class Drawer {
	/**
	 * Render the drawer.
	 *
	 * @param mixed $props The properties to render the drawer with.
	 *                  children: string of the children to render. Please ensure that you take responsibility to escape attributes and values in the passed HTML.
	 *                  is_initially_open: boolean to indicate if the drawer is initially open.
	 *                  is_open_context: namespaced context that controls if the drawer is open.
	 * @return string|false
	 */
	public static function render( $props ) {
		wp_enqueue_script( 'wc-interactivity-drawer' );

		$namespace = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-drawer' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$context   = array(
			'isInitiallyOpen'       => $props['is_initially_open'] ?? false,
			'isOpenContextProperty' => $props['is_open_context_property'] ?? 'woocommerce/interactivity-drawer',
		);

		ob_start();
		?>

		<div data-wc-body data-wc-init="actions.initialize" data-wc-interactive="<?php echo esc_attr( $namespace ); ?>" data-wc-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>" >
			<div tab-index="0" data-wc-bind--class="state.slideClasses" class="wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden" >
				<div class="wc-block-components-drawer" role="dialog" tab-index="-1">
					<div class="wc-block-components-drawer__content" role="document">
						<?php echo $props['children']; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the close button. Allows you to customize where it's rendered.
	 *
	 * @param string $on_close_action The action to perform when the close button is clicked.
	 *
	 * @return string
	 */
	public static function render_close_button( $on_close_action ) {
		$button_aria_label = __( 'Close', 'woocommerce' );
		ob_start();
		?>
		<div class="wc-block-components-drawer__close-wrapper">
			<button data-wc-on--click="<?php echo esc_attr( $on_close_action ); ?>" class="wc-block-components-button wp-element-button wc-block-components-drawer__close contained" aria-label="<?php echo esc_attr( $button_aria_label ); ?>" type="button">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
