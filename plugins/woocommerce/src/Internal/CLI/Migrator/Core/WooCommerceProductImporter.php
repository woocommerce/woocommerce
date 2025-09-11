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
		$start_time   = microtime( true );
		$product_name = $product_data['name'] ?? 'Unknown Product';

		try {
			wc_get_logger()->info( "Starting import for product: {$product_name}", array( 'source' => 'wc-migrator' ) );

			$validation_result = $this->validate_product_data( $product_data );
			if ( ! $validation_result['valid'] ) {
				wc_get_logger()->error( "Validation failed for product: {$product_name} - " . $validation_result['message'], array( 'source' => 'wc-migrator' ) );
				return $this->create_error_result( 'validation_failed', $validation_result['message'], $product_data );
			}

			$existing_product_id = $this->find_existing_product( $product_data, $source_data );

			if ( $existing_product_id && $this->import_options['skip_existing'] ) {
				++$this->import_stats['products_skipped'];
				return $this->create_success_result( 'skipped', $existing_product_id, 'Product already exists and skip_existing is enabled' );
			}

			$product_type = $this->determine_product_type( $product_data );
			$product      = $this->get_or_create_product_object( $existing_product_id, $product_type );

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

			$this->set_product_taxonomies( $product, $product_data );

			$this->handle_product_images( $product, $product_data['images'] ?? array() );

			wc_get_logger()->debug( "Processing {$product_type} product: {$product_name}", array( 'source' => 'wc-migrator' ) );

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

			$duration = microtime( true ) - $start_time;
			$action   = $existing_product_id ? 'updated' : 'created';

			wc_get_logger()->info(
				"Successfully {$action} product: {$product_name} (ID: {$product_id}) in {$duration}s",
				array( 'source' => 'wc-migrator' )
			);

			return $this->create_success_result( $action, $product_id, "Product {$action} successfully in {$duration}s" );

		} catch ( Exception $e ) {
			++$this->import_stats['errors_encountered'];
			$duration = microtime( true ) - $start_time;

			wc_get_logger()->error(
				"Exception importing product: {$product_name} after {$duration}s - " . $e->getMessage(),
				array(
					'source'    => 'wc-migrator',
					'exception' => $e,
				)
			);

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
			'skip_existing'           => false,
			'update_existing'         => true,
			'import_images'           => true,
			'image_timeout'           => self::DEFAULT_IMAGE_TIMEOUT,
			'max_images_per_product'  => self::MAX_IMAGES_PER_PRODUCT,
			'skip_duplicate_images'   => false,
			'create_categories'       => true,
			'create_tags'             => true,
			'handle_variations'       => true,
			'assign_default_category' => false,
			'dry_run'                 => false,
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
					'meta_key'    => '_original_product_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'  => $product_data['original_product_id'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
		if ( isset( $product_data['is_variable'] ) ) {
			return $product_data['is_variable'] ? 'variable' : 'simple';
		}

		if ( ! empty( $product_data['variations'] ) && count( $product_data['variations'] ) >= 1 ) {
			return 'variable';
		}

		if ( ! empty( $product_data['attributes'] ) ) {
			foreach ( $product_data['attributes'] as $attribute ) {
				if ( ! empty( $attribute['is_variation'] ) || ! empty( $attribute['variation'] ) ) {
					return 'variable';
				}
			}
		}

		return 'simple';
	}

	/**
	 * Get or create product object with proper type conversion handling.
	 *
	 * @param int|null $existing_product_id Existing product ID if updating.
	 * @param string   $required_type Required product type.
	 * @return WC_Product|null Product object or null on failure.
	 */
	private function get_or_create_product_object( ?int $existing_product_id, string $required_type ): ?WC_Product {
		if ( ! $existing_product_id ) {
			return $this->create_product_object( $required_type );
		}

		$existing_product = wc_get_product( $existing_product_id );
		if ( ! $existing_product ) {
			return $this->create_product_object( $required_type );
		}

		$current_type = $existing_product->get_type();
		if ( $current_type === $required_type ) {
			return $existing_product;
		}

		wc_get_logger()->info(
			"Converting product ID {$existing_product_id} from {$current_type} to {$required_type}",
			array( 'source' => 'wc-migrator' )
		);

		switch ( $required_type ) {
			case 'variable':
				return new WC_Product_Variable( $existing_product_id );
			case 'simple':
			default:
				return new WC_Product_Simple( $existing_product_id );
		}
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

		if ( ! empty( $product_data['date_created_gmt'] ) ) {
			$product->set_date_created( $product_data['date_created_gmt'] );
		}

		if ( ! empty( $product_data['weight'] ) ) {
			$product->set_weight( $product_data['weight'] );
		}

		if ( ! empty( $product_data['metafields'] ) ) {
			foreach ( $product_data['metafields'] as $key => $value ) {
				if ( ! empty( $key ) ) {
					$product->add_meta_data( $key, $value, true );
				}
			}
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
		if ( ! empty( $product_data['regular_price'] ) ) {
			$product->set_regular_price( $product_data['regular_price'] );
			$product->set_price( $product_data['regular_price'] );
		}

		if ( ! empty( $product_data['sale_price'] ) ) {
			$product->set_sale_price( $product_data['sale_price'] );
			$product->set_price( $product_data['sale_price'] );
		}

		if ( ! empty( $product_data['sku'] ) ) {
			add_filter( 'wc_product_has_unique_sku', '__return_false', 999 );
			$product->set_sku( $product_data['sku'] );
			remove_filter( 'wc_product_has_unique_sku', '__return_false', 999 );
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
		$product->set_sku( '' );
		$product->set_regular_price( '' );
		$product->set_sale_price( '' );
		$product->set_manage_stock( false );
		$product->set_weight( '' );
		$product->set_stock_quantity( null );

		if ( ! empty( $product_data['attributes'] ) ) {
			$this->setup_attributes( $product, $product_data['attributes'] );
		}

		$product_id = $product->save();

		if ( ! empty( $product_data['variations'] ) && $this->import_options['handle_variations'] ) {
			$this->sync_variations( $product, $product_data['variations'] );
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
			$attribute->set_variation( $attribute_data['is_variation'] ?? $attribute_data['variation'] ?? false );
			$attribute->set_visible( $attribute_data['is_visible'] ?? $attribute_data['visible'] ?? true );

			$product_attributes[] = $attribute;
		}

		$product->set_attributes( $product_attributes );
	}

	/**
	 * Sets up product attributes for variable products with global taxonomy creation.
	 *
	 * @param WC_Product_Variable $product The variable product object.
	 * @param array               $attributes_data Standardized attribute data from mapper.
	 */
	private function setup_attributes( WC_Product_Variable $product, array $attributes_data ): void {
		$woo_attributes = array();

		foreach ( $attributes_data as $attribute_info ) {
			$attr_name    = $attribute_info['name'] ?? null;
			$attr_options = $attribute_info['options'] ?? array();
			if ( empty( $attr_name ) || empty( $attr_options ) ) {
				continue;
			}

			$taxonomy_slug = sanitize_title( $attr_name );
			$taxonomy_name = 'pa_' . $taxonomy_slug;
			$attribute_id  = 0;

			if ( ! taxonomy_exists( $taxonomy_name ) ) {
				$attribute_id = wc_create_attribute(
					array(
						'name'         => $attr_name,
						'slug'         => $taxonomy_slug,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					)
				);
				if ( is_wp_error( $attribute_id ) ) {
					wc_get_logger()->warning( "Failed to create attribute '{$attr_name}': " . $attribute_id->get_error_message(), array( 'source' => 'wc-migrator' ) );
					continue;
				}
			} else {
				$attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy_name );
			}

			$term_ids   = array();
			$term_slugs = array();
			foreach ( $attr_options as $value ) {
				$term_slug = sanitize_title( $value );
				$term      = get_term_by( 'slug', $term_slug, $taxonomy_name );
				if ( ! $term ) {
					$term_result = wp_insert_term( $value, $taxonomy_name, array( 'slug' => $term_slug ) );
					if ( is_wp_error( $term_result ) ) {
						wc_get_logger()->warning( "Failed to insert term '{$value}' (slug: {$term_slug}) into {$taxonomy_name}: " . $term_result->get_error_message(), array( 'source' => 'wc-migrator' ) );
						continue;
					}
					$term_ids[]   = $term_result['term_id'];
					$term_slugs[] = $term_slug;
				} else {
					$term_ids[]   = $term->term_id;
					$term_slugs[] = $term->slug;
				}
			}

			$woo_attribute = new \WC_Product_Attribute();
			$woo_attribute->set_name( $taxonomy_name );
			$woo_attribute->set_id( $attribute_id );
			$woo_attribute->set_options( $term_slugs );
			$woo_attribute->set_position( $attribute_info['position'] ?? 0 );
			$woo_attribute->set_visible( $attribute_info['is_visible'] ?? true );
			$woo_attribute->set_variation( $attribute_info['is_variation'] ?? true );
			$woo_attributes[] = $woo_attribute;
		}

		$product->set_attributes( $woo_attributes );
	}

	/**
	 * Creates or updates product variations with proper mapping and lookup.
	 *
	 * @param WC_Product_Variable $product The parent variable product.
	 * @param array               $variations_data Standardized variation data from mapper.
	 */
	private function sync_variations( WC_Product_Variable $product, array $variations_data ): void {
		$parent_product_id       = $product->get_id();
		$parent_original_id      = $product->get_meta( '_original_product_id' );
		$processed_variation_ids = array();

		$variation_count = count( $variations_data );
		wc_get_logger()->debug( "Syncing {$variation_count} variations for product ID {$parent_product_id}", array( 'source' => 'wc-migrator' ) );

		$attribute_taxonomy_map = array();
		$product_attributes     = $product->get_attributes();

		foreach ( $product_attributes as $taxonomy => $attribute_obj ) {
			if ( $attribute_obj->get_variation() ) {
				$attribute_label = wc_attribute_label( $taxonomy, $product );
				// Store mapping with both original case and lowercase for case-insensitive lookup.
				$attribute_taxonomy_map[ $attribute_label ]               = $taxonomy;
				$attribute_taxonomy_map[ strtolower( $attribute_label ) ] = $taxonomy;
			}
		}

		foreach ( $variations_data as $var_data ) {
			$original_variant_id = $var_data['original_id'] ?? null;
			if ( ! $original_variant_id ) {
				wc_get_logger()->warning( 'Skipping variation: Missing original ID.', array( 'source' => 'wc-migrator' ) );
				continue;
			}

			$variation_id = null;
			$variation    = null;

			if ( isset( $this->migration_data['variations_mapping'][ $original_variant_id ] ) ) {
				$_variation_id = $this->migration_data['variations_mapping'][ $original_variant_id ];
				$_variation    = wc_get_product( $_variation_id );
				if ( $_variation instanceof WC_Product_Variation && $_variation->get_parent_id() === $parent_product_id ) {
					$variation    = $_variation;
					$variation_id = $_variation_id;
				} else {
					unset( $this->migration_data['variations_mapping'][ $original_variant_id ] );
				}
			}

			if ( ! $variation ) {
				$query_args = array(
					'post_parent' => $parent_product_id,
					'post_type'   => 'product_variation',
					'numberposts' => 1,
					'post_status' => 'any',
					'meta_key'    => '_original_variant_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'  => $original_variant_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'fields'      => 'ids',
				);

				$found_ids = get_posts( $query_args );
				if ( ! empty( $found_ids ) ) {
					$variation_id = $found_ids[0];
					$variation    = wc_get_product( $variation_id );
					if ( ! ( $variation instanceof WC_Product_Variation ) ) {
						wc_get_logger()->warning( "Found post ID {$variation_id} for original variant {$original_variant_id}, but it's not a WC_Product_Variation.", array( 'source' => 'wc-migrator' ) );
						$variation    = null;
						$variation_id = null;
					}
				}
			}

			if ( ! $variation ) {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $parent_product_id );
			}

			$variation->set_status( 'publish' );
			$variation->set_menu_order( $var_data['menu_order'] ?? 0 );

			$variation->set_regular_price( $var_data['regular_price'] ?? '' );
			$variation->set_sale_price( $var_data['sale_price'] ?? '' );

			if ( ! empty( $var_data['sku'] ) ) {
				add_filter( 'wc_product_has_unique_sku', '__return_false', 999 );
				$variation->set_sku( $var_data['sku'] );
				remove_filter( 'wc_product_has_unique_sku', '__return_false', 999 );
			}

			$variation->set_manage_stock( $var_data['manage_stock'] ?? false );
			$variation->set_stock_quantity( $var_data['stock_quantity'] ?? null );
			$variation->set_stock_status( $var_data['stock_status'] ?? 'instock' );

			$variation->set_weight( $var_data['weight'] ?? '' );

			$image_original_id = $var_data['image_original_id'] ?? null;
			if ( $image_original_id && isset( $this->migration_data['images_mapping'][ $image_original_id ] ) ) {
				$variation->set_image_id( $this->migration_data['images_mapping'][ $image_original_id ] );
			} else {
				$variation->set_image_id( '' );
			}

			$wc_variation_attributes = array();
			if ( ! empty( $var_data['attributes'] ) && is_array( $var_data['attributes'] ) ) {
				foreach ( $var_data['attributes'] as $attr_name => $attr_value ) {
					if ( isset( $attribute_taxonomy_map[ $attr_name ] ) ) {
						$taxonomy                             = $attribute_taxonomy_map[ $attr_name ];
						$term_slug                            = sanitize_title( $attr_value );
						$wc_variation_attributes[ $taxonomy ] = $term_slug;
					} else {
						wc_get_logger()->warning( "Attribute taxonomy mapping not found for option '{$attr_name}' while processing variation {$original_variant_id}.", array( 'source' => 'wc-migrator' ) );
					}
				}
			}
			$variation->set_attributes( $wc_variation_attributes );

			$variation->update_meta_data( '_original_variant_id', $original_variant_id );
			if ( $parent_original_id ) {
				$variation->update_meta_data( '_original_product_id', $parent_original_id );
			}

			$saved_variation_id = $variation->save();
			if ( $saved_variation_id ) {
				$processed_variation_ids[] = $saved_variation_id;
				$this->migration_data['variations_mapping'][ $original_variant_id ] = $saved_variation_id;
			} else {
				wc_get_logger()->error( "Failed to save variation for original variant {$original_variant_id}", array( 'source' => 'wc-migrator' ) );
			}
		}

		WC_Product_Variable::sync( $parent_product_id );

		$processed_count = count( $processed_variation_ids );
		wc_get_logger()->debug( "Successfully synced {$processed_count}/{$variation_count} variations for product ID {$parent_product_id}", array( 'source' => 'wc-migrator' ) );
	}

	/**
	 * Create product variations (legacy method - keeping for backward compatibility).
	 *
	 * @param int   $parent_id  Parent product ID.
	 * @param array $variations Variations data.
	 */
	private function create_product_variations( int $parent_id, array $variations ): void {
		$product = wc_get_product( $parent_id );
		if ( $product instanceof WC_Product_Variable ) {
			$this->sync_variations( $product, $variations );
		}
	}

	/**
	 * Handle post-save operations like metadata and migration tracking.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $product_data Product data.
	 */
	private function handle_post_save_operations( int $product_id, array $product_data ): void {

		if ( ! empty( $product_data['original_product_id'] ) ) {
			update_post_meta( $product_id, '_original_product_id', $product_data['original_product_id'] );
		}

		if ( ! empty( $product_data['original_url'] ) ) {
			update_post_meta( $product_id, '_original_url', $product_data['original_url'] );
		}

		update_post_meta( $product_id, '_migration_data', $this->migration_data );

		if ( ! empty( $product_data['metafields'] ) ) {
			$this->update_seo_meta( $product_id, $product_data['metafields'], $product_data );
		}
	}

	/**
	 * Set product taxonomies (categories, tags, brand) before product save.
	 *
	 * @param WC_Product $product The product object.
	 * @param array      $product_data Standardized data containing taxonomies.
	 */
	private function set_product_taxonomies( WC_Product $product, array $product_data ): void {
		$product_id = $product->get_id();
		if ( ! $product_id ) {
			$product_id = $product->save();
			if ( ! $product_id ) {
				wc_get_logger()->warning( 'Could not save product to set taxonomies.', array( 'source' => 'wc-migrator' ) );
				return;
			}
		}

		$taxonomies_to_set = array();

		if ( isset( $product_data['categories'] ) && is_array( $product_data['categories'] ) && $this->import_options['create_categories'] ) {
			$term_ids = $this->get_or_create_terms( $product_data['categories'], 'product_cat' );
			if ( ! empty( $term_ids ) ) {
				$taxonomies_to_set['product_cat'] = $term_ids;
			} elseif ( $this->import_options['assign_default_category'] ) {
				$default_cat_id = get_option( 'default_product_cat' );
				if ( $default_cat_id ) {
					$taxonomies_to_set['product_cat'] = array( $default_cat_id );
					wc_get_logger()->info( "Assigned default category (ID: {$default_cat_id}) to product with no categories", array( 'source' => 'wc-migrator' ) );
				}
			} else {
				wc_get_logger()->debug( 'Product has no categories and assign_default_category is disabled', array( 'source' => 'wc-migrator' ) );
			}
		}

		if ( isset( $product_data['tags'] ) && is_array( $product_data['tags'] ) && $this->import_options['create_tags'] ) {
			$term_ids = $this->get_or_create_terms( $product_data['tags'], 'product_tag' );
			if ( ! empty( $term_ids ) ) {
				$taxonomies_to_set['product_tag'] = $term_ids;
			}
		}

		if ( ! empty( $product_data['brand']['name'] ) && taxonomy_exists( 'product_brand' ) ) {
			$brand_data = array( $product_data['brand'] );
			$term_ids   = $this->get_or_create_terms( $brand_data, 'product_brand' );
			if ( ! empty( $term_ids ) ) {
				$taxonomies_to_set['product_brand'] = $term_ids;
			}
		}

		foreach ( $taxonomies_to_set as $taxonomy => $ids ) {
			wp_set_object_terms( $product_id, $ids, $taxonomy, false );
		}
	}

	/**
	 * Helper to get or create term IDs for a given taxonomy.
	 *
	 * @param array  $terms_data Array of ['name' => ..., 'slug' => ...].
	 * @param string $taxonomy Taxonomy slug.
	 * @return array Array of term IDs.
	 */
	private function get_or_create_terms( array $terms_data, string $taxonomy ): array {
		$term_ids = array();
		foreach ( $terms_data as $term_info ) {
			$term_name = $term_info['name'] ?? null;
			$term_slug = $term_info['slug'] ?? sanitize_title( $term_name );

			if ( empty( $term_name ) || empty( $term_slug ) ) {
				continue;
			}

			$term = get_term_by( 'slug', $term_slug, $taxonomy );

			if ( ! $term ) {
				$term_result = wp_insert_term( $term_name, $taxonomy, array( 'slug' => $term_slug ) );
				if ( is_wp_error( $term_result ) ) {
					wc_get_logger()->warning( "Failed to insert term '{$term_name}' (slug: {$term_slug}) into {$taxonomy}: " . $term_result->get_error_message(), array( 'source' => 'wc-migrator' ) );
					continue;
				}
				$term_ids[] = $term_result['term_id'];
			} else {
				$term_ids[] = $term->term_id;
			}
		}
		return array_unique( $term_ids );
	}

	/**
	 * Handle product images using product object methods.
	 *
	 * @param WC_Product $product The product object.
	 * @param array      $images_data Standardized image data from mapper.
	 */
	private function handle_product_images( WC_Product $product, array $images_data ): void {
		if ( empty( $images_data ) ) {
			return;
		}

		$gallery_ids     = array();
		$featured_id     = null;
		$product_id      = $product->get_id();
		$processed_count = 0;

		foreach ( $images_data as $index => $image ) {
			if ( $processed_count >= $this->import_options['max_images_per_product'] ) {
				break;
			}

			$original_id = $image['original_id'] ?? null;
			$image_url   = $image['src'] ?? null;
			$image_alt   = $image['alt'] ?? '';
			$is_featured = $image['is_featured'] ?? ( 0 === $index );

			if ( empty( $original_id ) || empty( $image_url ) ) {
				wc_get_logger()->warning( 'Skipping image: Missing original ID or URL.', array( 'source' => 'wc-migrator' ) );
				continue;
			}

			if ( isset( $this->migration_data['images_mapping'][ $original_id ] ) && wp_attachment_is_image( $this->migration_data['images_mapping'][ $original_id ] ) ) {
				$attachment_id = $this->migration_data['images_mapping'][ $original_id ];
			} else {
				if ( ! $product_id ) {
					$product_id = $product->save();
					if ( ! $product_id ) {
						wc_get_logger()->warning( "Skipping image upload {$original_id}: Could not get product ID before sideloading.", array( 'source' => 'wc-migrator' ) );
						continue;
					}
				}

				$start_time    = microtime( true );
				$image_desc    = $image_alt ? $image_alt : $product->get_name();
				$attachment_id = $this->import_image( $image_url, $image_alt, $product_id );
				$duration      = microtime( true ) - $start_time;

				if ( is_wp_error( $attachment_id ) ) {
					wc_get_logger()->error( "Error uploading {$image_url}: " . $attachment_id->get_error_message() . " (Duration: {$duration}s)", array( 'source' => 'wc-migrator' ) );
					continue;
				}

				if ( ! $attachment_id ) {
					wc_get_logger()->warning( "Image upload failed for {$image_url} (Duration: {$duration}s)", array( 'source' => 'wc-migrator' ) );
					continue;
				}

				$this->migration_data['images_mapping'][ $original_id ] = $attachment_id;

				if ( $image_alt ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_alt );
				}
			}

			if ( $is_featured ) {
				$featured_id = $attachment_id;
			} else {
				$gallery_ids[] = $attachment_id;
			}

			++$processed_count;
			++$this->import_stats['images_processed'];
		}

		if ( $featured_id ) {
			$product->set_image_id( $featured_id );
		}
		if ( ! empty( $gallery_ids ) ) {
			$product->set_gallery_image_ids( array_unique( $gallery_ids ) );
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
	 * @param int    $product_id Product ID for sideloading.
	 * @return int|null Attachment ID or null on failure.
	 */
	private function import_image( string $image_url, string $alt_text = '', int $product_id = 0 ): ?int {
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
		add_filter( 'image_sideload_extensions', array( $this, 'add_avif_support_to_sideload' ) );
		try {
			$attachment_id = media_sideload_image( $image_url, $product_id, null, 'id' );

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
			remove_filter( 'image_sideload_extensions', array( $this, 'add_avif_support_to_sideload' ) );
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
	 * Add AVIF support to image sideload extensions.
	 *
	 * @param array $allowed_extensions Array of allowed file extensions.
	 * @return array Modified array with AVIF support.
	 */
	public function add_avif_support_to_sideload( array $allowed_extensions ): array {
		if ( ! in_array( 'avif', $allowed_extensions, true ) ) {
			$allowed_extensions[] = 'avif';
		}
		return $allowed_extensions;
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
	 * Updates SEO meta fields if Yoast SEO is active.
	 *
	 * @param int   $product_id  The product ID.
	 * @param array $metafields  Key-value array of metafields from standardized data.
	 * @param array $product_data Full product data for fallbacks.
	 */
	private function update_seo_meta( int $product_id, array $metafields, array $product_data ): void {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$seo_title       = $metafields['global_title_tag'] ?? null;
		$seo_description = $metafields['global_description_tag'] ?? null;

		$final_seo_title       = $seo_title ? $seo_title : ( $product_data['name'] ?? '' );
		$fallback_desc         = $product_data['description'] ? $product_data['description'] : ( $product_data['short_description'] ?? '' );
		$final_seo_description = $seo_description ? $seo_description : wp_strip_all_tags( $fallback_desc );

		$current_title = get_post_meta( $product_id, '_yoast_wpseo_title', true );
		if ( $current_title !== $final_seo_title && ! empty( $final_seo_title ) ) {
			update_post_meta( $product_id, '_yoast_wpseo_title', $final_seo_title );
		}

		$current_desc = get_post_meta( $product_id, '_yoast_wpseo_metadesc', true );
		if ( $current_desc !== $final_seo_description && ! empty( $final_seo_description ) ) {
			$truncated_desc = mb_substr( $final_seo_description, 0, 160 );
			update_post_meta( $product_id, '_yoast_wpseo_metadesc', $truncated_desc );
		}
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
