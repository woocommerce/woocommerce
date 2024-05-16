<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;

class ImportProducts implements StepProcessor {
	// Do we really need it? If so, make it reusuable.
	private function cleanup() {
		global $wpdb;
		// @codingStandardsIgnoreStart.
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_original_id' ) );
		$wpdb->delete( $wpdb->posts, array(
			'post_type'   => 'product',
			'post_status' => 'importing',
		) );
		$wpdb->delete( $wpdb->posts, array(
			'post_type'   => 'product_variation',
			'post_status' => 'importing',
		) );
		// @codingStandardsIgnoreEnd.

		// Clean up orphaned data.
		$wpdb->query(
			"
				DELETE {$wpdb->posts}.* FROM {$wpdb->posts}
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->posts}.post_parent
				WHERE wp.ID IS NULL AND {$wpdb->posts}.post_type = 'product_variation'
			"
		);
		$wpdb->query(
			"
				DELETE {$wpdb->postmeta}.* FROM {$wpdb->postmeta}
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->postmeta}.post_id
				WHERE wp.ID IS NULL
			"
		);
		// @codingStandardsIgnoreStart.
		$wpdb->query( "
				DELETE tr.* FROM {$wpdb->term_relationships} tr
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE wp.ID IS NULL
				AND tt.taxonomy IN ( '" . implode( "','", array_map( 'esc_sql', get_object_taxonomies( 'product' ) ) ) . "' )
			" );
	}
	public function process( $schema ): StepProcessorResult {
		include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

		// @todo We should accept these from the schema.
		$params = array(
			'delimiter'          => ',',
			'start_pos'          =>  0,
			'mapping'            => array(), // PHPCS: input var ok.
			'update_existing'    => true, // PHPCS: input var ok.
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

		$importer         = \WC_Product_CSV_Importer_Controller::get_importer( $this->get_content_dir() . '/' . $schema->file, $params );
		$results          = $importer->import();
		$percent_complete = $importer->get_percent_complete();
		if (100===$percent_complete) {
			$this->cleanup();
		}
		// @todo check for errors.
		return StepProcessorResult::success('ImportProducts');
	}

	protected function get_content_dir() {
		return WP_CONTENT_DIR;
	}

	public function get_supported_step(): string {
		return 'importProducts';
	}
}
