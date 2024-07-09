<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

/**
 * Class ImportProducts
 *
 * This class imports WooCommerce products and implements the StepProcessor interface.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Importers
 */
class ImportProducts implements StepProcessor {

	/**
	 * Clean up temporary and orphaned data.
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	private function cleanup() {
		global $wpdb;
		// @codingStandardsIgnoreStart
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_original_id' ) );
		$wpdb->delete( $wpdb->posts, array(
			'post_type'   => 'product',
			'post_status' => 'importing',
		) );
		$wpdb->delete( $wpdb->posts, array(
			'post_type'   => 'product_variation',
			'post_status' => 'importing',
		) );
		// @codingStandardsIgnoreEnd

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
		// @codingStandardsIgnoreStart
		$wpdb->query(
			"
            DELETE tr.* FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE wp.ID IS NULL
            AND tt.taxonomy IN ( '" . implode( "','", array_map( 'esc_sql', get_object_taxonomies( 'product' ) ) ) . "' )
            "
		);
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Process the import of products.
	 *
	 * @param object $schema The schema object containing import details.
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';

		$params = array(
			'delimiter'          => ',',
			'start_pos'          => 0,
			'mapping'            => array(), // PHPCS: input var ok.
			'update_existing'    => true, // PHPCS: input var ok.
			'character_encoding' => '',

			/**
			 * Batch size for the product import process.
			 *
			 * @param int $size Batch size.
			 * @since 0.0.0
			 */
			'lines'              => apply_filters( 'woocommerce_product_import_batch_size', 30 ),
			'parse'              => true,
		);

		$importer         = \WC_Product_CSV_Importer_Controller::get_importer( $this->get_content_dir() . '/' . $schema->file, $params );
		$results          = $importer->import();
		$percent_complete = $importer->get_percent_complete();

		if ( 100 === $percent_complete ) {
			$this->cleanup();
		}

		return StepProcessorResult::success( 'ImportProducts' );
	}

	/**
	 * Get the content directory.
	 *
	 * @return string
	 */
	protected function get_content_dir() {
		return WP_CONTENT_DIR;
	}

	/**
	 * Get the class name for the step.
	 *
	 * @return string
	 */
	public function get_step_class(): string {
		return 'test';
	}
}
