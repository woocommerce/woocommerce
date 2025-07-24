<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * NavigationArrows class.
 */
class NavigationArrows extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'navigation-arrows';

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'align' ) );
		$vertical_alignment = StyleAttributesUtils::get_align_class_and_style( $attributes );

		ob_start();
		?>
		<div
			class="wc-block-navigation-arrows <?php echo esc_attr( $vertical_alignment['class'] ); ?>"
			data-wp-interactive="woocommerce/navigation-arrows"
		>
			<button
				class="wc-block-navigation-arrows__button <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
				style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
				data-wp-on--click="actions.previous.onClick"
				data-wp-on--keydown="actions.previous.onKeyDown"
				data-wp-bind--aria-disabled="context.previous.isDisabled"
				aria-label="context.previous.label"
			>
				<svg
					class="wc-block-navigation-arrows__icon wc-block-navigation-arrows__icon--left"
					xmlns="http://www.w3.org/2000/svg"
					width="8"
					height="12"
					fill="none"
				>
					<path
						fill="currentColor"
						fillRule="evenodd"
						d="M6.445 12.005.986 6 6.445-.005l1.11 1.01L3.014 6l4.54 4.995-1.109 1.01Z"
						clipRule="evenodd"
					/>
				</svg>
			</button>
			<button
				class="wc-block-navigation-arrows__button <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
				style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
				data-wp-on--click="actions.next.onClick"
				data-wp-on--keydown="actions.next.onKeyDown"
				data-wp-bind--aria-disabled="context.next.isDisabled"
				aria-label="context.next.label"
			>
				<svg
					class="wc-block-navigation-arrows__icon wc-block-navigation-arrows__icon--right"
					xmlns="http://www.w3.org/2000/svg"
					width="8"
					height="12"
					fill="none"
				>
					<path
						fill="currentColor"
						fillRule="evenodd"
						d="M1.555-.004 7.014 6l-5.459 6.005-1.11-1.01L4.986 6 .446 1.005l1.109-1.01Z"
						clipRule="evenodd"
					/>
				</svg>
			</button>
		</div>
		<?php
		$template = ob_get_clean();

		return $template;
	}
}
