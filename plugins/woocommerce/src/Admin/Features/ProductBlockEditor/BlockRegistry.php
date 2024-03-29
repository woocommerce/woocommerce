<?php
/**
 * WooCommerce Product Editor Block Registration
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Product block registration and style registration functionality.
 */
class BlockRegistry {

	/**
	 * Generic blocks directory.
	 */
	const GENERIC_BLOCKS_DIR = 'product-editor/blocks/generic';
	/**
	 * Product fields blocks directory.
	 */
	const PRODUCT_FIELDS_BLOCKS_DIR = 'product-editor/blocks/product-fields';
	/**
	 * Array of all available generic blocks.
	 */
	const GENERIC_BLOCKS = array(
		'woocommerce/conditional',
		'woocommerce/product-checkbox-field',
		'woocommerce/product-collapsible',
		'woocommerce/product-radio-field',
		'woocommerce/product-pricing-field',
		'woocommerce/product-section',
		'woocommerce/product-section-description',
		'woocommerce/product-subsection',
		'woocommerce/product-subsection-description',
		'woocommerce/product-details-section-description',
		'woocommerce/product-tab',
		'woocommerce/product-toggle-field',
		'woocommerce/product-taxonomy-field',
		'woocommerce/product-text-field',
		'woocommerce/product-text-area-field',
		'woocommerce/product-number-field',
		'woocommerce/product-linked-list-field',
		'woocommerce/product-select-field',
	);

	/**
	 * Array of all available product fields blocks.
	 */
	const PRODUCT_FIELDS_BLOCKS = array(
		'woocommerce/product-catalog-visibility-field',
		'woocommerce/product-custom-fields',
		'woocommerce/product-custom-fields-toggle-field',
		'woocommerce/product-description-field',
		'woocommerce/product-downloads-field',
		'woocommerce/product-images-field',
		'woocommerce/product-inventory-email-field',
		'woocommerce/product-sku-field',
		'woocommerce/product-name-field',
		'woocommerce/product-regular-price-field',
		'woocommerce/product-sale-price-field',
		'woocommerce/product-schedule-sale-fields',
		'woocommerce/product-shipping-class-field',
		'woocommerce/product-shipping-dimensions-fields',
		'woocommerce/product-summary-field',
		'woocommerce/product-tag-field',
		'woocommerce/product-inventory-quantity-field',
		'woocommerce/product-variation-items-field',
		'woocommerce/product-password-field',
		'woocommerce/product-list-field',
		'woocommerce/product-has-variations-notice',
		'woocommerce/product-single-variation-notice',
	);

	/**
	 * Singleton instance.
	 *
	 * @var BlockRegistry
	 */
	private static $instance = null;

