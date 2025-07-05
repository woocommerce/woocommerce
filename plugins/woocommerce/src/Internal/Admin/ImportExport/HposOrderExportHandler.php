<?php
/**
 * Handles the export of HPOS (High-Performance Order Storage) orders
 * via the WordPress Tools > Export functionality.
 *
 * @since {$version}
 */

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

defined( 'ABSPATH' ) || exit;


class HposOrderExportHandler {

	/**
	 * Class instance.
	 *
	 * @var HposOrderExportHandler|null
	 */
	protected static $instance = null;

	/**
	 * Constructor. Registers the export hook.
	 */
	public function __construct() {
	}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
