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
		global $wpdb;

		if ( get_option( 'woocommerce_product_lookup_table_is_generating' ) ) {
			// The underlying SQL is slower than querying `wc_product_meta_lookup`, so caching is used for performance.
			$has_downloadable_products = wp_cache_get( 'woocommerce_has_downloadable_products', 'woocommerce' );
			if ( false === $has_downloadable_products ) {
				$has_downloadable_products = (bool) $wpdb->get_var(
					"SELECT posts.ID
						FROM {$wpdb->posts} as posts
						INNER JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
					 WHERE
						    postmeta.meta_key   = '_downloadable'
						AND postmeta.meta_value = 'yes'
						AND posts.post_type     = 'product'
						AND posts.post_status   = 'publish'
						LIMIT 1"
				);
				$has_downloadable_products = $has_downloadable_products ? 'yes' : 'no';
				wp_cache_set( 'woocommerce_has_downloadable_products', $has_downloadable_products, 'woocommerce', HOUR_IN_SECONDS );
			}
			$has_downloadable_products = 'yes' === $has_downloadable_products;
		} else {
			$has_downloadable_products = (bool) $wpdb->get_var(
				"SELECT product_id FROM {$wpdb->wc_product_meta_lookup} WHERE downloadable = 1 LIMIT 1",
			);
		}

		return $has_downloadable_products;
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
