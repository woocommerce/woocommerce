<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry as AssetDataRegistry;

/**
 * AssetsController class.
 *
 * @since 5.0.0
 * @internal
 */
final class AssetsController {

	/**
	 * Asset API interface for various asset registration.
	 *
	 * @var AssetApi
	 */
	private $api;

	/**
	 * Constructor.
	 *
	 * @param AssetApi $asset_api  Asset API interface for various asset registration.
	 */
	public function __construct( AssetApi $asset_api ) {
		$this->api = $asset_api;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'body_class', array( $this, 'add_theme_body_class' ), 1 );
		add_action( 'admin_body_class', array( $this, 'add_theme_body_class' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'update_block_style_dependencies' ), 20 );
	}

	/**
	 * Register block scripts & styles.
	 */
	public function register_assets() {
		$this->register_style( 'wc-blocks-vendors-style', plugins_url( $this->api->get_block_asset_build_path( 'wc-blocks-vendors-style', 'css' ), __DIR__ ) );
		$this->register_style( 'wc-blocks-editor-style', plugins_url( $this->api->get_block_asset_build_path( 'wc-blocks-editor-style', 'css' ), __DIR__ ), [ 'wp-edit-blocks' ], 'all', true );
		$this->register_style( 'wc-blocks-style', plugins_url( $this->api->get_block_asset_build_path( 'wc-blocks-style', 'css' ), __DIR__ ), [ 'wc-blocks-vendors-style' ], 'all', true );

		$this->api->register_script( 'wc-blocks-middleware', 'build/wc-blocks-middleware.js', [], false );
		$this->api->register_script( 'wc-blocks-data-store', 'build/wc-blocks-data.js', [ 'wc-blocks-middleware' ] );
		$this->api->register_script( 'wc-blocks-vendors', $this->api->get_block_asset_build_path( 'wc-blocks-vendors' ), [], false );
		$this->api->register_script( 'wc-blocks-registry', 'build/wc-blocks-registry.js', [], false );
		$this->api->register_script( 'wc-blocks', $this->api->get_block_asset_build_path( 'wc-blocks' ), [ 'wc-blocks-vendors' ], false );
		$this->api->register_script( 'wc-blocks-shared-context', 'build/wc-blocks-shared-context.js', [] );
		$this->api->register_script( 'wc-blocks-shared-hocs', 'build/wc-blocks-shared-hocs.js', [], false );

		// The price package is shared externally so has no blocks prefix.
		$this->api->register_script( 'wc-price-format', 'build/price-format.js', [], false );

		if ( Package::feature()->is_feature_plugin_build() ) {
			$this->api->register_script( 'wc-blocks-checkout', is_admin() ? 'build/blocks-checkout-editor.js' : 'build/blocks-checkout.js', [] );
		}

		wp_add_inline_script(
			'wc-blocks-middleware',
			"
			var wcBlocksMiddlewareConfig = {
				storeApiNonce: '" . esc_js( wp_create_nonce( 'wc_store_api' ) ) . "',
				wcStoreApiNonceTimestamp: '" . esc_js( time() ) . "'
			};
			",
			'before'
		);
	}

	/**
	 * Add body classes to the frontend and within admin.
	 *
	 * @param string|array $classes Array or string of CSS classnames.
	 * @return string|array Modified classnames.
	 */
	public function add_theme_body_class( $classes ) {
		$class = 'theme-' . get_template();

		if ( is_array( $classes ) ) {
			$classes[] = $class;
		} else {
			$classes .= ' ' . $class . ' ';
		}

		return $classes;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( \Automattic\WooCommerce\Blocks\Package::get_path() . $file ) ) {
			return filemtime( \Automattic\WooCommerce\Blocks\Package::get_path() . $file );
		}
		return \Automattic\WooCommerce\Blocks\Package::get_version();
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @param string  $handle Name of the stylesheet. Should be unique.
	 * @param string  $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param array   $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string  $media  Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                        'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param boolean $rtl   Optional. Whether or not to register RTL styles.
	 */
	protected function register_style( $handle, $src, $deps = [], $media = 'all', $rtl = false ) {
		$filename = str_replace( plugins_url( '/', __DIR__ ), '', $src );
		$ver      = self::get_file_version( $filename );

		wp_register_style( $handle, $src, $deps, $ver, $media );

		if ( $rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	/**
	 * Update block style dependencies after they have been registered.
	 */
	public function update_block_style_dependencies() {
		$wp_styles = wp_styles();
		$style     = $wp_styles->query( 'wc-blocks-style', 'registered' );

		if ( ! $style ) {
			return;
		}

		// In WC < 5.5, `woocommerce-general` is not registered in block editor
		// screens, so we don't add it as a dependency if it's not registered.
		// In WC >= 5.5, `woocommerce-general` is registered on `admin_enqueue_scripts`,
		// so we need to check if it's registered here instead of on `init`.
		if (
			wp_style_is( 'woocommerce-general', 'registered' ) &&
			! in_array( 'woocommerce-general', $style->deps, true )
		) {
			$style->deps[] = 'woocommerce-general';
		}
	}
}
