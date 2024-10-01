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

		$select_type    = $props['select_type'] ?? 'single';
		$selected_items = $props['selected_items'] ?? array();

		// Items should be an array of objects with a label and value property.
		$items = $props['items'] ?? array();

		$default_placeholder = 'single' === $select_type ? __( 'Select an option', 'woocommerce' ) : __( 'Select options', 'woocommerce' );
		$placeholder         = $props['placeholder'] ?? $default_placeholder;

		$dropdown_context = array(
			'selectedItems'      => $selected_items,
			'isOpen'             => false,
			'selectType'         => $select_type,
			'defaultPlaceholder' => $placeholder,
		);

		$action    = $props['action'] ?? '';
		$namespace = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-dropdown' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );

		ob_start();
		?>
		<div data-wc-interactive='<?php echo esc_attr( $namespace ); ?>'>
			<div class="wc-interactivity-dropdown" data-wc-on--click="actions.toggleIsOpen" data-wc-context='<?php echo wp_json_encode( $dropdown_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>' >
				<div class="wc-interactivity-dropdown__dropdown" tabindex="-1" >
					<div class="wc-interactivity-dropdown__dropdown-selection" id="options-dropdown" tabindex="0" aria-haspopup="listbox">
						<span class="wc-interactivity-dropdown__placeholder" data-wc-text="state.placeholderText">
							<?php echo empty( $selected_items ) ? esc_html( $placeholder ) : ''; ?>
						</span>
						<?php if ( 'multiple' === $select_type ) { ?>
							<div class="selected-options">
								<template
										data-wc-each="context.selectedItems"
										data-wc-each-key="context.item.value"
									>
										<div class="wc-interactivity-dropdown__selected-badge">
											<span class="wc-interactivity-dropdown__badge-text" data-wc-text="context.item.label"></span>
											<svg
												data-wc-on--click="actions.unselectDropdownItem"
												data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>"
												class="wc-interactivity-dropdown__badge-remove"
												width="24"
												height="24"
												xmlns="http://www.w3.org/2000/svg"
												viewBox="0 0 24 24"
												aria-hidden="true"
											>
												<path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path>
											</svg>
										</div>
								</template>

								<?php foreach ( $selected_items as $selected ) { ?>
									<div
										class="wc-interactivity-dropdown__selected-badge"
										data-wc-key="<?php echo esc_attr( $selected['value'] ); ?>"
										data-wc-each-child
									>
											<span class="wc-interactivity-dropdown__badge-text"><?php echo esc_html( $selected['label'] ); ?></span>
											<svg
												data-wc-on--click="actions.unselectDropdownItem"
												data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>"
												class="wc-interactivity-dropdown__badge-remove"
												width="24"
												height="24"
												xmlns="http://www.w3.org/2000/svg"
												viewBox="0 0 24 24"
												aria-hidden="true"
											>
												<path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path>
											</svg>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
						<span class="wc-interactivity-dropdown__svg-container">
							<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="30" height="30" >
								<path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z" ></path>
							</svg>
						</span>
					</div>
					<div
						class="wc-interactivity-dropdown__dropdown-list"
						aria-labelledby="options-dropdown"
						role="listbox"
						data-wc-bind--hidden="!context.isOpen"
						<?php echo esc_attr( $dropdown_context['isOpen'] ? '' : 'hidden' ); ?>
					>
						<?php
						foreach ( $items as $item ) :
							$context = array( 'item' => $item );
							?>
							<div
								class="wc-interactivity-dropdown__dropdown-option"
								role="option"
								tabindex="0"
								data-wc-on--click--select-item="actions.selectDropdownItem"
								data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>"
								data-wc-class--is-selected="state.isSelected"
								class="components-form-token-field__suggestion"
								data-wc-bind--aria-selected="state.isSelected"
								data-wc-context='<?php echo wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>'
							>
								<?php echo $item['label']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
