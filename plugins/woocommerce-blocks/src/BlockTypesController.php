<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\BlockTypes\AtomicBlock;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * BlockTypesController class.
 *
 * @since 5.0.0
 * @internal
 */
final class BlockTypesController {

	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	protected $asset_api;

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Constructor.
	 *
	 * @param AssetApi          $asset_api Instance of the asset API.
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry ) {
		$this->asset_api           = $asset_api;
		$this->asset_data_registry = $asset_data_registry;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'woocommerce_login_form_end', array( $this, 'redirect_to_field' ) );
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public function register_blocks() {
		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			$block_type_class    = __NAMESPACE__ . '\\BlockTypes\\' . $block_type;
			$block_type_instance = new $block_type_class( $this->asset_api, $this->asset_data_registry, new IntegrationRegistry() );
		}

		foreach ( self::get_atomic_blocks() as $block_type ) {
			$block_type_instance = new AtomicBlock( $this->asset_api, $this->asset_data_registry, new IntegrationRegistry(), $block_type );
		}
	}

	/**
	 * Adds a redirect field to the login form so blocks can redirect users after login.
	 */
	public function redirect_to_field() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['redirect_to'] ) ) {
			return;
		}
		echo '<input type="hidden" name="redirect" value="' . esc_attr( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Get list of block types.
	 *
	 * @return array
	 */
	protected function get_block_types() {
		global $wp_version, $pagenow;

		$block_types = [
			'AllReviews',
			'FeaturedCategory',
			'FeaturedProduct',
			'HandpickedProducts',
			'ProductBestSellers',
			'ProductCategories',
			'ProductCategory',
			'ProductNew',
			'ProductOnSale',
			'ProductsByAttribute',
			'ProductTopRated',
			'ReviewsByProduct',
			'ReviewsByCategory',
			'ProductSearch',
			'ProductTag',
			'AllProducts',
			'PriceFilter',
			'AttributeFilter',
			'ActiveFilters',
		];

		if ( Package::feature()->is_feature_plugin_build() ) {
			$block_types[] = 'Checkout';
			$block_types[] = 'Cart';
		}

		if ( Package::feature()->is_experimental_build() ) {
			$block_types[] = 'SingleProduct';
		}

		/**
		 * This disables specific blocks in Widget Areas by not registering them.
		 */
		if ( 'themes.php' === $pagenow ) {
			$block_types = array_diff(
				$block_types,
				[
					'AllProducts',
					'PriceFilter',
					'AttributeFilter',
					'ActiveFilters',
				]
			);
		}

		return $block_types;
	}

	/**
	 * Get atomic blocks types.
	 *
	 * @return array
	 */
	protected function get_atomic_blocks() {
		return [
			'product-title',
			'product-button',
			'product-image',
			'product-price',
			'product-rating',
			'product-sale-badge',
			'product-summary',
			'product-sku',
			'product-category-list',
			'product-tag-list',
			'product-stock-indicator',
			'product-add-to-cart',
		];
	}
}
