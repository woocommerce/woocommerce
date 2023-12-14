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

		// @todo - default should be single , this is just for testing.
		$select_type = $props['select_type'] ?? 'multiple';

		$selected_items = $props['selected_items'] ?? array();

		$dropdown_context = array(
			'selectedItems' => $selected_items,
			'hoveredItem'   => array(
				'label' => null,
				'value' => null,
			),
			'isOpen'        => false,
			'selectType'    => $select_type,
		);

		wc_initial_state( 'woocommerce/interactivity-dropdown', array( 'selectedItems' => $selected_items ) );

		$action = $props['action'] ?? '';

		// Items should be an array of objects with a label and value property.
		$items = $props['items'] ?? array();

		$namespace = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-dropdown' ) );

		ob_start();
		?>
		<div data-wc-interactive='<?php echo esc_attr( $namespace ); ?>'>
			<div class="wc-interactivity-dropdown" data-wc-context='<?php echo esc_attr( wp_json_encode( $dropdown_context ) ); ?>' >
				<div class="wc-blocks-components-form-token-field-wrapper single-selection" >
					<div class="components-form-token-field" tabindex="-1">
						<div class="components-form-token-field__input-container" 
									data-wc-class--is-active="context.isOpen" 
									tabindex="-1" 
									data-wc-on--click="actions.toggleIsOpen" 
							>
							<?php if ( 'multiple' === $select_type ) { ?>
								<?php foreach ( $selected_items as $selected ) { ?>
									<span class="components-form-token-field__token">
										<span class="components-form-token-field__token-text">
											<?php echo esc_html( $selected['label'] ); ?>
										</span>
									</span>
									<button 
										type="button" 
										data-wc-context="<?php echo esc_attr( wp_json_encode( array( 'currentItem' => $selected ) ) ); ?>"
										data-wc-on--click="actions.unselectDropdownItem" 
										data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>" 
										class="components-button components-form-token-field__remove-token has-icon"
									>
										<svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
											<path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path>
										</svg>
									</button>
								<?php } ?>
							<?php } ?>
							<input id="components-form-token-input-1" type="text" autocomplete="off" data-wc-bind--placeholder="state.placeholderText" class="components-form-token-field__input" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-describedby="components-form-token-suggestions-howto-1" value="">
							<ul hidden data-wc-bind--hidden="!context.isOpen" class="components-form-token-field__suggestions-list" id="components-form-token-suggestions-1" role="listbox">
								<?php
								foreach ( $items as $item ) :
									$context = array(
										'currentItem' => $item,
									);
									?>
									<li
										role="option" 
										data-wc-on--click--select-item="actions.selectDropdownItem" 
										data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>" 
										data-wc-class--is-selected="state.isSelected"
										data-wc-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>' 
										class="components-form-token-field__suggestion" 
										data-wc-bind--aria-selected="state.isSelected"
									>
									<?php // This attribute supports HTML so should be sanitized by caller. ?>
									<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo $item['label']; ?>
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
		</div>
									<?php
									return ob_get_clean();
	}
}
