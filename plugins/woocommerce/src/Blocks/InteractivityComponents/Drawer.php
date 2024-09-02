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
	 * @return string|false
	 */
	public static function render( $props ) {
		// wp_enqueue_script( 'wc-interactivity-checkbox-list' );
		// wp_enqueue_style( 'wc-interactivity-checkbox-list' );

		$namespace = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-drawer' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$context   = array(
			'isInitiallyOpen' => $props['is_initially_open'] ?? false,
			'isOpen'          => $props['is_initially_open'] ?? false,
		);

		ob_start();
		?>

		<div data-wc-context="<?php echo wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>" data-wc-interactive="<?php echo esc_attr( $namespace ); ?>" data-wc-on--keydown="actions.handleEscapeKeyDown" class="wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden">
			<div data-wc-body class="wc-block-components-drawer" role="dialog" tab-index="-1">
				<div className="wc-block-components-drawer__content" role="document">
					<!-- TODO: CloseButtonPortal -->
					<?php echo wp_kses_post( $props['children'] ); ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
