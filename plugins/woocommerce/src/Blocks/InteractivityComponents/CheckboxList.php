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
		$namespace             = wp_json_encode( array( 'namespace' => 'woocommerce/interactivity-checkbox-list' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );

		$checked_items               = array_filter(
			$items,
			function ( $item ) {
				return $item['checked'];
			}
		);
		$show_initially              = $props['show_initially'] ?? 15;
		$remaining_initial_unchecked = count( $checked_items ) > $show_initially ? count( $checked_items ) : $show_initially - count( $checked_items );
		$count                       = 0;
		ob_start();
		?>
		<div
			class="wc-block-interactivity-components-checkbox-list"
			data-wc-interactive='<?php echo esc_attr( $namespace ); ?>'
			data-wc-context='<?php echo wp_json_encode( $checkbox_list_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>'
		>
			<ul class="wc-block-interactivity-components-checkbox-list__list">
			<?php foreach ( $items as $item ) { ?>
					<?php
					$item['id'] = $item['id'] ?? uniqid( 'checkbox-' );
					// translators: %s: checkbox label.
					$i18n_label = sprintf( __( 'Checkbox: %s', 'woocommerce' ), $item['aria_label'] ?? '' );
					?>
					<li
						data-wc-key="<?php echo esc_attr( $item['id'] ); ?>"
						<?php
						if ( ! $item['checked'] ) :
							if ( $count >= $remaining_initial_unchecked ) :
								?>
								class="wc-block-interactivity-components-checkbox-list__item hidden"
								data-wc-class--hidden="!context.showAll"
							<?php else : ?>
								<?php ++$count; ?>
							<?php endif; ?>
						<?php endif; ?>
						class="wc-block-interactivity-components-checkbox-list__item"
					>
						<label
							class="wc-block-interactivity-components-checkbox-list__label"
							for="<?php echo esc_attr( $item['id'] ); ?>"
						>
							<span class="wc-block-interactivity-components-checkbox-list__input-wrapper">
								<input
									id="<?php echo esc_attr( $item['id'] ); ?>"
									class="wc-block-interactivity-components-checkbox-list__input"
									type="checkbox"
									aria-invalid="false"
									aria-label="<?php echo esc_attr( $i18n_label ); ?>"
									data-wc-on--change--select-item="actions.selectCheckboxItem"
									data-wc-on--change--parent-action="<?php echo esc_attr( $on_change ); ?>"
									value="<?php echo esc_attr( $item['value'] ); ?>"
									<?php checked( $item['checked'], 1 ); ?>
								>
								<svg class="wc-block-interactivity-components-checkbox-list__mark" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.25 1.19922L3.75 6.69922L1 3.94922" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							<span class="wc-block-interactivity-components-checkbox-list__text">
								<?php echo wp_kses_post( $item['label'] ); ?>
							</span>
						</label>
					</li>
				<?php } ?>
			</ul>
					<?php if ( count( $items ) > $show_initially ) : ?>
				<span
					role="button"
					class="wc-block-interactivity-components-checkbox-list__show-more"
					data-wc-class--hidden="context.showAll"
					data-wc-on--click="actions.showAllItems"
				>
					<small role="presentation"><?php echo esc_html__( 'Show more...', 'woocommerce' ); ?></small>
				</span>
				<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
