<?php
/**
 * Shopify Mapper
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;

defined( 'ABSPATH' ) || exit;

/**
 * ShopifyMapper class.
 *
 * This class is responsible for transforming raw Shopify product data
 * into a standardized format suitable for the WooCommerce Importer.
 * Maps comprehensive product data including variants, images, taxonomies,
 * and metadata from Shopify's GraphQL API response format.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
class ShopifyMapper implements PlatformMapperInterface {

	/**
	 * Shopify weight unit to standard unit mapping.
	 *
	 * @var array
	 */
	private const WEIGHT_UNIT_MAP = array(
		'GRAMS'     => 'g',
		'KILOGRAMS' => 'kg',
		'POUNDS'    => 'lb',
		'OUNCES'    => 'oz',
	);

	/**
	 * Weight conversion factors between units.
	 * Structure: [from_unit][to_unit] = factor
	 *
	 * @var array
	 */
	private const WEIGHT_CONVERSION_FACTORS = array(
		'kg' => array(
			'kg' => 1,
			'g'  => 1000,
			'lb' => 2.20462,
			'oz' => 35.274,
		),
		'g'  => array(
			'kg' => 0.001,
			'g'  => 1,
			'lb' => 0.00220462,
			'oz' => 0.035274,
		),
		'lb' => array(
			'kg' => 0.453592,
			'g'  => 453.592,
			'lb' => 1,
			'oz' => 16,
		),
		'oz' => array(
			'kg' => 0.0283495,
			'g'  => 28.3495,
			'lb' => 0.0625,
			'oz' => 1,
		),
	);

	/**
	 * Fields to process during mapping.
	 *
	 * @var array
	 */
	private $fields_to_process = array();

	/**
	 * Constructor.
	 *
	 * @param array $args Optional arguments including 'fields' array for selective processing.
	 */
	public function __construct( array $args = array() ) {
		$this->fields_to_process = $args['fields'] ?? $this->get_default_product_fields();
	}

	/**
	 * Maps raw Shopify product data to a standardized array format.
	 *
	 * @param object $shopify_product The raw Shopify product node from GraphQL.
	 * @return array Standardized data array for WooCommerce_Product_Importer.
	 */
	public function map_product_data( object $shopify_product ): array {
		$is_variable = $this->is_variable_product( $shopify_product );

		$wc_data = $this->map_basic_product_fields( $shopify_product, $is_variable );

		// Map simple product data (for non-variable products).
		if ( ! $is_variable ) {
			$simple_data = $this->map_simple_product_data( $shopify_product );
			$wc_data     = array_merge( $wc_data, $simple_data );
		}

		// Map product images.
		$wc_data['images'] = $this->map_product_images( $shopify_product );

		// Map metafields and SEO data.
		$wc_data['metafields'] = $this->map_metafields( $shopify_product );

		// Map variable product data (attributes and variations).
		$variable_data = $this->map_variable_product_data( $shopify_product, $is_variable );
		$wc_data       = array_merge( $wc_data, $variable_data );

		return $wc_data;
	}

	/**
	 * Checks if a product is a variable product.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return bool True if the product is a variable product, false otherwise.
	 */
	private function is_variable_product( object $shopify_product ): bool {
		return isset( $shopify_product->variants->edges ) && count( $shopify_product->variants->edges ) > 1;
	}

	/**
	 * Converts the Shopify product status into WooCommerce product status.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return string The WooCommerce product status.
	 */
	private function get_woo_product_status( object $shopify_product ): string {
		$woo_product_status = 'draft';
		if ( 'ACTIVE' === $shopify_product->status ) {
			$woo_product_status = 'publish';
		}
		return $woo_product_status;
	}

	/**
	 * Maps enhanced publication status fields from Shopify.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Enhanced status data.
	 */
	private function map_enhanced_status( object $shopify_product ): array {
		$status_data = array();

		// Publication date.
		if ( property_exists( $shopify_product, 'publishedAt' ) && $shopify_product->publishedAt ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
			$status_data['date_published_gmt'] = $shopify_product->publishedAt; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		}

		// Available for sale flag.
		if ( property_exists( $shopify_product, 'availableForSale' ) ) {
			$status_data['available_for_sale'] = $shopify_product->availableForSale; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		}

		return $status_data;
	}

	/**
	 * Maps product classification fields from Shopify.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Product classification data.
	 */
	private function map_product_classification( object $shopify_product ): array {
		$classification = array();

		// Product type - check both camelCase and snake_case for compatibility.
		$product_type = null;
		if ( property_exists( $shopify_product, 'productType' ) && $shopify_product->productType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
			$product_type = $shopify_product->productType; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		} elseif ( property_exists( $shopify_product, 'product_type' ) && $shopify_product->product_type ) {
			$product_type = $shopify_product->product_type;
		}

		if ( $product_type ) {
			$classification['product_type'] = array(
				'name' => $product_type,
				'slug' => sanitize_title( $product_type ),
			);
		}

		// Standard category.
		if ( property_exists( $shopify_product, 'category' ) && is_object( $shopify_product->category ) ) {
			$classification['standard_category'] = array(
				'name' => $shopify_product->category->name ?? '',
				'slug' => sanitize_title( $shopify_product->category->name ?? '' ),
			);
		}

		// Gift card detection - check both camelCase and snake_case for compatibility.
		if ( property_exists( $shopify_product, 'isGiftCard' ) ) {
			$classification['is_gift_card'] = $shopify_product->isGiftCard; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		} elseif ( property_exists( $shopify_product, 'is_gift_card' ) ) {
			$classification['is_gift_card'] = $shopify_product->is_gift_card;
		}

		if ( property_exists( $shopify_product, 'requiresSellingPlan' ) ) {
			$classification['requires_subscription'] = $shopify_product->requiresSellingPlan; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		} elseif ( property_exists( $shopify_product, 'requires_selling_plan' ) ) {
			$classification['requires_subscription'] = $shopify_product->requires_selling_plan;
		}

		return $classification;
	}

	/**
	 * Maps SEO fields from Shopify product data.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array SEO metafields data.
	 */
	private function map_seo_fields( object $shopify_product ): array {
		$seo_data = array();

		if ( property_exists( $shopify_product, 'seo' ) && is_object( $shopify_product->seo ) ) {
			if ( ! empty( $shopify_product->seo->title ) ) {
				$seo_data['global_title_tag'] = $shopify_product->seo->title;
			}
			if ( ! empty( $shopify_product->seo->description ) ) {
				$seo_data['global_description_tag'] = $shopify_product->seo->description;
			}
		}

		return $seo_data;
	}

	/**
	 * Gets mapped WooCommerce product categories from Shopify collections.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Mapped category data.
	 */
	private function get_mapped_categories( object $shopify_product ): array {
		$categories = array();
		if ( ! property_exists( $shopify_product, 'collections' ) || empty( $shopify_product->collections->edges ) ) {
			return $categories;
		}

		foreach ( $shopify_product->collections->edges as $collection_edge ) {
			$collection_node = $collection_edge->node;
			$categories[]    = array(
				'name' => $collection_node->title,
				'slug' => $collection_node->handle,
			);
		}

		return $categories;
	}

	/**
	 * Gets mapped WooCommerce product tags from Shopify tags.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Mapped tag data.
	 */
	private function get_mapped_tags( object $shopify_product ): array {
		$tags = array();
		if ( empty( $shopify_product->tags ) ) {
			return $tags;
		}

		foreach ( $shopify_product->tags as $tag ) {
			$trimmed_tag = trim( $tag );
			if ( ! empty( $trimmed_tag ) ) {
				$tags[] = array(
					'name' => $trimmed_tag,
					'slug' => sanitize_title( $trimmed_tag ),
				);
			}
		}
		return $tags;
	}

	/**
	 * Converts weight based on Shopify weight unit to store's weight unit.
	 *
	 * @param float|null  $weight      The weight value from Shopify.
	 * @param string|null $weight_unit The weight unit from Shopify.
	 * @return float|null The converted weight, or null if input is invalid/zero.
	 */
	private function get_converted_weight( $weight, $weight_unit ): ?float {
		if ( null === $weight || null === $weight_unit || (float) $weight <= 0 ) {
			return null;
		}

		$shopify_unit_key = self::WEIGHT_UNIT_MAP[ $weight_unit ] ?? null;

		if ( ! $shopify_unit_key ) {
			return (float) $weight;
		}

		$store_weight_unit = get_option( 'woocommerce_weight_unit' );

		if ( 'lbs' === $store_weight_unit ) {
			$store_weight_unit = 'lb';
		}

		if ( $shopify_unit_key === $store_weight_unit ) {
			return (float) $weight;
		}

		// Use wc_get_weight for conversion if possible.
		if ( function_exists( 'wc_get_weight' ) ) {
			$converted = wc_get_weight( (float) $weight, $store_weight_unit, $shopify_unit_key );
			return is_numeric( $converted ) ? (float) $converted : null;
		}

		// Fallback manual conversion using class constants.
		if ( ! isset( self::WEIGHT_CONVERSION_FACTORS[ $shopify_unit_key ][ $store_weight_unit ] ) ) {
			return (float) $weight;
		}

		return (float) $weight * self::WEIGHT_CONVERSION_FACTORS[ $shopify_unit_key ][ $store_weight_unit ];
	}

	/**
	 * Basic sanitization for product description HTML.
	 *
	 * @param string $html Raw description HTML.
	 * @return string Sanitized HTML.
	 */
	private function sanitize_product_description( string $html ): string {
		return trim( $html );
	}

	/**
	 * Checks if a specific field should be processed based on constructor args.
	 *
	 * @param string $field_key The field key.
	 * @return bool True if the field should be processed.
	 */
	private function should_process( string $field_key ): bool {
		if ( empty( $this->fields_to_process ) ) {
			return true;
		}
		return in_array( $field_key, $this->fields_to_process, true );
	}

	/**
	 * Maps basic product fields from Shopify to WooCommerce format.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @param bool   $is_variable     Whether this is a variable product.
	 * @return array Basic product field mappings.
	 */
	private function map_basic_product_fields( object $shopify_product, bool $is_variable ): array {
		$basic_data = array();

		$basic_data['is_variable']         = $is_variable;
		$basic_data['original_product_id'] = basename( $shopify_product->id );

		// Basic Product Fields.
		$basic_data['name']              = $shopify_product->title;
		$basic_data['slug']              = $shopify_product->handle;
		$basic_data['description']       = $this->sanitize_product_description( $shopify_product->descriptionHtml ?? '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		$basic_data['short_description'] = $shopify_product->descriptionPlainSummary ?? ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		$basic_data['status']            = $this->get_woo_product_status( $shopify_product );
		$basic_data['date_created_gmt']  = $shopify_product->createdAt; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.

		// Enhanced date handling.
		if ( property_exists( $shopify_product, 'updatedAt' ) ) {
			$basic_data['date_modified_gmt'] = $shopify_product->updatedAt; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		}

		// Catalog Visibility & Original URL.
		$basic_data['catalog_visibility'] = 'visible';
		$basic_data['original_url']       = null;
		if ( property_exists( $shopify_product, 'onlineStoreUrl' ) ) {
			if ( null === $shopify_product->onlineStoreUrl ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				$basic_data['catalog_visibility'] = 'hidden';
			} else {
				$basic_data['original_url'] = $shopify_product->onlineStoreUrl; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
			}
		}

		$enhanced_status = $this->map_enhanced_status( $shopify_product );
		$basic_data      = array_merge( $basic_data, $enhanced_status );

		// Taxonomies.
		$basic_data['categories'] = $this->get_mapped_categories( $shopify_product );
		$basic_data['tags']       = $this->get_mapped_tags( $shopify_product );

		// Enhanced product classification.
		$classification = $this->map_product_classification( $shopify_product );
		$basic_data     = array_merge( $basic_data, $classification );

		// Brand (Vendor).
		$brand_name          = $shopify_product->vendor ?? null;
		$basic_data['brand'] = $brand_name ? array(
			'name' => $brand_name,
			'slug' => sanitize_title( $brand_name ),
		) : null;

		return $basic_data;
	}

	/**
	 * Maps simple product data (price, SKU, stock, weight) from Shopify variant.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Simple product data mappings.
	 */
	private function map_simple_product_data( object $shopify_product ): array {
		$simple_data = array();

		if ( ! empty( $shopify_product->variants->edges ) ) {
			$variant_node = $shopify_product->variants->edges[0]->node;

			if ( $this->should_process( 'price' ) ) {
				if ( $variant_node->compareAtPrice && $variant_node->compareAtPrice > $variant_node->price ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					$simple_data['sale_price']    = $variant_node->price;
					$simple_data['regular_price'] = $variant_node->compareAtPrice; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				} else {
					$simple_data['sale_price']    = null;
					$simple_data['regular_price'] = $variant_node->price;
				}
			}

			if ( $this->should_process( 'sku' ) ) {
				$simple_data['sku'] = $variant_node->sku;
			}

			if ( $this->should_process( 'stock' ) ) {
				$manage_stock                  = property_exists( $variant_node, 'inventoryItem' ) && $variant_node->inventoryItem->tracked; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				$simple_data['manage_stock']   = $manage_stock;
				$stock_quantity                = $variant_node->inventoryQuantity ?? 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				$allow_oversell                = $manage_stock && 'CONTINUE' === $variant_node->inventoryPolicy; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				$simple_data['stock_status']   = ( $stock_quantity > 0 || $allow_oversell ) ? 'instock' : 'outofstock';
				$simple_data['stock_quantity'] = $stock_quantity;
			}

			if ( $this->should_process( 'weight' ) ) {
				$weight_data = null;
				if ( property_exists( $variant_node, 'inventoryItem' ) && is_object( $variant_node->inventoryItem ) && // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					property_exists( $variant_node->inventoryItem, 'measurement' ) && is_object( $variant_node->inventoryItem->measurement ) && // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					property_exists( $variant_node->inventoryItem->measurement, 'weight' ) && is_object( $variant_node->inventoryItem->measurement->weight ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				) {
					$weight_data = $variant_node->inventoryItem->measurement->weight; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
				}
				$weight                = $weight_data ? $weight_data->value : null;
				$weight_unit           = $weight_data ? $weight_data->unit : null;
				$simple_data['weight'] = $this->get_converted_weight( $weight, $weight_unit );
			}

			$simple_data['original_variant_id'] = basename( $variant_node->id );

		} else {
			// Defaults for variable or product with no variants.
			$simple_data['sku']                 = null;
			$simple_data['regular_price']       = null;
			$simple_data['sale_price']          = null;
			$simple_data['stock_quantity']      = null;
			$simple_data['manage_stock']        = false;
			$simple_data['stock_status']        = 'instock';
			$simple_data['weight']              = null;
			$simple_data['original_variant_id'] = null;
		}

		return $simple_data;
	}

	/**
	 * Maps variable product data (attributes and variations) from Shopify.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @param bool   $is_variable     Whether this is a variable product.
	 * @return array Variable product data mappings.
	 */
	private function map_variable_product_data( object $shopify_product, bool $is_variable ): array {
		$variable_data = array();

		// Attributes (Variable Only).
		$variable_data['attributes'] = array();
		if ( $is_variable && property_exists( $shopify_product, 'options' ) && ! empty( $shopify_product->options ) ) {
			foreach ( $shopify_product->options as $option ) {
				$variable_data['attributes'][] = array(
					'name'         => $option->name,
					'options'      => $option->values,
					'position'     => $option->position,
					'is_visible'   => true,
					'is_variation' => true,
				);
			}
		}

		// Variations (Variable Only).
		$variable_data['variations'] = array();
		if ( $is_variable && property_exists( $shopify_product, 'variants' ) && ! empty( $shopify_product->variants->edges ) ) {
			foreach ( $shopify_product->variants->edges as $variant_edge ) {
				$variant_node                  = $variant_edge->node;
				$variation_data                = array();
				$variation_data['original_id'] = basename( $variant_node->id );

				if ( $this->should_process( 'price' ) ) {
					if ( $variant_node->compareAtPrice && (float) $variant_node->compareAtPrice > (float) $variant_node->price ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
						$variation_data['regular_price'] = $variant_node->compareAtPrice; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
						$variation_data['sale_price']    = $variant_node->price;
					} else {
						$variation_data['regular_price'] = $variant_node->price;
						$variation_data['sale_price']    = null;
					}
				}

				if ( $this->should_process( 'sku' ) ) {
					$variation_data['sku'] = $variant_node->sku ?? null;
				}

				if ( $this->should_process( 'stock' ) ) {
					$manage_stock                     = property_exists( $variant_node, 'inventoryItem' ) && $variant_node->inventoryItem->tracked; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					$variation_data['manage_stock']   = $manage_stock;
					$stock_quantity                   = $variant_node->inventoryQuantity ?? 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					$allow_oversell                   = $manage_stock && 'CONTINUE' === $variant_node->inventoryPolicy; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					$variation_data['stock_status']   = ( $stock_quantity > 0 || $allow_oversell ) ? 'instock' : 'outofstock';
					$variation_data['stock_quantity'] = $stock_quantity;
				}

				if ( $this->should_process( 'weight' ) ) {
					$weight_data = null;
					if ( property_exists( $variant_node, 'inventoryItem' ) && is_object( $variant_node->inventoryItem ) && // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
						property_exists( $variant_node->inventoryItem, 'measurement' ) && is_object( $variant_node->inventoryItem->measurement ) && // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
						property_exists( $variant_node->inventoryItem->measurement, 'weight' ) && is_object( $variant_node->inventoryItem->measurement->weight ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					) {
						$weight_data = $variant_node->inventoryItem->measurement->weight; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
					}
					$weight                   = $weight_data ? $weight_data->value : null;
					$weight_unit              = $weight_data ? $weight_data->unit : null;
					$variation_data['weight'] = $this->get_converted_weight( $weight, $weight_unit );
				}

				if ( $this->should_process( 'attributes' ) ) {
					$variation_data['attributes'] = array();
					if ( ! empty( $variant_node->selectedOptions ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
						foreach ( $variant_node->selectedOptions as $selectedOption ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- GraphQL uses camelCase.
							$variation_data['attributes'][ $selectedOption->name ] = $selectedOption->value; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- GraphQL uses camelCase.
						}
					}
				}

				if ( $this->should_process( 'images' ) ) {
					$variation_data['image_original_id'] = null;
					if ( ! empty( $variant_node->media->edges ) ) {
						$variant_media_node = $variant_node->media->edges[0]->node ?? null;
						if ( $variant_media_node && property_exists( $variant_media_node, 'image' ) && is_object( $variant_media_node->image ) && ! empty( $variant_media_node->id ) ) {
							$variation_data['image_original_id'] = $variant_media_node->id;
						}
					}
				}

				// Menu Order / Position.
				$variation_data['menu_order'] = $variant_node->position;

				$variable_data['variations'][] = $variation_data;
			}
		}

		return $variable_data;
	}

	/**
	 * Maps product images from Shopify media data.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Product images data.
	 */
	private function map_product_images( object $shopify_product ): array {
		$images_data       = array();
		$featured_media_id = null;

		if ( ! empty( $shopify_product->featuredMedia ) && is_object( $shopify_product->featuredMedia ) && ! empty( $shopify_product->featuredMedia->id ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
			$featured_media_id = $shopify_product->featuredMedia->id; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
		}

		if ( ! empty( $shopify_product->media->edges ) ) {
			foreach ( $shopify_product->media->edges as $media_edge ) {
				$media_node = $media_edge->node;
				if ( property_exists( $media_node, 'image' ) && is_object( $media_node->image ) && ! empty( $media_node->id ) && ! empty( $media_node->image->url ) ) {
					$images_data[] = array(
						'original_id' => $media_node->id,
						'src'         => $media_node->image->url,
						'alt'         => $media_node->image->altText ?? null,
						'is_featured' => ( $media_node->id === $featured_media_id ),
					);
				}
			}
		}

		return $images_data;
	}

	/**
	 * Maps metafields and SEO data from Shopify product.
	 *
	 * @param object $shopify_product The Shopify product data.
	 * @return array Metafields data.
	 */
	private function map_metafields( object $shopify_product ): array {
		$metafields_data = array();

		if ( property_exists( $shopify_product, 'metafields' ) && ! empty( $shopify_product->metafields->edges ) ) {
			foreach ( $shopify_product->metafields->edges as $edge ) {
				$field_node              = $edge->node;
				$key                     = sprintf( '%s_%s', $field_node->namespace, $field_node->key );
				$metafields_data[ $key ] = $field_node->value;
			}
		}

		// Enhanced SEO mapping.
		$seo_data        = $this->map_seo_fields( $shopify_product );
		$metafields_data = array_merge( $metafields_data, $seo_data );

		return $metafields_data;
	}

	/**
	 * Gets the default product fields to process if not specified.
	 *
	 * @return array Default fields.
	 */
	private function get_default_product_fields(): array {
		return array(
			'title',
			'slug',
			'description',
			'short_description',
			'status',
			'date_created',
			'catalog_visibility',
			'category',
			'tag',
			'price',
			'sku',
			'stock',
			'weight',
			'brand',
			'images',
			'seo',
			'attributes',
		);
	}
}
