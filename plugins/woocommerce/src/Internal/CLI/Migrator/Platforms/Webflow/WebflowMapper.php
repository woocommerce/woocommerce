<?php
/**
 * Webflow Mapper
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Transforms a raw Webflow product+SKUs payload into the standardized array
 * consumed by WooCommerceProductImporter.
 *
 * Webflow's eCommerce model differs from Shopify's in several ways:
 *
 *  - Variants ("SKUs") live nested under the product in a single response.
 *  - Variant options live in `product.fieldData['sku-properties']` as an array
 *    of `{ id, name, enum: [ { id, name, slug } ] }`. Each SKU's `sku-values`
 *    maps property id => enum id, so the mapper must resolve ids to human
 *    names before producing WC attributes / variation attributes.
 *  - Prices are integer minor units (e.g. cents). The unit lives alongside.
 *  - Categories are CMS item references that the fetcher pre-resolves onto
 *    the item as `_resolved_categories` (an array of `{name, slug}`).
 *  - Images come from `main-image` + `more-images` on each SKU, plus
 *    `more-images` on the product itself. They must all live in a single
 *    `images[]` array so the importer can build the original_id => attachment
 *    map that variations rely on.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
class WebflowMapper implements PlatformMapperInterface {

	/**
	 * Fields to process during mapping (selected via --fields/--exclude-fields).
	 *
	 * @var array
	 */
	private array $fields_to_process = array();

	/**
	 * Map of ISO currency code => minor-unit count, lazily built from core's locale-info.
	 *
	 * @var array<string,int>|null
	 */
	private ?array $currency_decimals = null;

	/**
	 * Constructor.
	 *
	 * @param array $args Optional arguments. Recognized keys:
	 *                    - 'fields': array of field keys to process. Empty/missing means all.
	 */
	public function __construct( array $args = array() ) {
		$this->fields_to_process = $args['fields'] ?? array();
	}

	/**
	 * Maps a raw Webflow product+SKUs item into the importer's standardized array shape.
	 *
	 * @param object $platform_data Webflow product item: `{ product: { id, fieldData: ... }, skus: [...], _resolved_categories?: [...] }`.
	 * @return array Standardized data array.
	 */
	public function map_product_data( object $platform_data ): array {
		$product    = $this->extract_product( $platform_data );
		$field_data = $this->extract_field_data( $product );
		$skus       = $this->extract_skus( $platform_data );

		$properties = $this->extract_sku_properties( $field_data );
		// Needs properties AND more than one SKU. A single-SKU product is imported as simple,
		// intentionally dropping its lone option (a one-value attribute adds no variation choice).
		$is_variable = ! empty( $properties ) && count( $skus ) > 1;

		$wc_data = $this->map_basic_fields( $product, $field_data, $is_variable );

		$wc_data['categories'] = $this->should_process( 'categories' ) ? $this->map_categories( $platform_data ) : array();
		$wc_data['tags']       = array();

		$images            = $this->should_process( 'images' ) ? $this->build_images( $field_data, $skus ) : array();
		$wc_data['images'] = $images;

		if ( $is_variable ) {
			$wc_data = array_merge( $wc_data, $this->map_variable_data( $properties, $skus, $images ) );
		} else {
			$wc_data               = array_merge( $wc_data, $this->map_simple_data( $skus ) );
			$wc_data['attributes'] = array();
			$wc_data['variations'] = array();
		}

		$wc_data['metafields'] = $this->map_seo( $field_data );

		return $wc_data;
	}

	/**
	 * Webflow list-products items wrap a `product` object and a `skus` array.
	 *
	 * Accept either shape (the wrapped item, or a bare product object) so tests
	 * and downstream callers can be lenient.
	 *
	 * @param object $platform_data Raw item.
	 * @return object Product object (with fieldData).
	 */
	private function extract_product( object $platform_data ): object {
		if ( isset( $platform_data->product ) && is_object( $platform_data->product ) ) {
			return $platform_data->product;
		}
		return $platform_data;
	}

	/**
	 * Returns the fieldData object on a product, or an empty stdClass for safety.
	 *
	 * @param object $product Product object.
	 * @return object
	 */
	private function extract_field_data( object $product ): object {
		if ( isset( $product->fieldData ) && is_object( $product->fieldData ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			return $product->fieldData; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		}
		return new \stdClass();
	}

	/**
	 * Returns the SKUs array off a wrapped item, or empty array.
	 *
	 * @param object $platform_data Raw item.
	 * @return array<int,object>
	 */
	private function extract_skus( object $platform_data ): array {
		if ( isset( $platform_data->skus ) && is_array( $platform_data->skus ) ) {
			return $platform_data->skus;
		}
		return array();
	}

	/**
	 * Returns the sku-properties array off fieldData, or empty array.
	 *
	 * @param object $field_data Field data.
	 * @return array<int,object>
	 */
	private function extract_sku_properties( object $field_data ): array {
		$key = 'sku-properties';
		if ( isset( $field_data->{$key} ) && is_array( $field_data->{$key} ) ) {
			return $field_data->{$key};
		}
		return array();
	}

	/**
	 * Map fields common to every product (name, slug, description, status…).
	 *
	 * @param object $product     Product object.
	 * @param object $field_data  Field data object.
	 * @param bool   $is_variable Whether this product has multiple SKUs to expose as variations.
	 * @return array
	 */
	private function map_basic_fields( object $product, object $field_data, bool $is_variable ): array {
		$basic = array();

		$basic['is_variable']         = $is_variable;
		$basic['original_product_id'] = isset( $product->id ) ? (string) $product->id : null;

		$basic['name']        = isset( $field_data->name ) ? sanitize_text_field( (string) $field_data->name ) : '';
		$basic['slug']        = isset( $field_data->slug ) ? sanitize_title( (string) $field_data->slug ) : sanitize_title( $basic['name'] );
		$basic['description'] = isset( $field_data->description ) ? wp_kses_post( (string) $field_data->description ) : '';

		$short_description_key      = 'short-description';
		$basic['short_description'] = isset( $field_data->{$short_description_key} )
			? wp_kses_post( (string) $field_data->{$short_description_key} )
			: '';

		// Status and visibility mirror the source on every import, so a re-run resets any manual
		// draft/hide a merchant applied to a previously imported product. This is intentional: the
		// migrator treats Webflow as the source of truth for these fields.
		$basic['status']             = $this->map_status( $product );
		$basic['catalog_visibility'] = 'visible';

		if ( isset( $product->createdOn ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			$basic['date_created_gmt'] = (string) $product->createdOn; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		}
		if ( isset( $product->lastUpdated ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			$basic['date_modified_gmt'] = (string) $product->lastUpdated; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		}

		$basic['brand'] = null;

		return $basic;
	}

	/**
	 * Map Webflow product publication flags to WC status.
	 *
	 * @param object $product Product object.
	 * @return string
	 */
	private function map_status( object $product ): string {
		$is_archived = ! empty( $product->isArchived ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		$is_draft    = ! empty( $product->isDraft );    // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.

		if ( $is_archived ) {
			return 'draft';
		}
		if ( $is_draft ) {
			return 'draft';
		}
		return 'publish';
	}

	/**
	 * Read the pre-resolved categories the fetcher attached to the item.
	 *
	 * @param object $platform_data Raw item.
	 * @return array<int,array{name:string,slug:string}>
	 */
	private function map_categories( object $platform_data ): array {
		$resolved_key = '_resolved_categories';
		if ( ! isset( $platform_data->{$resolved_key} ) || ! is_array( $platform_data->{$resolved_key} ) ) {
			return array();
		}

		$categories = array();
		foreach ( $platform_data->{$resolved_key} as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['name'] ) ) {
				$categories[] = array(
					'name' => sanitize_text_field( (string) $entry['name'] ),
					'slug' => sanitize_title( (string) ( $entry['slug'] ?? $entry['name'] ) ),
				);
			} elseif ( is_object( $entry ) && ! empty( $entry->name ) ) {
				$categories[] = array(
					'name' => sanitize_text_field( (string) $entry->name ),
					'slug' => sanitize_title( (string) ( $entry->slug ?? $entry->name ) ),
				);
			}
		}
		return $categories;
	}

	/**
	 * Build the unified images array (product gallery + SKU images, deduped by URL).
	 *
	 * The importer requires every image referenced from a variation to also appear
	 * in this top-level array — that's how the original_id => attachment mapping is
	 * populated. We assign stable `original_id`s here (preferring Webflow's fileId,
	 * falling back to a URL hash) so SKU references resolve cleanly later.
	 *
	 * @param object            $field_data Product fieldData.
	 * @param array<int,object> $skus       SKUs array.
	 * @return array<int,array{original_id:string,src:string,alt:?string,is_featured:bool}>
	 */
	private function build_images( object $field_data, array $skus ): array {
		$images       = array();
		$by_url       = array();
		$featured_url = null;

		// Product-level gallery: fieldData['more-images'].
		$more_images_key = 'more-images';
		if ( isset( $field_data->{$more_images_key} ) && is_array( $field_data->{$more_images_key} ) ) {
			foreach ( $field_data->{$more_images_key} as $img ) {
				$entry = $this->normalize_image_object( $img );
				if ( null === $entry ) {
					continue;
				}
				if ( null === $featured_url ) {
					$featured_url = $entry['src'];
				}
				$this->add_unique_image( $images, $by_url, $entry );
			}
		}

		// SKU main-image + more-images.
		$main_image_key = 'main-image';
		foreach ( $skus as $sku ) {
			$sku_field = isset( $sku->fieldData ) && is_object( $sku->fieldData ) ? $sku->fieldData : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			if ( null === $sku_field ) {
				continue;
			}

			$main_entry = isset( $sku_field->{$main_image_key} ) ? $this->normalize_image_object( $sku_field->{$main_image_key} ) : null;
			if ( $main_entry ) {
				if ( null === $featured_url ) {
					$featured_url = $main_entry['src'];
				}
				$this->add_unique_image( $images, $by_url, $main_entry );
			}

			if ( isset( $sku_field->{$more_images_key} ) && is_array( $sku_field->{$more_images_key} ) ) {
				foreach ( $sku_field->{$more_images_key} as $img ) {
					$entry = $this->normalize_image_object( $img );
					if ( $entry ) {
						$this->add_unique_image( $images, $by_url, $entry );
					}
				}
			}
		}

		// Mark featured.
		foreach ( $images as &$image ) {
			$image['is_featured'] = ( $image['src'] === $featured_url );
		}
		unset( $image );

		return $images;
	}

	/**
	 * Normalize a Webflow image object into our images-array entry, or return null if unusable.
	 *
	 * @param mixed $img Raw Webflow image entry.
	 * @return array{original_id:string,src:string,alt:?string,is_featured:bool}|null
	 */
	private function normalize_image_object( $img ): ?array {
		if ( ! is_object( $img ) ) {
			return null;
		}

		$url = isset( $img->url ) ? (string) $img->url : '';
		if ( '' === $url ) {
			return null;
		}

		$alt = isset( $img->alt ) ? (string) $img->alt : null;

		$original_id = '';
		if ( isset( $img->fileId ) && '' !== (string) $img->fileId ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			$original_id = (string) $img->fileId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		} elseif ( isset( $img->id ) && '' !== (string) $img->id ) {
			$original_id = (string) $img->id;
		} else {
			$original_id = 'webflow-' . md5( $url );
		}

		return array(
			'original_id' => $original_id,
			'src'         => $url,
			'alt'         => $alt,
			'is_featured' => false,
		);
	}

	/**
	 * Append an image entry to $images keyed by URL, deduping repeats.
	 *
	 * @param array $images   Image list (mutated).
	 * @param array $by_url   URL => index map (mutated).
	 * @param array $entry    The image entry to add.
	 * @return void
	 */
	private function add_unique_image( array &$images, array &$by_url, array $entry ): void {
		$url = $entry['src'];
		if ( isset( $by_url[ $url ] ) ) {
			return;
		}
		$by_url[ $url ] = count( $images );
		$images[]       = $entry;
	}

	/**
	 * Map fields specific to a simple (single-SKU) product.
	 *
	 * @param array<int,object> $skus SKUs.
	 * @return array
	 */
	private function map_simple_data( array $skus ): array {
		$simple = array(
			'sku'            => null,
			'regular_price'  => null,
			'sale_price'     => null,
			'manage_stock'   => false,
			'stock_quantity' => null,
			'stock_status'   => 'instock',
			'tax_status'     => 'taxable',
		);

		if ( empty( $skus ) ) {
			return $simple;
		}

		$sku       = $skus[0];
		$sku_field = isset( $sku->fieldData ) && is_object( $sku->fieldData ) ? $sku->fieldData : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
		if ( null === $sku_field ) {
			return $simple;
		}

		if ( $this->should_process( 'price' ) ) {
			$prices                  = $this->extract_prices( $sku_field );
			$simple['regular_price'] = $prices['regular_price'];
			$simple['sale_price']    = $prices['sale_price'];
		}

		if ( $this->should_process( 'sku' ) && isset( $sku_field->sku ) ) {
			$simple['sku'] = wc_clean( (string) $sku_field->sku );
		}

		if ( $this->should_process( 'stock' ) ) {
			$stock                    = $this->extract_stock( $sku_field );
			$simple['manage_stock']   = $stock['manage_stock'];
			$simple['stock_quantity'] = $stock['stock_quantity'];
			$simple['stock_status']   = $stock['stock_status'];
		}

		if ( $this->should_process( 'weight' ) ) {
			$simple['weight'] = $this->extract_weight( $sku_field );
		}

		if ( $this->should_process( 'dimensions' ) ) {
			$simple = array_merge( $simple, $this->extract_dimensions( $sku_field ) );
		}

		return $simple;
	}

	/**
	 * Map variable-product data: attribute definitions + per-variation rows.
	 *
	 * @param array<int,object> $properties sku-properties array.
	 * @param array<int,object> $skus       SKUs array.
	 * @param array<int,array>  $images     Already-built images list (to resolve image references).
	 * @return array{attributes: array, variations: array}
	 */
	private function map_variable_data( array $properties, array $skus, array $images ): array {
		$attributes = array();

		// Build a lookup: property_id => [ 'name' => string, 'enums' => [ enum_id => option_name ] ].
		$property_lookup = array();
		$position        = 0;
		foreach ( $properties as $property ) {
			if ( ! is_object( $property ) || empty( $property->id ) || empty( $property->name ) ) {
				continue;
			}

			$enum_map     = array();
			$enum_options = array();
			$enums        = ( isset( $property->enum ) && is_array( $property->enum ) ) ? $property->enum : array();

			foreach ( $enums as $enum ) {
				if ( ! is_object( $enum ) || empty( $enum->id ) || empty( $enum->name ) ) {
					continue;
				}
				$enum_map[ (string) $enum->id ] = (string) $enum->name;
				$enum_options[]                 = (string) $enum->name;
			}

			$property_lookup[ (string) $property->id ] = array(
				'name'  => (string) $property->name,
				'enums' => $enum_map,
			);

			if ( $this->should_process( 'attributes' ) ) {
				$attributes[] = array(
					'name'         => wc_clean( (string) $property->name ),
					'options'      => array_map( 'wc_clean', $enum_options ),
					'position'     => $position,
					'is_visible'   => true,
					'is_variation' => true,
				);
			}

			++$position;
		}

		// Build URL => original_id map so we can resolve a SKU's main-image back to an entry in images[].
		$url_to_original_id = array();
		foreach ( $images as $image ) {
			$url_to_original_id[ $image['src'] ] = $image['original_id'];
		}

		$variations = array();
		$menu_order = 0;
		foreach ( $skus as $sku ) {
			$sku_field = isset( $sku->fieldData ) && is_object( $sku->fieldData ) ? $sku->fieldData : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			if ( null === $sku_field ) {
				continue;
			}

			$variation = array(
				'original_id'       => isset( $sku->id ) ? (string) $sku->id : null,
				'sku'               => null,
				'regular_price'     => null,
				'sale_price'        => null,
				'manage_stock'      => false,
				'stock_quantity'    => null,
				'stock_status'      => 'instock',
				'tax_status'        => 'taxable',
				'attributes'        => array(),
				'image_original_id' => null,
				'menu_order'        => $menu_order,
			);

			if ( $this->should_process( 'sku' ) && isset( $sku_field->sku ) ) {
				$variation['sku'] = wc_clean( (string) $sku_field->sku );
			}

			if ( $this->should_process( 'price' ) ) {
				$prices                     = $this->extract_prices( $sku_field );
				$variation['regular_price'] = $prices['regular_price'];
				$variation['sale_price']    = $prices['sale_price'];
			}

			if ( $this->should_process( 'stock' ) ) {
				$stock                       = $this->extract_stock( $sku_field );
				$variation['manage_stock']   = $stock['manage_stock'];
				$variation['stock_quantity'] = $stock['stock_quantity'];
				$variation['stock_status']   = $stock['stock_status'];
			}

			if ( $this->should_process( 'weight' ) ) {
				$variation['weight'] = $this->extract_weight( $sku_field );
			}

			if ( $this->should_process( 'dimensions' ) ) {
				$variation = array_merge( $variation, $this->extract_dimensions( $sku_field ) );
			}

			if ( $this->should_process( 'attributes' ) ) {
				$variation['attributes'] = $this->resolve_variation_attributes( $sku_field, $property_lookup );
				if ( empty( $variation['attributes'] ) ) {
					wc_get_logger()->debug(
						sprintf(
							'Webflow variation %s resolved to zero attributes; WooCommerce will store it as an "Any" variation, which can collide with sibling variations.',
							$variation['original_id'] ?? 'unknown'
						),
						array( 'source' => 'wc-migrator' )
					);
				}
			}

			if ( $this->should_process( 'images' ) ) {
				$main_image_key = 'main-image';
				if ( isset( $sku_field->{$main_image_key}->url ) ) {
					$url = (string) $sku_field->{$main_image_key}->url;
					if ( isset( $url_to_original_id[ $url ] ) ) {
						$variation['image_original_id'] = $url_to_original_id[ $url ];
					}
				}
			}

			$variations[] = $variation;
			++$menu_order;
		}

		return array(
			'attributes' => $attributes,
			'variations' => $variations,
		);
	}

	/**
	 * Resolve a SKU's `sku-values` (property_id => enum_id) into a human-readable
	 * `attribute_name => option_name` array using the property lookup.
	 *
	 * @param object $sku_field       SKU fieldData.
	 * @param array  $property_lookup Property lookup map.
	 * @return array<string,string>
	 */
	private function resolve_variation_attributes( object $sku_field, array $property_lookup ): array {
		$resolved   = array();
		$values_key = 'sku-values';
		if ( ! isset( $sku_field->{$values_key} ) ) {
			return $resolved;
		}

		$values = $sku_field->{$values_key};
		if ( ! is_object( $values ) && ! is_array( $values ) ) {
			return $resolved;
		}

		foreach ( (array) $values as $property_id => $enum_id ) {
			$property_id = (string) $property_id;
			$enum_id     = (string) $enum_id;
			if ( ! isset( $property_lookup[ $property_id ] ) ) {
				continue;
			}
			$property_name = $property_lookup[ $property_id ]['name'];
			$option_name   = $property_lookup[ $property_id ]['enums'][ $enum_id ] ?? null;
			if ( null === $option_name ) {
				continue;
			}
			$resolved[ sanitize_text_field( $property_name ) ] = sanitize_text_field( $option_name );
		}

		return $resolved;
	}

	/**
	 * Convert Webflow `price.value` (minor units, e.g. cents) into a decimal string,
	 * and detect sale pricing via `compare-at-price`.
	 *
	 * @param object $sku_field SKU fieldData.
	 * @return array{regular_price: ?string, sale_price: ?string}
	 */
	private function extract_prices( object $sku_field ): array {
		$price         = $this->price_to_decimal( $sku_field->price ?? null );
		$compare_key   = 'compare-at-price';
		$compare_price = $this->price_to_decimal( $sku_field->{$compare_key} ?? null );

		if ( null !== $compare_price && null !== $price && (float) $compare_price > (float) $price ) {
			return array(
				'regular_price' => $compare_price,
				'sale_price'    => $price,
			);
		}

		return array(
			'regular_price' => $price,
			'sale_price'    => null,
		);
	}

	/**
	 * Convert a Webflow money object `{ value: int (minor units), unit: "USD" }` into a decimal string.
	 *
	 * The number of minor units per major unit varies by currency (e.g. JPY has 0,
	 * KWD has 3, most have 2), so the divisor is derived from the currency's
	 * `num_decimals` rather than a hardcoded `/ 100`.
	 *
	 * @param mixed $money Webflow money object.
	 * @return string|null
	 */
	private function price_to_decimal( $money ): ?string {
		if ( ! is_object( $money ) || ! isset( $money->value ) ) {
			return null;
		}
		$minor = (int) $money->value;
		if ( $minor < 0 ) {
			return null;
		}
		$unit     = isset( $money->unit ) ? (string) $money->unit : 'USD';
		$decimals = $this->get_currency_decimals( $unit );
		return number_format( $minor / ( 10 ** $decimals ), $decimals, '.', '' );
	}

	/**
	 * Get the number of decimal places (minor units) for an ISO currency code.
	 *
	 * Reads core's `i18n/locale-info.php` so the migrator stays in sync with
	 * WooCommerce's own per-currency data. Falls back to 2 for unknown codes.
	 *
	 * @param string $currency ISO 4217 currency code.
	 * @return int
	 */
	private function get_currency_decimals( string $currency ): int {
		if ( null === $this->currency_decimals ) {
			$this->currency_decimals = array();
			$locale_info             = include WC()->plugin_path() . '/i18n/locale-info.php';
			if ( is_array( $locale_info ) ) {
				foreach ( $locale_info as $info ) {
					if ( isset( $info['currency_code'], $info['num_decimals'] ) ) {
						$this->currency_decimals[ $info['currency_code'] ] = (int) $info['num_decimals'];
					}
				}
			}
		}
		return $this->currency_decimals[ strtoupper( $currency ) ] ?? 2;
	}

	/**
	 * Map Webflow inventory shape to WC stock fields.
	 *
	 * @param object $sku_field SKU fieldData.
	 * @return array{manage_stock: bool, stock_quantity: ?int, stock_status: string}
	 */
	private function extract_stock( object $sku_field ): array {
		$inventory = isset( $sku_field->inventory ) && is_object( $sku_field->inventory ) ? $sku_field->inventory : null;

		if ( null === $inventory ) {
			return array(
				'manage_stock'   => false,
				'stock_quantity' => null,
				'stock_status'   => 'instock',
			);
		}

		$type = isset( $inventory->type ) ? (string) $inventory->type : 'infinite';
		if ( 'finite' !== $type ) {
			return array(
				'manage_stock'   => false,
				'stock_quantity' => null,
				'stock_status'   => 'instock',
			);
		}

		$quantity = isset( $inventory->quantity ) ? (int) $inventory->quantity : 0;
		return array(
			'manage_stock'   => true,
			'stock_quantity' => $quantity,
			'stock_status'   => $quantity > 0 ? 'instock' : 'outofstock',
		);
	}

	/**
	 * Extract Webflow SKU dimensions (length, width, height).
	 *
	 * Webflow returns these as raw numerics with no associated unit on the SKU
	 * payload — the unit is a store-level setting on the Webflow side, with no
	 * API representation. We pass through as-is; WooCommerce will interpret them
	 * in whatever `woocommerce_dimension_unit` is configured for the destination
	 * store. Null/zero/non-numeric values are dropped.
	 *
	 * @param object $sku_field SKU fieldData.
	 * @return array{length: ?float, width: ?float, height: ?float}
	 */
	private function extract_dimensions( object $sku_field ): array {
		$dimensions = array(
			'length' => null,
			'width'  => null,
			'height' => null,
		);
		foreach ( array_keys( $dimensions ) as $key ) {
			if ( isset( $sku_field->{$key} ) && is_numeric( $sku_field->{$key} ) && (float) $sku_field->{$key} > 0 ) {
				$dimensions[ $key ] = (float) $sku_field->{$key};
			}
		}
		return $dimensions;
	}

	/**
	 * Convert Webflow `weight` (with optional `weight-unit`) to the store's weight unit.
	 *
	 * @param object $sku_field SKU fieldData.
	 * @return float|null
	 */
	private function extract_weight( object $sku_field ): ?float {
		if ( ! isset( $sku_field->weight ) ) {
			return null;
		}

		$weight = (float) $sku_field->weight;
		if ( $weight <= 0 ) {
			return null;
		}

		$unit_key    = 'weight-unit';
		$source_unit = isset( $sku_field->{$unit_key} ) ? strtolower( (string) $sku_field->{$unit_key} ) : 'lbs';
		$source_unit = $this->normalize_weight_unit( $source_unit );

		$store_unit = strtolower( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );

		if ( $source_unit === $store_unit ) {
			return $weight;
		}

		if ( function_exists( 'wc_get_weight' ) ) {
			$converted = wc_get_weight( $weight, $store_unit, $source_unit );
			return is_numeric( $converted ) ? (float) $converted : $weight;
		}

		return $weight;
	}

	/**
	 * Normalize a Webflow weight unit string to a wc_get_weight compatible unit.
	 *
	 * @param string $unit Raw unit string.
	 * @return string
	 */
	private function normalize_weight_unit( string $unit ): string {
		$map = array(
			'oz'    => 'oz',
			'lb'    => 'lbs',
			'lbs'   => 'lbs',
			'pound' => 'lbs',
			'g'     => 'g',
			'gram'  => 'g',
			'kg'    => 'kg',
		);
		return $map[ $unit ] ?? 'lbs';
	}

	/**
	 * Map Webflow SEO fields (`seo-title`, `seo-description`) into a metafields array.
	 *
	 * @param object $field_data Field data.
	 * @return array<string,string>
	 */
	private function map_seo( object $field_data ): array {
		$meta = array();

		$title_key = 'seo-title';
		$desc_key  = 'seo-description';

		if ( isset( $field_data->{$title_key} ) && '' !== (string) $field_data->{$title_key} ) {
			$meta['global_title_tag'] = (string) $field_data->{$title_key};
		}
		if ( isset( $field_data->{$desc_key} ) && '' !== (string) $field_data->{$desc_key} ) {
			$meta['global_description_tag'] = (string) $field_data->{$desc_key};
		}

		return $meta;
	}

	/**
	 * Should this field be processed?
	 *
	 * @param string $field_key The field key.
	 * @return bool
	 */
	private function should_process( string $field_key ): bool {
		if ( empty( $this->fields_to_process ) ) {
			return true;
		}
		return in_array( $field_key, $this->fields_to_process, true );
	}
}
