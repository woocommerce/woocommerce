<?php
/**
 * WooCommerce Product Importer
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Core;

use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Error;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerceProductImporter class.
 *
 * Handles the creation and updating of WooCommerce products from mapped data.
 * This class focuses on the actual product creation logic, following WordPress
 * coding standards and our established architecture patterns.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
class WooCommerceProductImporter {

	/**
	 * Default timeout for image downloads in seconds.
	 *
	 * @var int
	 */
	private const DEFAULT_IMAGE_TIMEOUT = 10;

	/**
	 * Maximum number of images to process per product.
	 *
	 * @var int
	 */
	private const MAX_IMAGES_PER_PRODUCT = 50;

	/**
	 * Import options and configuration.
	 *
	 * @var array
	 */
	private array $import_options;

	/**
	 * Statistics tracking for import operations.
	 *
	 * @var array
	 */
	private array $import_stats = array(
		'products_created'   => 0,
		'products_updated'   => 0,
		'products_skipped'   => 0,
		'images_processed'   => 0,
		'errors_encountered' => 0,
	);

	/**
	 * Migration data including image and variation mappings for session persistence.
	 *
	 * @var array
	 */
	private array $migration_data = array(
		'images_mapping'     => array(),
		'variations_mapping' => array(),
	);

	/**
	 * Constructor - parameterless to support WooCommerce DI container.
	 */
	public function __construct() {
		$this->import_options = $this->get_default_options();
	}

	/**
	 * Configure the importer with options.
	 *
	 * @param array $options Import options and configuration.
	 */
	public function configure( array $options ): void {
		$this->import_options = array_merge( $this->import_options, $options );
	}

	/**
	 * Import a single product from mapped data.
	 *
	 * @param array $product_data Mapped WooCommerce product data.
	 * @param array $source_data  Original source platform data for reference.
	 * @return array Import result with status and details.
	 */
	public function import_product( array $product_data, array $source_data = array() ): array {
		try {
			$validation_result = $this->validate_product_data( $product_data );
			if ( ! $validation_result['valid'] ) {
				return $this->create_error_result( 'validation_failed', $validation_result['message'], $product_data );
			}

			$existing_product_id = $this->find_existing_product( $product_data, $source_data );

			if ( $existing_product_id && $this->import_options['skip_existing'] ) {
				++$this->import_stats['products_skipped'];
				return $this->create_success_result( 'skipped', $existing_product_id, 'Product already exists and skip_existing is enabled' );
			}

			$product_type = $this->determine_product_type( $product_data );
			$product      = $existing_product_id ? wc_get_product( $existing_product_id ) : $this->create_product_object( $product_type );

			if ( ! $product ) {
				return $this->create_error_result( 'product_creation_failed', 'Failed to create product object', $product_data );
			}

			if ( $existing_product_id ) {
				$existing_migration_data = $product->get_meta( '_migration_data' );
				if ( is_array( $existing_migration_data ) ) {
					$this->migration_data['images_mapping']     = $existing_migration_data['images_mapping'] ?? array();
					$this->migration_data['variations_mapping'] = $existing_migration_data['variations_mapping'] ?? array();
				}
			}

			$this->set_basic_product_properties( $product, $product_data );

			switch ( $product_type ) {
				case 'variable':
					$this->handle_variable_product( $product, $product_data );
					break;
				case 'simple':
				default:
					$this->handle_simple_product( $product, $product_data );
					break;
			}

			$product_id = $product->save();

			if ( ! $product_id ) {
				return $this->create_error_result( 'save_failed', 'Failed to save product to database', $product_data );
			}

			$this->handle_post_save_operations( $product_id, $product_data, $source_data );

			if ( $existing_product_id ) {
				++$this->import_stats['products_updated'];
			} else {
				++$this->import_stats['products_created'];
			}

			$action = $existing_product_id ? 'updated' : 'created';
			return $this->create_success_result( $action, $product_id, "Product {$action} successfully" );

		} catch ( Exception $e ) {
			++$this->import_stats['errors_encountered'];
			return $this->create_error_result( 'exception', $e->getMessage(), $product_data );
		}
	}

	/**
	 * Import a batch of products.
	 *
	 * @param array $products_data Array of mapped product data.
	 * @param array $source_data_batch Array of original source data for reference.
	 * @return array Batch import results.
	 */
	public function import_batch( array $products_data, array $source_data_batch = array() ): array {
		$results     = array();
		$batch_stats = array(
			'successful' => 0,
			'failed'     => 0,
			'skipped'    => 0,
		);

		foreach ( $products_data as $index => $product_data ) {
			$source_data = $source_data_batch[ $index ] ?? array();
			$result      = $this->import_product( $product_data, $source_data );

			$results[] = $result;

			if ( 'success' === $result['status'] ) {
				if ( 'skipped' === $result['action'] ) {
					++$batch_stats['skipped'];
				} else {
					++$batch_stats['successful'];
				}
			} else {
				++$batch_stats['failed'];
			}
		}

		return array(
			'results' => $results,
			'stats'   => $batch_stats,
		);
	}

	/**
	 * Get current import statistics.
	 *
	 * @return array Import statistics.
	 */
	public function get_import_stats(): array {
		return $this->import_stats;
	}

	/**
	 * Reset import statistics.
	 */
	public function reset_stats(): void {
		$this->import_stats = array(
			'products_created'   => 0,
			'products_updated'   => 0,
			'products_skipped'   => 0,
			'images_processed'   => 0,
			'errors_encountered' => 0,
		);
	}

	/**
	 * Get default import options.
	 *
	 * @return array Default options.
	 */
	private function get_default_options(): array {
		return array(
			'skip_existing'          => false,
			'update_existing'        => true,
			'import_images'          => true,
			'image_timeout'          => self::DEFAULT_IMAGE_TIMEOUT,
			'max_images_per_product' => self::MAX_IMAGES_PER_PRODUCT,
			'skip_duplicate_images'  => false,  // Set to true for faster imports.
			'create_categories'      => true,
			'create_tags'            => true,
			'handle_variations'      => true,
			'dry_run'                => false,
		);
	}

	/**
	 * Validate product data before import.
	 *
	 * @param array $product_data Product data to validate.
	 * @return array Validation result.
	 */
	private function validate_product_data( array $product_data ): array {
		$required_fields = array( 'name' );
		$missing_fields  = array();

		foreach ( $required_fields as $field ) {
			if ( empty( $product_data[ $field ] ) ) {
				$missing_fields[] = $field;
			}
		}

		if ( ! empty( $missing_fields ) ) {
			return array(
				'valid'   => false,
				'message' => 'Missing required fields: ' . implode( ', ', $missing_fields ),
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Find existing product by various identifiers.
	 *
	 * @param array $product_data Mapped product data.
	 * @return int|null Existing product ID or null if not found.
	 */
	private function find_existing_product( array $product_data ): ?int {
		if ( ! empty( $product_data['original_product_id'] ) ) {
			$existing_posts = get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'any', // Find regardless of status.
					'meta_key'    => '_original_product_id',
					'meta_value'  => $product_data['original_product_id'],
					'fields'      => 'ids',
					'numberposts' => 1,
				)
			);

			if ( ! empty( $existing_posts ) ) {
				return (int) $existing_posts[0];
			}
		}

		if ( ! empty( $product_data['sku'] ) ) {
			$product_id = wc_get_product_id_by_sku( $product_data['sku'] );
			if ( $product_id ) {
				return $product_id;
			}
		}

		if ( ! empty( $product_data['slug'] ) ) {
			$post = get_page_by_path( $product_data['slug'], OBJECT, 'product' );
			if ( $post ) {
				return $post->ID;
			}
		}

		return null;
	}


	/**
	 * Determine product type from product data.
	 *
	 * @param array $product_data Product data.
	 * @return string Product type.
	 */
	private function determine_product_type( array $product_data ): string {
		if ( ! empty( $product_data['variations'] ) && count( $product_data['variations'] ) > 1 ) {
			return 'variable';
		}

		if ( ! empty( $product_data['attributes'] ) ) {
			foreach ( $product_data['attributes'] as $attribute ) {
				if ( ! empty( $attribute['variation'] ) ) {
					return 'variable';
				}
			}
		}

		return 'simple';
	}

	/**
	 * Create appropriate product object based on type.
	 *
	 * @param string $product_type Product type.
	 * @return WC_Product|null Product object or null on failure.
	 */
	private function create_product_object( string $product_type ): ?WC_Product {
		switch ( $product_type ) {
			case 'variable':
				return new WC_Product_Variable();
			case 'simple':
			default:
				return new WC_Product_Simple();
		}
	}

	/**
	 * Set basic product properties common to all product types.
	 *
	 * @param WC_Product $product      Product object.
	 * @param array      $product_data Product data.
	 */
	private function set_basic_product_properties( WC_Product $product, array $product_data ): void {
		$product->set_name( $product_data['name'] );

		if ( ! empty( $product_data['slug'] ) ) {
			$product->set_slug( $product_data['slug'] );
		}

		if ( ! empty( $product_data['description'] ) ) {
			$product->set_description( $product_data['description'] );
		}

		if ( ! empty( $product_data['short_description'] ) ) {
			$product->set_short_description( $product_data['short_description'] );
		}

		if ( ! empty( $product_data['status'] ) ) {
			$product->set_status( $product_data['status'] );
		}

		if ( ! empty( $product_data['sku'] ) ) {
			$product->set_sku( $product_data['sku'] );
		}

		if ( isset( $product_data['catalog_visibility'] ) ) {
			$product->set_catalog_visibility( $product_data['catalog_visibility'] );
		}

		if ( ! empty( $product_data['date_created'] ) ) {
			$product->set_date_created( $product_data['date_created'] );
		}

		if ( ! empty( $product_data['weight'] ) ) {
			$product->set_weight( $product_data['weight'] );
		}

		if ( ! empty( $product_data['meta_data'] ) ) {
			foreach ( $product_data['meta_data'] as $meta ) {
				if ( ! empty( $meta['key'] ) ) {
					$product->add_meta_data( $meta['key'], $meta['value'] ?? '', true );
				}
			}
		}
	}

	/**
	 * Handle simple product specific data.
	 *
	 * @param WC_Product_Simple $product      Simple product object.
	 * @param array             $product_data Product data.
	 */
	private function handle_simple_product( WC_Product_Simple $product, array $product_data ): void {
		if ( ! empty( $product_data['price'] ) ) {
			$product->set_regular_price( $product_data['price'] );
			$product->set_price( $product_data['price'] );
		}

		if ( ! empty( $product_data['sale_price'] ) ) {
			$product->set_sale_price( $product_data['sale_price'] );
			$product->set_price( $product_data['sale_price'] );
		}

		if ( isset( $product_data['manage_stock'] ) ) {
			$product->set_manage_stock( $product_data['manage_stock'] );
		}

		if ( ! empty( $product_data['stock_quantity'] ) ) {
			$product->set_stock_quantity( (int) $product_data['stock_quantity'] );
		}

		if ( ! empty( $product_data['stock_status'] ) ) {
			$product->set_stock_status( $product_data['stock_status'] );
		}
	}

	/**
	 * Handle variable product specific data.
	 *
	 * @param WC_Product_Variable $product      Variable product object.
	 * @param array               $product_data Product data.
	 */
	private function handle_variable_product( WC_Product_Variable $product, array $product_data ): void {
		if ( ! empty( $product_data['attributes'] ) ) {
			$this->set_product_attributes( $product, $product_data['attributes'] );
		}

		$product_id = $product->save();

		if ( ! empty( $product_data['variations'] ) && $this->import_options['handle_variations'] ) {
			$this->create_product_variations( $product_id, $product_data['variations'] );
		}
	}

	/**
	 * Set product attributes.
	 *
	 * @param WC_Product $product    Product object.
	 * @param array      $attributes Attributes data.
	 */
	private function set_product_attributes( WC_Product $product, array $attributes ): void {
		$product_attributes = array();

		foreach ( $attributes as $attribute_data ) {
			if ( empty( $attribute_data['name'] ) ) {
				continue;
			}

			$attribute = new \WC_Product_Attribute();
			$attribute->set_name( $attribute_data['name'] );
			$attribute->set_options( $attribute_data['options'] ?? array() );
			$attribute->set_variation( $attribute_data['variation'] ?? false );
			$attribute->set_visible( $attribute_data['visible'] ?? true );

			$product_attributes[] = $attribute;
		}

		$product->set_attributes( $product_attributes );
	}

	/**
	 * Create product variations.
	 *
	 * @param int   $parent_id  Parent product ID.
	 * @param array $variations Variations data.
	 */
	private function create_product_variations( int $parent_id, array $variations ): void {
		foreach ( $variations as $variation_data ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $parent_id );

			if ( ! empty( $variation_data['attributes'] ) ) {
				$variation->set_attributes( $variation_data['attributes'] );
			}

			if ( ! empty( $variation_data['sku'] ) ) {
				$variation->set_sku( $variation_data['sku'] );
			}

			if ( ! empty( $variation_data['price'] ) ) {
				$variation->set_regular_price( $variation_data['price'] );
				$variation->set_price( $variation_data['price'] );
			}

			if ( ! empty( $variation_data['sale_price'] ) ) {
				$variation->set_sale_price( $variation_data['sale_price'] );
				$variation->set_price( $variation_data['sale_price'] );
			}

			if ( isset( $variation_data['manage_stock'] ) ) {
				$variation->set_manage_stock( $variation_data['manage_stock'] );
			}

			if ( ! empty( $variation_data['stock_quantity'] ) ) {
				$variation->set_stock_quantity( (int) $variation_data['stock_quantity'] );
			}

			if ( ! empty( $variation_data['weight'] ) ) {
				$variation->set_weight( $variation_data['weight'] );
			}

			$variation->save();
		}

		WC_Product_Variable::sync( $parent_id );
	}

	/**
	 * Handle post-save operations like categories, tags, and images.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $product_data Product data.
	 */
	private function handle_post_save_operations( int $product_id, array $product_data ): void {
		if ( ! empty( $product_data['categories'] ) && $this->import_options['create_categories'] ) {
			$this->assign_product_categories( $product_id, $product_data['categories'] );
		}

		if ( ! empty( $product_data['tags'] ) && $this->import_options['create_tags'] ) {
			$this->assign_product_tags( $product_id, $product_data['tags'] );
		}

		if ( ! empty( $product_data['images'] ) && $this->import_options['import_images'] ) {
			$this->assign_product_images( $product_id, $product_data['images'] );
		}

		if ( ! empty( $product_data['original_product_id'] ) ) {
			update_post_meta( $product_id, '_original_product_id', $product_data['original_product_id'] );
		}

		update_post_meta( $product_id, '_migration_data', $this->migration_data );
	}

	/**
	 * Assign categories to product.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $categories Categories data.
	 */
	private function assign_product_categories( int $product_id, array $categories ): void {
		$category_ids = array();

		foreach ( $categories as $category ) {
			if ( empty( $category['name'] ) ) {
				continue;
			}

			$term = get_term_by( 'name', $category['name'], 'product_cat' );

			if ( ! $term ) {
				// Create new category.
				$result = wp_insert_term(
					$category['name'],
					'product_cat',
					array(
						'slug' => $category['slug'] ?? sanitize_title( $category['name'] ),
					)
				);

				if ( ! is_wp_error( $result ) ) {
					$category_ids[] = $result['term_id'];
				}
			} else {
				$category_ids[] = $term->term_id;
			}
		}

		if ( ! empty( $category_ids ) ) {
			wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
		}
	}

	/**
	 * Assign tags to product.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $tags       Tags data.
	 */
	private function assign_product_tags( int $product_id, array $tags ): void {
		$tag_names = array();

		foreach ( $tags as $tag ) {
			if ( ! empty( $tag['name'] ) ) {
				$tag_names[] = $tag['name'];
			}
		}

		if ( ! empty( $tag_names ) ) {
			wp_set_object_terms( $product_id, $tag_names, 'product_tag' );
		}
	}

	/**
	 * Assign images to product.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $images     Images data.
	 */
	private function assign_product_images( int $product_id, array $images ): void {
		$gallery_ids     = array();
		$processed_count = 0;

		foreach ( $images as $index => $image ) {
			if ( $processed_count >= $this->import_options['max_images_per_product'] ) {
				break;
			}

			if ( empty( $image['src'] ) ) {
				continue;
			}

			$original_id   = $image['original_id'] ?? null;
			$is_featured   = $image['is_featured'] ?? ( 0 === $index );
			$attachment_id = $this->import_image_with_mapping(
				$image['src'],
				$image['alt'] ?? '',
				$original_id,
				$product_id
			);

			if ( $attachment_id ) {
				if ( $is_featured ) {
					set_post_thumbnail( $product_id, $attachment_id );
				} else {
					$gallery_ids[] = $attachment_id;
				}

				++$processed_count;
				++$this->import_stats['images_processed'];
			}
		}

		// Set product gallery.
		if ( ! empty( $gallery_ids ) ) {
			update_post_meta( $product_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
		}
	}

	/**
	 * Import image from URL with mapping optimization.
	 *
	 * @param string      $image_url   Image URL.
	 * @param string      $alt_text    Alt text for the image.
	 * @param string|null $original_id Original platform image ID.
	 * @param int         $product_id  Product ID for sideloading.
	 * @return int|null Attachment ID or null on failure.
	 */
	private function import_image_with_mapping( string $image_url, string $alt_text = '', ?string $original_id = null, int $product_id = 0 ): ?int {
		if ( $original_id && isset( $this->migration_data['images_mapping'][ $original_id ] ) ) {
			$attachment_id = $this->migration_data['images_mapping'][ $original_id ];
			if ( wp_attachment_is_image( $attachment_id ) ) {
				return $attachment_id;
			} else {
				unset( $this->migration_data['images_mapping'][ $original_id ] );
			}
		}

		$start_time    = microtime( true );
		$attachment_id = $this->import_image( $image_url, $alt_text, $product_id );
		$duration      = microtime( true ) - $start_time;

		if ( $attachment_id && $original_id ) {
			$this->migration_data['images_mapping'][ $original_id ] = $attachment_id;
		}

		if ( $attachment_id ) {
			$message = sprintf( 'Image uploaded successfully in %.2fs: %s -> %d', $duration, $image_url, $attachment_id );

			if ( $this->import_options['verbose'] ?? false ) {
				\WP_CLI::log( $message );
			}

			wc_get_logger()->info( $message, array( 'source' => 'wc-migrator-images' ) );
		} else {
			$message = sprintf( 'Image upload failed in %.2fs: %s', $duration, $image_url );

			if ( $this->import_options['verbose'] ?? false ) {
				\WP_CLI::warning( $message );
			}

			wc_get_logger()->error( $message, array( 'source' => 'wc-migrator-images' ) );
		}

		return $attachment_id;
	}

	/**
	 * Import image from URL.
	 *
	 * @param string $image_url Image URL.
	 * @param string $alt_text  Alt text for the image.
	 * @return int|null Attachment ID or null on failure.
	 */
	private function import_image( string $image_url, string $alt_text = '' ): ?int {
		if ( $this->import_options['dry_run'] ) {
			return null;
		}

		if ( ! $this->import_options['skip_duplicate_images'] ) {
			$existing_attachment = $this->get_attachment_by_url( $image_url );
			if ( $existing_attachment ) {
				return $existing_attachment;
			}
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		add_filter( 'http_request_timeout', array( $this, 'set_image_download_timeout' ) );
		add_filter( 'http_request_args', array( $this, 'optimize_http_request_args' ) );
		try {
			$attachment_id = media_sideload_image( $image_url, 0, null, 'id' );

			if ( is_wp_error( $attachment_id ) ) {
				$message = sprintf( 'Image import failed for URL %s: %s', $image_url, $attachment_id->get_error_message() );

				if ( $this->import_options['verbose'] ?? false ) {
					\WP_CLI::warning( $message );
				}
				wc_get_logger()->error( $message, array( 'source' => 'wc-migrator-images' ) );
				return null;
			}

			if ( $alt_text ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
			}

			return $attachment_id;
		} finally {
			remove_filter( 'http_request_timeout', array( $this, 'set_image_download_timeout' ) );
			remove_filter( 'http_request_args', array( $this, 'optimize_http_request_args' ) );
		}
	}

	/**
	 * Set HTTP timeout for image downloads.
	 *
	 * @return int Modified timeout.
	 */
	public function set_image_download_timeout(): int {
		return $this->import_options['image_timeout'];
	}

	/**
	 * Optimize HTTP request arguments for faster image downloads.
	 *
	 * @param array $args HTTP request arguments.
	 * @return array Optimized arguments.
	 */
	public function optimize_http_request_args( array $args ): array {
		$args['redirection'] = 3;
		$args['timeout']     = $this->import_options['image_timeout'] ?? 30;

		return $args;
	}

	/**
	 * Get existing attachment by URL.
	 *
	 * @param string $image_url Image URL.
	 * @return int|null Attachment ID or null if not found.
	 */
	private function get_attachment_by_url( string $image_url ): ?int {
		global $wpdb;

		$basename      = wp_basename( $image_url );
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
				'%' . $wpdb->esc_like( $basename )
			)
		);
		return $attachment_id ? (int) $attachment_id : null;
	}

	/**
	 * Create success result array.
	 *
	 * @param string $action     Action performed (created, updated, skipped).
	 * @param int    $product_id Product ID.
	 * @param string $message    Success message.
	 * @return array Success result.
	 */
	private function create_success_result( string $action, int $product_id, string $message ): array {
		return array(
			'status'     => 'success',
			'action'     => $action,
			'product_id' => $product_id,
			'message'    => $message,
		);
	}

	/**
	 * Create error result array.
	 *
	 * @param string $error_code   Error code.
	 * @param string $message      Error message.
	 * @param array  $product_data Product data that failed.
	 * @return array Error result.
	 */
	private function create_error_result( string $error_code, string $message, array $product_data ): array {
		return array(
			'status'       => 'error',
			'error_code'   => $error_code,
			'message'      => $message,
			'product_data' => $product_data,
		);
	}
}
