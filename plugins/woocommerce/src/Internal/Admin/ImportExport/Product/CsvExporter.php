<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

use WC_Product_CSV_Exporter;

/**
 * Subclasses `WC_Product_CSV_Exporter` for runners with no admin request behind them,
 * such as WP-CLI.
 *
 * Writes its temporary CSV and headers files to `get_temp_dir()` instead of the uploads
 * directory the admin export flow uses. `ProductExporter` refuses to run when that directory
 * resolves inside the web root.
 *
 * @since 11.4.0
 */
final class CsvExporter extends WC_Product_CSV_Exporter {

	/**
	 * Rows written by pages before the current one.
	 *
	 * @var int
	 */
	private int $rows_in_earlier_pages = 0;

	/**
	 * Set the page, banking the rows of the page just finished.
	 *
	 * `ProductExporter` reuses one instance across pages, but the parent resets `exported_row_count`
	 * only per request, so the count would otherwise carry over from page to page.
	 *
	 * @param int $page Page number.
	 */
	public function set_page( $page ): void {
		$this->rows_in_earlier_pages += $this->exported_row_count;
		$this->exported_row_count     = 0;

		parent::set_page( $page );
	}

	/**
	 * Get the number of rows exported across all pages so far.
	 *
	 * The parent assumes one instance per page. `get_percent_complete()` still calls the parent
	 * version directly, which sees only the current page's rows, as in an admin request.
	 *
	 * @return int
	 */
	public function get_total_exported() {
		return $this->rows_in_earlier_pages + $this->exported_row_count;
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	public function get_file_path() {
		return get_temp_dir() . $this->get_filename();
	}
}
