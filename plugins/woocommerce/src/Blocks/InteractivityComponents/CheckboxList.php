<?php

namespace Automattic\WooCommerce\Blocks\InteractivityComponents;

/**
 * CheckboxList class. This is a component for reuse with interactivity API.
 *
 * @package Automattic\WooCommerce\Blocks\InteractivityComponents
 */
class CheckboxList {
	/**
	 * Render the checkbox list.
	 *
	 * @param mixed $props The properties to render the dropdown with.
	 *                  items: array of objects with label and value properties.
	 *                      - id: string of the id to use for the checkbox (optional).
	 *                      - checked: boolean to indicate if the checkbox is checked.
	 *                      - label: string of the label to display (plaintext or HTML).
	 *                      - aria_label: string of the aria label to use for the checkbox. (optional, plaintext only).
	 *                      - value: string of the value to use.
	 *                  on_change: string of the action to perform when the dropdown changes.
	 *                  intitial_items_count: how many items are displayed initially, default to 10.
	 * @return string|false
	 */
	public static function render( $props ) {
		wp_enqueue_script( 'wc-interactivity-checkbox-list' );
		wp_enqueue_style( 'wc-interactivity-checkbox-list' );

		$items              = $props['items'] ?? array();
		$intial_items_count = $props['intial_items_count'] ?? 10;
		$on_change          = $props['on_change'] ?? '';

		$checkbox_list_context = array(
			'items'             => $items,
			'initialItemsCount' => $intial_items_count,
			'showAll'           => false,
		);
		$visible_items         = array_slice( $items, 0, $intial_items_count );
		$namespace             = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-checkbox-list' ) );

		ob_start();
		?>
		<div data-wc-interactive='<?php echo esc_attr( $namespace ); ?>'>
			<div data-wc-context='<?php echo esc_attr( wp_json_encode( $checkbox_list_context ) ); ?>' >
			<div class="wc-block-stock-filter style-list">
					<ul class="wc-block-components-checkbox-list">
						<?php foreach ( $visible_items as $item ) { ?>
							<?php
							$item['id'] = $item['id'] ?? uniqid( 'checkbox-' );
							// translators: %s: checkbox label.
							$i18n_label = sprintf( __( 'Checkbox: %s', 'woocommerce' ), $item['aria_label'] ?? '' );
							?>
							<li
								data-wc-key="<?php echo esc_attr( $item['id'] ); ?>"
								data-wc-each-child
							>
								<div class="wc-block-components-checkbox">
									<label for="<?php echo esc_attr( $item['id'] ); ?>">
										<input
											id="<?php echo esc_attr( $item['id'] ); ?>"
											class="wc-block-components-checkbox__input"
											type="checkbox"
											aria-invalid="false"
											aria-label="<?php echo esc_attr( $i18n_label ); ?>"
											data-wc-on--change--select-item="actions.selectCheckboxItem"
											data-wc-on--change--parent-action="<?php echo esc_attr( $on_change ); ?>"
											value="<?php echo esc_attr( $item['value'] ); ?>"
											<?php checked( $item['checked'], 1 ); ?>
										>
											<svg class="wc-block-components-checkbox__mark" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 20">
												<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"></path>
											</svg>
											<span class="wc-block-components-checkbox__label">
												<?php // The label can be HTML, so we don't want to escape it. ?>
												<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												<?php echo $item['label']; ?>
											</span>
									</label>
								</div>
							</li>
						<?php } ?>
						<template
							data-wc-each="state.visibleItems"
							data-wc-each-key="context.item.id"
						>
							<li>
								<div class="wc-block-components-checkbox">
									<label data-wc-bind--for="context.item.id">
										<input
											data-wc-bind--id="context.item.id"
											class="wc-block-components-checkbox__input"
											type="checkbox"
											aria-invalid="false"
											data-wc-bind--aria-label="context.item.aria_label"
											data-wc-on--change--select-item="actions.selectCheckboxItem"
											data-wc-on--change--parent-action="<?php echo esc_attr( $on_change ); ?>"
											data-wc-bind--value="context.item.value"
											data-wc-bind--aria-label="context.item.checked"
										>
											<svg class="wc-block-components-checkbox__mark" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 20">
												<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"></path>
											</svg>
											<span class="wc-block-components-checkbox__label" data-wc-text="context.item.label"></span>
									</label>
								</div>
							</li>
						</template>
					</ul>
					<button class="show-all" data-wc-on--click="actions.showAllItems" data-wc-bind--hidden="context.showAll"><?php echo esc_html( 'Show All', 'woocommerce' ); ?></button>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
