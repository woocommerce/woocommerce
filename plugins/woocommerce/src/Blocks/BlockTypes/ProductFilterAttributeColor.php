<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CollectionPriceFilter class.
 */
final class ProductFilterAttributeColor extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-attribute-color';

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
		$wrapper_attributes = get_block_wrapper_attributes(
            array(
                'class' => "style-{$attributes['displayStyle']}",
            )
        );
		ob_start();
		?>

		<div <?php echo $wrapper_attributes; ?>">
    		<div class="terms">
        		<?php foreach ( $block->context['filterOptions'] as $option ) : ?>
                    <?php $uid = $option['id'] ?? uniqid(); ?>
                    <div class="term <?php echo empty( $option['checked'] ) ? '' : ' selected' ?>">
                        <input id='<?php echo $uid; ?>' type="checkbox" value="<?php echo $option['value']; ?>" data-wc-on--change="woocommerce/product-filter-attribute::actions.updateProducts" />
                        <label for='<?php echo $uid; ?>'>
                            <span class="color" style="background-color: <?php echo $attributes['termColors'][$option['attrs']['term_id']] ?? '#eee'; ?>;"></span>
                            <span class="name"><?php echo $option['label']; ?></span>
                        </label>
                    </div>
        		<?php endforeach; ?>
    		</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
