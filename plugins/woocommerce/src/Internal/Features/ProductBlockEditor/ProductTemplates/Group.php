<?php
/**
 * WooCommerce Product Group Block class.
 */

namespace Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockContainerTrait;

/**
 * Class for Group block.
 */
class Group extends ProductBlock implements GroupInterface {
	use BlockContainerTrait;
	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
	/**
	 * Group Block constructor.
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
			throw new \InvalidArgumentException( 'Unexpected key "blockName", this defaults to "woocommerce/product-tab".' );
		}
		if ( $config['id'] && ( empty( $config['attributes'] ) || empty( $config['attributes']['id'] ) ) ) {
			$config['attributes']       = empty( $config['attributes'] ) ? array() : $config['attributes'];
			$config['attributes']['id'] = $config['id'];
		}
		parent::__construct( array_merge( array( 'blockName' => 'woocommerce/product-tab' ), $config ), $root_template, $parent );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Add a section block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_section( array $block_config ): SectionInterface {
		$block = new Section( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}
}
