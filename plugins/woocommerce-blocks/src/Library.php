<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\BlockTypes\AtomicBlock;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Library class.
 *
 * @deprecated $VID:$ This class will be removed in a future release. This has been replaced by BlockTypesController.
 * @internal
 */
class Library {

	/**
	 * Initialize block library features.
	 *
	 * @deprecated $VID:$
	 */
	public static function init() {
		_deprecated_function( 'Library::init', '$VID:$' );
	}

	/**
	 * Register custom tables within $wpdb object.
	 *
	 * @deprecated $VID:$
	 */
	public static function define_tables() {
		_deprecated_function( 'Library::define_tables', '$VID:$' );
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 *
	 * @deprecated $VID:$
	 */
	public static function register_blocks() {
		_deprecated_function( 'Library::register_blocks', '$VID:$' );
	}
}
