<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

/**
 * DownloadsWrapper class.
 */
class DownloadsWrapper extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-downloads-wrapper';

	/**
	 * See if the store has a downloadable product. This controls if we bother to show a preview in the editor.
	 *
	 * @return boolean
	 */
	protected function store_has_downloadable_products() {
		$has_downloadable_product = get_transient( 'wc_blocks_has_downloadable_product', false );

		if ( false === $has_downloadable_product ) {
			$product_ids              = get_posts(
				array(
					'post_type'   => 'product',
					'numberposts' => 1,
					'post_status' => 'publish',
					'fields'      => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'  => array(
						array(
							'key'     => '_downloadable',
							'value'   => 'yes',
							'compare' => '=',
						),
					),
				)
			);
			$has_downloadable_product = ! empty( $product_ids );
			set_transient( 'wc_blocks_has_downloadable_product', $has_downloadable_product ? '1' : '0', MONTH_IN_SECONDS );
		}

		return (bool) $has_downloadable_product;
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

		$this->asset_data_registry->add( 'storeHasDownloadableProducts', $this->store_has_downloadable_products() );
	}

	/**
	 * This renders the content of the downloads wrapper.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission If the current user can view the order details or not.
	 * @param array        $attributes Block attributes.
	 * @param string       $content Original block content.
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		$show_downloads = $order && $order->has_downloadable_item() && $order->is_download_permitted();

		if ( ! $show_downloads || ! $permission ) {
			return '';
		}

		return $content;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
