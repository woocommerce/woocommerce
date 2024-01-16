<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

/**
 * AdditionalFieldsWrapper class.
 */
class AdditionalFieldsWrapper extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-additional-fields-wrapper';

	/**
	 * This renders the content of the downloads wrapper.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		if ( ! $permission ) {
			return '';
		}

		// Contact and additional fields are currently grouped in this section.
		$additional_fields = array_merge(
			Package::container()->get( CheckoutFields::class )->get_fields_for_location( 'contact' ),
			Package::container()->get( CheckoutFields::class )->get_fields_for_location( 'additional' )
		);

		return empty( $additional_fields ) ? '' : $content;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'additionalFields', Package::container()->get( CheckoutFields::class )->get_fields_for_location( 'additional' ) );
		$this->asset_data_registry->add( 'additionalContactFields', Package::container()->get( CheckoutFields::class )->get_fields_for_location( 'contact' ) );
	}
}
