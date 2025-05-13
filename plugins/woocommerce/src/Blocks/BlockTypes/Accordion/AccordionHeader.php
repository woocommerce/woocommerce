<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\Accordion;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Utils\BlockUtils;
/**
 * AccordionHeader class.
 */
class AccordionHeader extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'accordion-header';

	public static function create_block( $attrs ) {
		$block_type_name = 'woocommerce/accordion-header';

		$attrs = BlockUtils::filter_block_attributes( $block_type_name, $attrs );

		$block = array(
			'blockName'   => $block_type_name,
			'attrs'       => $attrs,
			'innerBlocks' => array(),
		);

		// TODO: Add other icons.
		$icons = array(
			'plus' => function ( $args = array() ) {
				$defaults = array(
					'width'  => 24,
					'height' => 24,
				);

				$args = wp_parse_args( $args, $defaults );

				return <<<END
				<svg
					width="{$args['width']}"
					height="{$args['height']}"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path
						d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z"
						fill="currentColor"
					/>
				</svg>
				END;
			}
		);

		$heading_classes = array( 'accordion-item__heading' );
		// TODO: Add conditional classes based on attributes.

		// Create the icon HTML
		$icon_classes = array( 'accordion-item__toggle-icon' );
		if ( ! empty( $attrs['icon'] ) ) {
			$icon = $icons[$attrs['icon']];
			if ( is_callable( $icon ) ) {
				$icon = call_user_func( $icon, array( 'width' => '1.2em', 'height' => '1.2em' ) );
			} else {
				$icon = '';
			}
			$icon_classes[] = "has-icon-{$attrs['icon']}";
		}

		$icon_class_string = implode( ' ', $icon_classes );

		// Define tag name based on level
		$tag_name = "h{$attrs['level']}";

		$block_wrapper_attributes = BlockUtils::get_block_wrapper_attributes(
			$block,
			array( 'class' => implode( ' ', $heading_classes ) )
		);

		$block['innerContent'] = array(
			"<{$tag_name} {$block_wrapper_attributes}>",
			"<button class=\"accordion-item__toggle\">",
			"<span>{$attrs['title']}</span>",
			"<span class=\"{$icon_class_string}\" style=\"width:1.2em;height:1.2em;\">",
			$icon,
			"</span>",
			"</button>",
			"</{$tag_name}>"
		);
		return $block;
	}
}
