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
	 * Initialize dependencies via DI container.
	 * Required for WooCommerce DI pattern compliance.
	 *
	 * @internal
	 */
	final public function init(): void {
		// No dependencies currently needed, but method required for DI pattern.
	}

	/**
	 * Maps raw Shopify product data to a standardized array format.
	 *
	 * @param object $shopify_product The raw Shopify product node from GraphQL.
	 * @return array Standardized data array for WooCommerce_Product_Importer.
	 */
	public function map_product_data( object $shopify_product ): array {
		$wc_data = array();

		$is_variable                    = $this->is_variable_product( $shopify_product );
		$wc_data['is_variable']         = $is_variable;
		$wc_data['original_product_id'] = basename( $shopify_product->id );

		// Basic Product Fields.
		$wc_data['name']             = $shopify_product->title;
		$wc_data['slug']             = $shopify_product->handle;
		$wc_data['description']      = $this->sanitize_product_description( $shopify_product->descriptionHtml ?? '' );
		$wc_data['short_description'] = $shopify_product->descriptionPlainSummary ?? '';
		$wc_data['status']           = $this->get_woo_product_status( $shopify_product );
		$wc_data['date_created_gmt'] = $shopify_product->createdAt;

		// Enhanced date handling.
		if ( property_exists( $shopify_product, 'updatedAt' ) ) {
			$wc_data['date_modified_gmt'] = $shopify_product->updatedAt;
		}

		// Catalog Visibility & Original URL.
		$wc_data['catalog_visibility'] = 'visible';
		$wc_data['original_url']       = null;
		if ( property_exists( $shopify_product, 'onlineStoreUrl' ) ) {
			if ( null === $shopify_product->onlineStoreUrl ) {
				$wc_data['catalog_visibility'] = 'hidden';
			} else {
				$wc_data['original_url'] = $shopify_product->onlineStoreUrl;
			}
		}

		// Enhanced publication status.
		$enhanced_status = $this->map_enhanced_status( $shopify_product );
		$wc_data         = array_merge( $wc_data, $enhanced_status );

		// Taxonomies.
		$wc_data['categories'] = $this->get_mapped_categories( $shopify_product );
		$wc_data['tags']       = $this->get_mapped_tags( $shopify_product );

		// Enhanced product classification.
		$classification = $this->map_product_classification( $shopify_product );
		$wc_data        = array_merge( $wc_data, $classification );

		// Brand (Vendor).
		$brand_name       = $shopify_product->vendor ?? null;
		$wc_data['brand'] = $brand_name ? array(
			'name' => $brand_name,
			'slug' => sanitize_title( $brand_name ),
		) : null;

		// Simple Product Fields.
		if ( ! $is_variable && ! empty( $shopify_product->variants->edges ) ) {
			$variant_node = $shopify_product->variants->edges[0]->node;

			// Price.
			if ( $this->should_process( 'price' ) ) {
				if ( $variant_node->compareAtPrice && $variant_node->compareAtPrice > $variant_node->price ) {
					$wc_data['sale_price']    = $variant_node->price;
					$wc_data['regular_price'] = $variant_node->compareAtPrice;
				} else {
					$wc_data['sale_price']    = null;
					$wc_data['regular_price'] = $variant_node->price;
				}
			}

			// SKU.
			if ( $this->should_process( 'sku' ) ) {
				$wc_data['sku'] = $variant_node->sku;
			}

			// Stock.
			if ( $this->should_process( 'stock' ) ) {
				$manage_stock              = property_exists( $variant_node, 'inventoryItem' ) && $variant_node->inventoryItem->tracked;
				$wc_data['manage_stock']   = $manage_stock;
				$stock_quantity            = $variant_node->inventoryQuantity ?? 0;
				$allow_oversell            = $manage_stock && 'CONTINUE' === $variant_node->inventoryPolicy;
				$wc_data['stock_status']   = ( $stock_quantity > 0 || $allow_oversell ) ? 'instock' : 'outofstock';
				$wc_data['stock_quantity'] = $stock_quantity;
			}

			// Weight.
			if ( $this->should_process( 'weight' ) ) {
				$weight_data = null;
				if ( property_exists( $variant_node, 'inventoryItem' ) && is_object( $variant_node->inventoryItem ) &&
					property_exists( $variant_node->inventoryItem, 'measurement' ) && is_object( $variant_node->inventoryItem->measurement ) &&
					property_exists( $variant_node->inventoryItem->measurement, 'weight' ) && is_object( $variant_node->inventoryItem->measurement->weight )
				) {
					$weight_data = $variant_node->inventoryItem->measurement->weight;
				}
				$weight            = $weight_data ? $weight_data->value : null;
				$weight_unit       = $weight_data ? $weight_data->unit : null;
				$wc_data['weight'] = $this->get_converted_weight( $weight, $weight_unit );
			}

			$wc_data['original_variant_id'] = basename( $variant_node->id );

		} else {
			// Defaults for variable or product with no variants.
			$wc_data['sku']                 = null;
			$wc_data['regular_price']       = null;
			$wc_data['sale_price']          = null;
			$wc_data['stock_quantity']      = null;
			$wc_data['manage_stock']        = false;
			$wc_data['stock_status']        = 'instock';
			$wc_data['weight']              = null;
			$wc_data['original_variant_id'] = null;
		}

		// Images.
		$wc_data['images'] = array();
		$featured_media_id = null;
		if ( ! empty( $shopify_product->featuredMedia ) && is_object( $shopify_product->featuredMedia ) && ! empty( $shopify_product->featuredMedia->id ) ) {
			$featured_media_id = $shopify_product->featuredMedia->id;
		}

		if ( ! empty( $shopify_product->media->edges ) ) {
			foreach ( $shopify_product->media->edges as $media_edge ) {
				$media_node = $media_edge->node;
				if ( property_exists( $media_node, 'image' ) && is_object( $media_node->image ) && ! empty( $media_node->id ) && ! empty( $media_node->image->url ) ) {
					$wc_data['images'][] = array(
						'original_id' => $media_node->id,
						'url'         => $media_node->image->url,
						'alt'         => $media_node->image->altText ?? null,
						'is_featured' => ( $media_node->id === $featured_media_id ),
					);
				}
			}
		}

		// Metafields & SEO.
		$wc_data['metafields'] = array();
		if ( property_exists( $shopify_product, 'metafields' ) && ! empty( $shopify_product->metafields->edges ) ) {
			foreach ( $shopify_product->metafields->edges as $edge ) {
				$field_node                    = $edge->node;
				$key                           = sprintf( '%s_%s', $field_node->namespace, $field_node->key );
				$wc_data['metafields'][ $key ] = $field_node->value;
			}
		}

		// Enhanced SEO mapping.
		$seo_data              = $this->map_seo_fields( $shopify_product );
		$wc_data['metafields'] = array_merge( $wc_data['metafields'], $seo_data );

		// Attributes (Variable Only).
		$wc_data['attributes'] = array();
		if ( $is_variable && property_exists( $shopify_product, 'options' ) && ! empty( $shopify_product->options ) ) {
			foreach ( $shopify_product->options as $option ) {
				$wc_data['attributes'][] = array(
					'name'         => $option->name,
					'options'      => $option->values,
					'position'     => $option->position,
					'is_visible'   => true,
					'is_variation' => true,
				);
			}
		}

		// Variations (Variable Only).
		$wc_data['variations'] = array();
		if ( $is_variable && property_exists( $shopify_product, 'variants' ) && ! empty( $shopify_product->variants->edges ) ) {
			foreach ( $shopify_product->variants->edges as $variant_edge ) {
				$variant_node                  = $variant_edge->node;
				$variation_data                = array();
				$variation_data['original_id'] = basename( $variant_node->id );

				// Price.
				if ( $this->should_process( 'price' ) ) {
					if ( $variant_node->compareAtPrice && (float) $variant_node->compareAtPrice > (float) $variant_node->price ) {
						$variation_data['regular_price'] = $variant_node->compareAtPrice;
						$variation_data['sale_price']    = $variant_node->price;
					} else {
						$variation_data['regular_price'] = $variant_node->price;
						$variation_data['sale_price']    = null;
					}
				}

				// SKU.
				if ( $this->should_process( 'sku' ) ) {
					$variation_data['sku'] = $variant_node->sku ?? null;
				}

				// Stock.
				if ( $this->should_process( 'stock' ) ) {
					$manage_stock                     = property_exists( $variant_node, 'inventoryItem' ) && $variant_node->inventoryItem->tracked;
					$variation_data['manage_stock']   = $manage_stock;
					$stock_quantity                   = $variant_node->inventoryQuantity ?? 0;
					$allow_oversell                   = $manage_stock && 'CONTINUE' === $variant_node->inventoryPolicy;
					$variation_data['stock_status']   = ( $stock_quantity > 0 || $allow_oversell ) ? 'instock' : 'outofstock';
					$variation_data['stock_quantity'] = $stock_quantity;
				}

				// Weight.
				if ( $this->should_process( 'weight' ) ) {
					$weight_data = null;
					if ( property_exists( $variant_node, 'inventoryItem' ) && is_object( $variant_node->inventoryItem ) &&
						property_exists( $variant_node->inventoryItem, 'measurement' ) && is_object( $variant_node->inventoryItem->measurement ) &&
						property_exists( $variant_node->inventoryItem->measurement, 'weight' ) && is_object( $variant_node->inventoryItem->measurement->weight )
					) {
						$weight_data = $variant_node->inventoryItem->measurement->weight;
					}
					$weight                   = $weight_data ? $weight_data->value : null;
					$weight_unit              = $weight_data ? $weight_data->unit : null;
					$variation_data['weight'] = $this->get_converted_weight( $weight, $weight_unit );
				}

				// Mapped Attributes.
				if ( $this->should_process( 'attributes' ) ) {
					$variation_data['attributes'] = array();
					if ( ! empty( $variant_node->selectedOptions ) ) {
						foreach ( $variant_node->selectedOptions as $selectedOption ) {
							$variation_data['attributes'][ $selectedOption->name ] = $selectedOption->value;
						}
					}
				}

				// Image Mapping.
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

				$wc_data['variations'][] = $variation_data;
			}
		}

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
		if ( property_exists( $shopify_product, 'publishedAt' ) && $shopify_product->publishedAt ) {
			$status_data['date_published_gmt'] = $shopify_product->publishedAt;
		}

		// Available for sale flag.
		if ( property_exists( $shopify_product, 'availableForSale' ) ) {
			$status_data['available_for_sale'] = $shopify_product->availableForSale;
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

		// Product type.
		if ( property_exists( $shopify_product, 'productType' ) && $shopify_product->productType ) {
			$classification['product_type'] = array(
				'name' => $shopify_product->productType,
				'slug' => sanitize_title( $shopify_product->productType ),
			);
		}

		// Standard category.
		if ( property_exists( $shopify_product, 'category' ) && is_object( $shopify_product->category ) ) {
			$classification['standard_category'] = array(
				'name' => $shopify_product->category->name ?? '',
				'slug' => sanitize_title( $shopify_product->category->name ?? '' ),
			);
		}

		// Gift card detection.
		if ( property_exists( $shopify_product, 'isGiftCard' ) ) {
			$classification['is_gift_card'] = $shopify_product->isGiftCard;
		}

		// Subscription product detection.
		if ( property_exists( $shopify_product, 'requiresSellingPlan' ) ) {
			$classification['requires_subscription'] = $shopify_product->requiresSellingPlan;
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

		$unit_map = array(
			'GRAMS'     => 'g',
			'KILOGRAMS' => 'kg',
			'POUNDS'    => 'lb',
			'OUNCES'    => 'oz',
		);

		$shopify_unit_key = $unit_map[ $weight_unit ] ?? null;

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

		// Fallback manual conversion.
		$conversion_factors = array(
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

		if ( ! isset( $conversion_factors[ $shopify_unit_key ][ $store_weight_unit ] ) ) {
			return (float) $weight;
		}

		return (float) $weight * $conversion_factors[ $shopify_unit_key ][ $store_weight_unit ];
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
