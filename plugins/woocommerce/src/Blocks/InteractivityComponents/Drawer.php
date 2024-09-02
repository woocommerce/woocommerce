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
	 *                  children: string of the children to render.
	 *                  is_initially_open: boolean to indicate if the drawer is initially open.
	 *                  is_open_context: namespaced context that controls if the drawer is open.
	 * @return string|false
	 */
	public static function render( $props ) {
		wp_enqueue_script( 'wc-interactivity-drawer' );
		// wp_enqueue_style( 'wc-interactivity-drawer' );

		$namespace = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-drawer' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$context   = array(
			'isInitiallyOpen'       => $props['is_initially_open'] ?? false,
			'isOpen'                => $props['is_initially_open'] ?? false,
			'isOpenContextProperty' => $props['is_open_context_property'] ?? 'woocommerce/interactivity-drawer',
			'gday'                  => 'hi mate',
			// 'slideClasses'    => 'hi mate',
		);

		ob_start();
		?>

		<div data-wc-interactive="<?php echo esc_attr( $namespace ); ?>" data-wc-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>" >
			<div class="wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden" data-wc-on--keydown="actions.handleEscapeKeyDown" >
				<p data-wc-text="state.slideClasses">Hi</p>
				<div class="wc-block-components-drawer" role="dialog" tab-index="-1">
					<div class="wc-block-components-drawer__content" role="document">
						<!-- TODO: CloseButtonPortal -->
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
