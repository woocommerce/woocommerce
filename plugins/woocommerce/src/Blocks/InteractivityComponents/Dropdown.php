<?php

namespace Automattic\WooCommerce\Blocks\InteractivityComponents;

/**
 * Dropdown class. This is a component for reuse with interactivity API.
 *
 * @package Automattic\WooCommerce\Blocks\InteractivityComponents
 */
class Dropdown {
	/**
	 * Render the dropdown.
	 *
	 * @param mixed $props The properties to render the dropdown with.
	 * @return string|false
	 */
	public static function render( $props ) {
		wp_enqueue_script( 'wc-interactivity-dropdown' );
		wp_enqueue_style( 'wc-interactivity-dropdown' );

		$select_type      = $props['select_type'] ?? 'single';
		$selected_items   = $props['selected_items'] ?? array();
		$text_color       = $props['text_color'] ?? 'inherit';
		$text_color_style = "color: {$text_color};";

		// Items should be an array of objects with a label and value property.
		$items = $props['items'] ?? array();

		$dropdown_context = array(
			'selectedItems' => $selected_items,
			'isOpen'        => false,
			'selectType'    => $select_type,
		);

		$action        = $props['action'] ?? '';
		$namespace     = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-dropdown' ) );
		$wrapper_class = 'multiple' === $select_type ? '' : 'single-selection';
		$input_id      = wp_unique_id( 'wc-interactivity-dropdown-input-' );

		wp_add_inline_style(
			'wc-interactivity-dropdown',
			"#$input_id::placeholder {
					$text_color_style
			}"
		);

		ob_start();
		?>
		<div data-wc-interactive='<?php echo esc_attr( $namespace ); ?>'>
			<div class="new-interactivity-dropdown" data-wc-on--click="actions.toggleIsOpen" data-wc-context='<?php echo esc_attr( wp_json_encode( $dropdown_context ) ); ?>' >
				<div class="dropdown" tabindex="-1" >
					<div class="dropdown-selection" id="options-dropdown" tabindex="0" aria-haspopup="listbox">
						<span class="new-interactivity-dropdown__placeholder">Select an option</span>
						<span class="svg-container">
							<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="30" height="30" >
								<path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z" ></path>
							</svg>
						</span>
					</div>
					<div data-wc-bind--hidden="!context.isOpen" class="dropdown-list" aria-labelledby="options-dropdown" role="listbox">
						<div class="dropdown-option" role="option" tabindex="0">In stock</div>
						<div class="dropdown-option" role="option" tabindex="0">Out of stock</div>
						<div class="dropdown-option" role="option" tabindex="0">On backorder</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
