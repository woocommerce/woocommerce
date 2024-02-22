<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\InteractivityComponents\CheckboxList;

/**
 * CollectionPriceFilter class.
 */
final class ProductFilterCheckboxList extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-checkbox-list';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
    	return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$wrapper_attributes = get_block_wrapper_attributes();

        $options = array_map( function( $option ) use ( $attributes ) {
            if ( ! empty( $attributes['showCounts'] ) ) {
                $option['label'] = sprintf( '%s (%d)', $option['label'], $option['count'] );
            }
            return $option;
        }, $block->context['filterOptions'] );

		return sprintf(
		    '<div %s>%s</div>',
            $wrapper_attributes,
            CheckboxList::render(
                array(
                    'items'     => $options,
                    'on_change' => "{$block->context['parentBlockName']}::actions.updateProducts",
                )
            )
        );
	}
}
