<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * CreateAccount class.
 */
class CreateAccount extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-create-account';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-order-confirmation-create-account-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( 'order-confirmation-create-account-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * This renders the content of the block within the wrapper.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
	 * @return string
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		if ( ! $permission ) {
			return '';
		}

		$processor = new \WP_HTML_Tag_Processor( $content );

		if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-order-confirmation-create-account' ) ) ) {
			return $content;
		}

		$processor->set_attribute( 'class', '' );
		$processor->add_class( 'woocommerce-order-confirmation-create-account-content' );

		return $processor->get_updated_html() . '<div class="woocommerce-order-confirmation-create-account-form"></div>';
	}
}
