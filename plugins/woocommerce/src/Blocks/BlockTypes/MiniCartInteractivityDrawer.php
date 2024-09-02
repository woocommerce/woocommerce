<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * MiniCartInteractivityDrawer class.
 *
 * @package Automattic\WooCommerce\Blocks\BlockTypes
 */
class MiniCartInteractivityDrawer {
	/**
	 * Render the MiniCartInteractivityDrawer. Using the Interactivity body directive we render the drawer in the body.
	 *
	 * @return string|false
	 */
	public static function render() {
		ob_start();
		?>
		<div data-wc-on--keydown="actions.handleEscapeKeyDown" class="wc-block-components-drawer__screen-overlay wc-block-components-drawer__screen-overlay--is-hidden">
			<div data-wc-body class="wc-block-components-drawer" role="dialog" tab-index="-1">
				<div className="wc-block-components-drawer__content" role="document">
					<!-- TODO: CloseButtonPortal -->
					<!-- TODO: children -->
				</div>
			</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
