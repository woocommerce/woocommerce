<?php
/**
 * Contains the Bootstrap class
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Domain;

use Automattic\WooCommerce\Blocks\Assets as OldAssets;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\BackCompatAssetDataRegistry;
use Automattic\WooCommerce\Blocks\Library;
use Automattic\WooCommerce\Blocks\Registry\Container;
use Automattic\WooCommerce\Blocks\RestApi;

/**
 * Takes care of bootstrapping the plugin.
 *
 * @since 2.5.0
 */
class Bootstrap {

	/**
	 * Holds the Dependency Injection Container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Holds the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Constructor
	 *
	 * @param Container $container  The Dependency Injection Container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
		$this->package   = $container->get( Package::class );
		$this->init();
		/**
		 * Usable as a safe event hook for when the plugin has been loaded.
		 */
		do_action( 'woocommerce_blocks_loaded' );
	}

	/**
	 * Init the package - load the blocks library and define constants.
	 */
	public function init() {
		if ( ! $this->has_dependencies() ) {
			return;
		}

		$this->remove_core_blocks();

		if ( ! $this->is_built() ) {
			$this->add_build_notice();
		}

		// register core dependencies with the container.
		$this->container->register(
			AssetApi::class,
			function ( Container $container ) {
				return new AssetApi( $container->get( Package::class ) );
			}
		);
		$this->container->register(
			AssetDataRegistry::class,
			function( Container $container ) {
				$asset_api        = $container->get( AssetApi::class );
				$load_back_compat = defined( 'WC_ADMIN_VERSION_NUMBER' )
					&& version_compare( WC_ADMIN_VERSION_NUMBER, '0.19.0', '<=' );
				return $load_back_compat
					? new BackCompatAssetDataRegistry( $asset_api )
					: new AssetDataRegistry( $asset_api );
			}
		);

		// load AssetDataRegistry.
		$this->container->get( AssetDataRegistry::class );

		Library::init();
		OldAssets::init();
		RestApi::init();
	}

	/**
	 * Check dependencies exist.
	 *
	 * @return boolean
	 */
	protected function has_dependencies() {
		return class_exists( 'WooCommerce' ) && function_exists( 'register_block_type' );
	}

	/**
	 * See if files have been built or not.
	 *
	 * @return bool
	 */
	protected function is_built() {
		return file_exists(
			$this->package->get_path( 'build/featured-product.js' )
		);
	}

	/**
	 * Add a notice stating that the build has not been done yet.
	 */
	protected function add_build_notice() {
		add_action(
			'admin_notices',
			function() {
				echo '<div class="error"><p>';
				printf(
					/* Translators: %1$s is the install command, %2$s is the build command, %3$s is the watch command. */
					esc_html__( 'WooCommerce Blocks development mode requires files to be built. From the plugin directory, run %1$s to install dependencies, %2$s to build the files or %3$s to build the files and watch for changes.', 'woocommerce' ),
					'<code>npm install</code>',
					'<code>npm run build</code>',
					'<code>npm start</code>'
				);
				echo '</p></div>';
			}
		);
	}

	/**
	 * Remove core blocks.
	 *
	 * Older installs of WooCommerce (3.6 and below) did not use the blocks package and instead included classes directly.
	 * This code disables those core classes when running blocks as a feature plugin. Newer versions which use the Blocks package are unaffected.
	 *
	 * When the feature plugin supports only WooCommerce 3.7+ this method can be removed.
	 */
	protected function remove_core_blocks() {
		remove_action( 'init', array( 'WC_Block_Library', 'init' ) );
		remove_action( 'init', array( 'WC_Block_Library', 'register_blocks' ) );
		remove_action( 'init', array( 'WC_Block_Library', 'register_assets' ) );
		remove_filter( 'block_categories', array( 'WC_Block_Library', 'add_block_category' ) );
		remove_action( 'admin_print_footer_scripts', array( 'WC_Block_Library', 'print_script_settings' ), 1 );
		remove_action( 'init', array( 'WGPB_Block_Library', 'init' ) );
	}
}
