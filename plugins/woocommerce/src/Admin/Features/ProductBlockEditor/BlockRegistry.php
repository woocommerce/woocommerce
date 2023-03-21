<?php
/**
 * WooCommerce Product Editor Block Registration
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Product block registration and style registration functionality.
 */
class BlockRegistry {
	/**
	 * The directory where blocks are stored after build.
	 */
	const BLOCKS_DIR = 'product-editor/blocks';

	/**
	 * Array of all available product blocks.
	 */
	const PRODUCT_BLOCKS = [
		'woocommerce/product-name',
		'woocommerce/product-pricing',
		'woocommerce/product-section',
		'woocommerce/product-tab',
	];

	/**
	 * Get a file path for a given block file.
	 *
	 * @param string $path File path.
	 */
	public function get_file_path( $path ) {
		return WC_ABSPATH . WCAdminAssets::get_path( 'js' ) . trailingslashit( self::BLOCKS_DIR ) . $path;
	}

	/**
	 * Register all the product blocks.
	 */
	public function register_product_blocks() {
		foreach ( self::PRODUCT_BLOCKS as $block_name ) {
			$this->register_block( $block_name );
		}
	}

	/**
	 * Get the block name without the "woocommerce/" prefix.
	 *
	 * @param string $block_name Block name.
	 *
	 * @return string
	 */
	public function remove_block_prefix( $block_name ) {
		if ( 0 === strpos( $block_name, 'woocommerce/' ) ) {
			return substr_replace( $block_name, '', 0, strlen( 'woocommerce/' ) );
		}

		return $block_name;
	}

	/**
	 * Register a single block.
	 *
	 * @param string $block_name Block name.
	 *
	 * @return WP_Block_Type|false The registered block type on success, or false on failure.
	 */
	public function register_block( $block_name ) {
		$block_name      = $this->remove_block_prefix( $block_name );
		$block_json_file = $this->get_file_path( $block_name . '/block.json' );

		if ( ! file_exists( $block_json_file ) ) {
			return false;
		}

		$metadata = json_decode( file_get_contents( $block_json_file ), true );
		if ( ! is_array( $metadata ) || ! $metadata['name'] ) {
			return false;
		}

		$registry = \WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $metadata['name'] ) ) {
			$registry->unregister( $metadata['name'] );
		}

		return register_block_type_from_metadata( $block_json_file );
	}

}
