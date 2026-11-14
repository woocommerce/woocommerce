<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

use WC_Product_CSV_Importer_Controller;

if ( ! class_exists( 'WC_Product_CSV_Importer_Controller' ) ) {
	include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
}

/**
 * Subclasses `WC_Product_CSV_Importer_Controller` so callers outside the admin import
 * screen can use its protected column mapping methods without widening the parent's
 * visibility.
 *
 * The parent constructor reads `$_REQUEST`, which is empty under WP-CLI, so it falls back
 * to its default state there. Runners with request parameters, such as REST, would pass
 * them through, including `map_preferences`, which changes the auto-mapped columns.
 *
 * @since 11.4.0
 */
final class CsvImporterController extends WC_Product_CSV_Importer_Controller {

	/**
	 * Get the auto-mapped columns for the given raw headers.
	 *
	 * @param array $raw_headers Raw header columns.
	 * @param bool  $num_indexes If should use numbers or raw header columns as indexes.
	 * @return array
	 */
	public function get_auto_mapped_columns( array $raw_headers, bool $num_indexes = true ): array {
		return $this->auto_map_columns( $raw_headers, $num_indexes );
	}

	/**
	 * Get the available column mapping options.
	 *
	 * @param string $item Item name.
	 * @return array
	 */
	public function get_column_mapping_options( string $item = '' ): array {
		return $this->get_mapping_options( $item );
	}
}
