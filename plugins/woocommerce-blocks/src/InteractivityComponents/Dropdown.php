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

		$selected_item = $props['selected_item'] ?? array(
			'label' => null,
			'value' => null,
		);

		$dropdown_context = array(
			'woocommerceDropdown' => array(
				'selectedItem' => $selected_item,
				'hoveredItem'  => array(
					'label' => null,
					'value' => null,
				),
				'isOpen'       => false,
			),
		);

		$action = $props['action'] ?? '';

		// Items should be an array of objects with a label and value property.
		$items = $props['items'] ?? [];

		ob_start();
		?>
	<div class="wc-block-stock-filter style-dropdown" data-wc-context='<?php echo wp_json_encode( $dropdown_context ); ?>' >
		<div class="wc-blocks-components-form-token-field-wrapper single-selection" >
			<div class="components-form-token-field" tabindex="-1">
				<div class="components-form-token-field__input-container" 
							data-wc-class--is-active="context.woocommerceDropdown.isOpen" 
							tabindex="-1" 
							data-wc-on--click="actions.woocommerceDropdown.toggleIsOpen" 
					>
					<input id="components-form-token-input-1" type="text" autocomplete="off" data-wc-bind--placeholder="selectors.woocommerceDropdown.placeholderText" class="components-form-token-field__input" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-describedby="components-form-token-suggestions-howto-1" value="">
					<ul hidden data-wc-bind--hidden="!context.woocommerceDropdown.isOpen" class="components-form-token-field__suggestions-list" id="components-form-token-suggestions-1" role="listbox">
						<?php
						foreach ( $items as $item ) :
							$context = array(
								'woocommerceDropdown' => array( 'currentItem' => $item ),
								JSON_NUMERIC_CHECK,
							);
							?>
							<li
								role="option" 
								data-wc-on--click--select-item="actions.woocommerceDropdown.selectDropdownItem" 
								data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>" 
								data-wc-class--is-selected="selectors.woocommerceDropdown.isSelected" 
								data-wc-on--mouseover="actions.woocommerceDropdown.addHoverClass" 
								data-wc-on--mouseout="actions.woocommerceDropdown.removeHoverClass" 
								data-wc-context='<?php echo wp_json_encode( $context ); ?>' 
								class="components-form-token-field__suggestion" 
								data-wc-bind--aria-selected="selectors.woocommerceDropdown.isSelected"
							>
							<?php echo esc_html( $item['label'] ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
								</div>
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="30" height="30" >
			<path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z" ></path>
		</svg>      
	</div>
		<?php
		return ob_get_clean();
	}
}
