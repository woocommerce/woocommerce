<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Importer;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Data_Store;

/**
 * Integrates WooCommerce with the WordPress importer (Tools > Import > WordPress).
 *
 * The WordPress importer inserts product posts and meta directly via the WP core
 * functions (wp_insert_post, update_post_meta), bypassing WooCommerce's CRUD
 * pipeline. As a result, rows in the wc_product_meta_lookup table are never
 * populated for imported products, which causes blocks and APIs that rely on
 * that table (e.g. the Filter Products by Price block) to behave incorrectly.
 *
 * This class watches the importer for product posts and refreshes the lookup
 * table rows for each one when the import completes.
 *
 * @since 10.9.0
 */
class WordPressImporterIntegration implements RegisterHooksInterface {

	/**
	 * Product post IDs collected during an import run.
	 *
	 * @var int[]
	 */
	private array $imported_product_ids = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_import_insert_post', array( $this, 'track_imported_post' ), 10, 4 );
		add_action( 'import_end', array( $this, 'refresh_lookup_for_imported_products' ) );
	}

	/**
	 * Track product posts inserted by the WordPress importer.
	 *
	 * Fired by the WordPress importer for every post it inserts. We only retain
	 * IDs that correspond to products or product variations, so that the lookup
	 * table refresh on import_end is cheap.
	 *
	 * @param int                  $post_id          Newly inserted post ID.
	 * @param int                  $original_post_id Original post ID from the WXR file (unused).
	 * @param array<string, mixed> $postdata         Post data array passed to wp_insert_post (unused).
	 * @param array<string, mixed> $post             Original post data from the WXR file.
	 *
	 * @return void
	 */
	public function track_imported_post( $post_id, $original_post_id, $postdata, $post ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		$post_type = '';
		if ( is_array( $post ) && isset( $post['post_type'] ) ) {
			$post_type = (string) $post['post_type'];
		} else {
			$post_type = (string) get_post_type( $post_id );
		}

		if ( 'product' !== $post_type && 'product_variation' !== $post_type ) {
			return;
		}

		$this->imported_product_ids[ $post_id ] = $post_id;
	}

	/**
	 * Refresh wc_product_meta_lookup rows for products imported in this run.
	 *
	 * Called on import_end (fired by both the canonical WordPress Importer and
	 * the WP-CLI importer). After processing, the internal buffer is cleared so
	 * a subsequent import run starts from a clean slate.
	 *
	 * @return void
	 */
	public function refresh_lookup_for_imported_products() {
		if ( empty( $this->imported_product_ids ) ) {
			return;
		}

		$product_ids                = $this->imported_product_ids;
		$this->imported_product_ids = array();

		$data_store = WC_Data_Store::load( 'product' );

		foreach ( $product_ids as $product_id ) {
			// `refresh_product_lookup_table` lives on WC_Product_Data_Store_CPT and is reached
			// via WC_Data_Store::__call. PHPStan can't see through the magic method.
			// @phpstan-ignore-next-line method.notFound
			$data_store->refresh_product_lookup_table( $product_id );
		}
	}

	/**
	 * Return the IDs collected so far in the current run.
	 *
	 * Exposed for tests.
	 *
	 * @return int[]
	 */
	public function get_tracked_product_ids(): array {
		return array_values( $this->imported_product_ids );
	}
}
