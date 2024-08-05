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
	 * @return string|false
	 */
	public static function render( $props ) {
		wp_enqueue_script( 'wc-interactivity-checkbox-list' );
		wp_enqueue_style( 'wc-interactivity-checkbox-list' );

		$items                 = $props['items'] ?? array();
		$checkbox_list_context = array( 'items' => $items );
		$on_change             = $props['on_change'] ?? '';

		$namespace = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-checkbox-list' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );

		ob_start();
		?>
		<div data-wc-interactive='<?php echo esc_attr( $namespace ); ?>'>
			<div data-wc-context='<?php echo wp_json_encode( $checkbox_list_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>' >
			<div class="wc-block-stock-filter style-list">
					<ul class="wc-block-components-checkbox-list">
						<?php foreach ( $items as $item ) { ?>
							<?php
							$item['id'] = $item['id'] ?? uniqid( 'checkbox-' );
							// translators: %s: checkbox label.
							$i18n_label = sprintf( __( 'Checkbox: %s', 'woocommerce' ), $item['aria_label'] ?? '' );
							?>
							<li data-wc-key="<?php echo esc_attr( $item['id'] ); ?>">
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
					</ul>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
