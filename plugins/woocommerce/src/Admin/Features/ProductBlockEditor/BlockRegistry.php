<?php
/**
 * WooCommerce Product Editor Block Registration compatibility shim.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Removed product block editor block registry.
 *
 * @deprecated 10.9.0 Product editor extension APIs were deprecated. The product block editor was removed in 11.0.0 with no replacement.
 */
class BlockRegistry {
	/**
	 * Version that product editor APIs were deprecated in.
	 */
	const DEPRECATED_SINCE = '10.9.0';

	/**
	 * Generic blocks directory.
	 */
	const GENERIC_BLOCKS_DIR = '';

	/**
	 * Product fields blocks directory.
	 */
	const PRODUCT_FIELDS_BLOCKS_DIR = '';

	/**
	 * Array of all available generic blocks.
	 */
	const GENERIC_BLOCKS = array();

	/**
	 * Array of all available product fields blocks.
	 */
	const PRODUCT_FIELDS_BLOCKS = array();

	/**
	 * Singleton instance.
	 *
	 * @var BlockRegistry|null
	 */
	private static $instance = null;

	/**
	 * Whether the removal warning has already been logged for the current request.
	 *
	 * @var bool
	 */
	private static $removal_warning_logged = false;

	/**
	 * Constructor.
	 */
	protected function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @return BlockRegistry
	 */
	public static function get_instance(): BlockRegistry {
		$instance = self::$instance;

		if ( null === $instance ) {
			$instance       = new self();
			self::$instance = $instance;
		}

		self::maybe_log_removal_warning();

		return $instance;
	}

	/**
	 * Register product related block categories.
	 *
	 * @param array $block_categories Array of categories for block types.
	 * @param mixed $editor_context   The current block editor context.
	 *
	 * @return array[]
	 */
	public function register_categories( $block_categories, $editor_context ) {
		unset( $editor_context );

		self::maybe_log_removal_warning();

		return $block_categories;
	}

	/**
	 * Check if a block is registered.
	 *
	 * @param string $block_name Block name.
	 *
	 * @return bool
	 */
	public function is_registered( $block_name ): bool {
		unset( $block_name );

		self::maybe_log_removal_warning();

		return false;
	}

	/**
	 * Unregister a block.
	 *
	 * @param string $block_name Block name.
	 * @return void
	 */
	public function unregister( $block_name ) {
		unset( $block_name );

		self::maybe_log_removal_warning();
	}

	/**
	 * Register a block type from metadata stored in the block.json file.
	 *
	 * @param string $file_or_folder Path to the JSON file with metadata definition for the block or
	 *                               path to the folder where the `block.json` file is located.
	 *
	 * @return false
	 */
	public function register_block_type_from_metadata( $file_or_folder ) {
		unset( $file_or_folder );

		self::maybe_log_removal_warning();

		return false;
	}

	/**
	 * Log a warning about the removed compatibility class.
	 */
	private static function maybe_log_removal_warning(): void {
		if ( self::$removal_warning_logged || ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		self::$removal_warning_logged = true;

		wc_get_logger()->warning(
			'Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry is a temporary compatibility shim and will be removed soon. Product editor extension APIs were deprecated in WooCommerce 10.9.0, and the product block editor was removed in WooCommerce 11.0.0 with no replacement.',
			array( 'source' => 'product-block-editor' )
		);
	}
}
