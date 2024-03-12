<?php
/**
 * WooCommerce Section Block class.
 */

namespace Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SubsectionInterface;

/**
 * Class for Section block.
 */
class Section extends ProductBlock implements SectionInterface {
	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
	/**
	 * Section Block constructor.
	 *
	 * @param array                   $config The block configuration.
	 * @param BlockTemplateInterface  $root_template The block template that this block belongs to.
	 * @param ContainerInterface|null $parent The parent block container.
	 *
	 * @throws \ValueError If the block configuration is invalid.
	 * @throws \ValueError If the parent block container does not belong to the same template as the block.
	 * @throws \InvalidArgumentException If blockName key and value are passed into block configuration.
	 */
	public function __construct( array $config, BlockTemplateInterface &$root_template, ContainerInterface &$parent = null ) {
		if ( ! empty( $config['blockName'] ) ) {
			throw new \InvalidArgumentException( 'Unexpected key "blockName", this defaults to "woocommerce/product-section".' );
		}
		parent::__construct( array_merge( array( 'blockName' => 'woocommerce/product-section' ), $config ), $root_template, $parent );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Add a sub-section block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_subsection( array $block_config ): SubsectionInterface {
		$block = new Subsection( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}

	/**
	 * Add a sub-section block type to this template.
	 *
	 * @deprecated 8.6.0
	 *
	 * @param array $block_config The block data.
	 */
	public function add_section( array $block_config ): SubsectionInterface {
		wc_deprecated_function( 'add_section', '8.6.0', 'add_subsection' );
		return $this->add_subsection( $block_config );
	}
}