	/**
	 * Registered blocks.
	 *
	 * @var array
	 */
	private $registered_blocks = [];

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance(): BlockRegistry {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'block_categories_all', array( $this, 'register_categories' ), 10, 2 );
		$this->register_product_blocks();
		add_action( 'admin_enqueue_scripts', array( $this, 'register_block_views' ) );
	}

	/**
	 * Register the block views in the admin area.
	 *
	 * @todo These could be fetched dynamically to improve performance.
	 */
	public function register_block_views() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		foreach ( $this->registered_blocks as $block ) {
			if ( ! empty( $block->view_script_handles ) ) {
				foreach ( $block->view_script_handles as $view_script_handle ) {
					// global $wp_scripts;
					// wp_die(print_r($wp_scripts));
					wp_enqueue_script( $view_script_handle );
				}
			}
		}
	}

	/**
	 * Get a file path for a given block file.
	 *
	 * @param string $path File path.
	 * @param string $dir File directory.
	 */
	private function get_file_path( $path, $dir ) {
		return WC_ABSPATH . WCAdminAssets::get_path( 'js' ) . trailingslashit( $dir ) . $path;
	}

	/**
	 * Register all the product blocks.
	 */
	private function register_product_blocks() {
		foreach ( self::PRODUCT_FIELDS_BLOCKS as $block_name ) {
			$this->registered_blocks[] = $this->register_block( $block_name, self::PRODUCT_FIELDS_BLOCKS_DIR );
		}
		foreach ( self::GENERIC_BLOCKS as $block_name ) {
			$this->registered_blocks[] = $this->register_block( $block_name, self::GENERIC_BLOCKS_DIR );
		}
	}

	/**
	 * Register product related block categories.
	 *
	 * @param array[]                 $block_categories Array of categories for block types.
	 * @param WP_Block_Editor_Context $editor_context   The current block editor context.
	 */
	public function register_categories( $block_categories, $editor_context ) {
		if ( INIT::EDITOR_CONTEXT_NAME === $editor_context->name ) {
			$block_categories[] = array(
				'slug'  => 'woocommerce',
				'title' => __( 'WooCommerce', 'woocommerce' ),
				'icon'  => null,
			);
		}

		return $block_categories;
	}

	/**
	 * Get the block name without the "woocommerce/" prefix.
	 *
	 * @param string $block_name Block name.
	 *
	 * @return string
	 */
	private function remove_block_prefix( $block_name ) {
		if ( 0 === strpos( $block_name, 'woocommerce/' ) ) {
			return substr_replace( $block_name, '', 0, strlen( 'woocommerce/' ) );
		}

		return $block_name;
	}

	/**
	 * Augment the attributes of a block by adding attributes that are used by the product editor.
	 *
	 * @param array $attributes Block attributes.
	 */
	private function augment_attributes( $attributes ) {
		// Note: If you modify this function, also update the client-side
		// registerWooBlockType function in @woocommerce/block-templates.
		return array_merge(
			$attributes,
			array(
				'_templateBlockId'                => array(
					'type'               => 'string',
					'__experimentalRole' => 'content',
				),
				'_templateBlockOrder'             => array(
					'type'               => 'integer',
					'__experimentalRole' => 'content',
				),
				'_templateBlockHideConditions'    => array(
					'type'               => 'array',
					'__experimentalRole' => 'content',
				),
				'_templateBlockDisableConditions' => array(
					'type'               => 'array',
					'__experimentalRole' => 'content',
				),
				'disabled'                        => isset( $attributes['disabled'] ) ? $attributes['disabled'] : array(
					'type'               => 'boolean',
					'__experimentalRole' => 'content',
				),
			)
		);
	}

	/**
	 * Augment the uses_context of a block by adding attributes that are used by the product editor.
	 *
	 * @param array $uses_context Block uses_context.
	 */
	private function augment_uses_context( $uses_context ) {
		// Note: If you modify this function, also update the client-side
		// registerProductEditorBlockType function in @woocommerce/product-editor.
		return array_merge(
			isset( $uses_context ) ? $uses_context : array(),
			array(
				'postType',
			)
		);
	}

	/**
	 * Register a single block.
	 *
	 * @param string $block_name Block name.
	 * @param string $block_dir Block directory.
	 *
	 * @return WP_Block_Type|false The registered block type on success, or false on failure.
	 */
	private function register_block( $block_name, $block_dir ) {
		$block_name      = $this->remove_block_prefix( $block_name );
		$block_json_file = $this->get_file_path( $block_name . '/block.json', $block_dir );

		return $this->register_block_type_from_metadata( $block_json_file );
	}

	/**
	 * Check if a block is registered.
	 *
	 * @param string $block_name Block name.
	 */
	public function is_registered( $block_name ): bool {
		$registry = \WP_Block_Type_Registry::get_instance();

		return $registry->is_registered( $block_name );
	}

	/**
	 * Unregister a block.
	 *
	 * @param string $block_name Block name.
	 */
	public function unregister( $block_name ) {
		$registry = \WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $block_name ) ) {
			$registry->unregister( $block_name );
		}
	}

	/**
	 * Register a block type from metadata stored in the block.json file.
	 *
	 * @param string $file_or_folder Path to the JSON file with metadata definition for the block or
	 * path to the folder where the `block.json` file is located.
	 *
	 * @return \WP_Block_Type|false The registered block type on success, or false on failure.
	 */
	public function register_block_type_from_metadata( $file_or_folder ) {
		$metadata_file = ( ! str_ends_with( $file_or_folder, 'block.json' ) )
			? trailingslashit( $file_or_folder ) . 'block.json'
			: $file_or_folder;

		if ( ! file_exists( $metadata_file ) ) {
			return false;
		}

		// We are dealing with a local file, so we can use file_get_contents.
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$metadata = json_decode( file_get_contents( $metadata_file ), true );
		if ( ! is_array( $metadata ) || ! $metadata['name'] ) {
			return false;
		}

		$this->unregister( $metadata['name'] );

		return register_block_type_from_metadata(
			$metadata_file,
			array(
				'attributes'   => $this->augment_attributes( isset( $metadata['attributes'] ) ? $metadata['attributes'] : array() ),
				'uses_context' => $this->augment_uses_context( isset( $metadata['usesContext'] ) ? $metadata['usesContext'] : array() ),
			)
		);
	}
}
