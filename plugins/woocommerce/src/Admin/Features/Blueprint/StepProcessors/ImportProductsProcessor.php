<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class ImportProductsProcessor implements StepProcessor {

	public function process( $schema ) {
		include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

		// @todo We should accept these from the schema.
		$params = array(
			'delimiter'          => ',',
			'start_pos'          =>  0,
			'mapping'            => array(), // PHPCS: input var ok.
			'update_existing'    => false, // PHPCS: input var ok.
			'character_encoding' => '',

			/**
			 * Batch size for the product import process.
			 *
			 * @param int $size Batch size.
			 *
			 * @since
			 */
			'lines'              => apply_filters( 'woocommerce_product_import_batch_size', 30 ),
			'parse'              => true,
		);

		$importer         = \WC_Product_CSV_Importer_Controller::get_importer( ABSPATH . '/' . $schema->file, $params );
		$results          = $importer->import();
		// @todo check for errors.

		return true;
	}
}
