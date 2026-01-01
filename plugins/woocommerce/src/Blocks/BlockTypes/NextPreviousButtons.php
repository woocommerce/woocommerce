<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * NextPreviousButtons class.
 */
class NextPreviousButtons extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name. Block has been initially created for Product Gallery Viewer block
	 * hence the slug is related to this block. But it can be used for other blocks as well.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-large-image-next-previous';

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$iapi_provider = $block->context['iapi/provider'] ?? null;

		if ( empty( $iapi_provider ) ) {
			return '';
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'align' ) );
		$vertical_alignment = StyleAttributesUtils::get_align_class_and_style( $attributes );

		$left_arrow_path  = 'M6.445 12.005.986 6 6.445-.005l1.11 1.01L3.014 6l4.54 4.995-1.109 1.01Z';
		$right_arrow_path = 'M1.555-.004 7.014 6l-5.459 6.005-1.11-1.01L4.986 6 .446 1.005l1.109-1.01Z';

		ob_start();
		?>
	<div
			class="wc-block-next-previous-buttons <?php echo esc_attr( $vertical_alignment['class'] ); ?>"
			data-wp-interactive="<?php echo esc_attr( $iapi_provider ); ?>"
			data-wp-bind--hidden="context.hideNextPreviousButtons"
		>
			<button
				class="wc-block-next-previous-buttons__button <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
				style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
				data-wp-on--click="actions.onClickPrevious"
				data-wp-on--keydown="actions.onKeyDownPrevious"
				data-wp-bind--aria-disabled="context.isDisabledPrevious"
				data-wp-bind--aria-label="context.ariaLabelPrevious"
			>
				<svg
					class="wc-block-next-previous-buttons__icon wc-block-next-previous-buttons__icon--left"
					xmlns="http://www.w3.org/2000/svg"
					width="8"
					height="12"
					fill="none"
				>
					<path
						fill="currentColor"
						fillRule="evenodd"
						d="<?php echo is_rtl() ? esc_attr( $right_arrow_path ) : esc_attr( $left_arrow_path ); ?>"
						clipRule="evenodd"
					/>
				</svg>
			</button>
			<button
				class="wc-block-next-previous-buttons__button <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
				style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
				data-wp-on--click="actions.onClickNext"
				data-wp-on--keydown="actions.onKeyDownNext"
				data-wp-bind--aria-disabled="context.isDisabledNext"
				data-wp-bind--aria-label="context.ariaLabelNext"
			>
				<svg
					class="wc-block-next-previous-buttons__icon wc-block-next-previous-buttons__icon--right"
					xmlns="http://www.w3.org/2000/svg"
					width="8"
					height="12"
					fill="none"
				>
					<path
						fill="currentColor"
						fillRule="evenodd"
						d="<?php echo is_rtl() ? esc_attr( $left_arrow_path ) : esc_attr( $right_arrow_path ); ?>"
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
