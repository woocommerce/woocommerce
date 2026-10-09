<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductFeed\Mapping;

use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;

/**
 * Maps WooCommerce products to the UCP catalog product shape.
 *
 * Instances carry per-response state (variant budget, truncation records), so a
 * caller assembling one response reuses one instance and calls
 * {@see self::begin_response()} before its first mapping call.
 *
 * @since 11.3.0
 */
class UcpProductMapper implements ProductShapeMapperInterface {
	/**
	 * Maximum number of variants returned for a single product. Each variant is
	 * a product load; a request naming its variations never hits this cap.
	 */
	const MAX_VARIANTS_PER_PRODUCT = 100;

	/**
	 * Maximum number of variants built across one response. Not absolute:
	 * `variants` has `minItems: 1`, so the worst case is this budget plus one
	 * per product on the page.
	 */
	const MAX_VARIANTS_PER_RESPONSE = 500;

	/**
	 * Maximum number of media entries returned per product.
	 */
	const MAX_MEDIA_PER_PRODUCT = 10;

	/**
	 * Maximum number of categories or tags returned per product.
	 */
	const MAX_TERMS_PER_PRODUCT = 25;

	/**
	 * ISO 4217 currency code, or '' to resolve the store currency per call.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Minor-unit formatter.
	 *
	 * @var MoneyFormatter
	 */
	private $money;

	/**
	 * Products whose variant list was cut short, keyed by product id.
	 *
	 * @var array
	 */
	private $truncations = array();

	/**
	 * Variants still available to build in the response being assembled.
	 *
	 * @var int
	 */
	private $variant_budget = self::MAX_VARIANTS_PER_RESPONSE;

	/**
	 * Constructor.
	 *
	 * @param string $currency ISO 4217 currency code; '' uses the store currency.
	 */
	public function __construct( string $currency = '' ) {
		$this->currency = $currency;
		$this->money    = new MoneyFormatter();
	}

	/**
	 * Start a new response, clearing per-response state.
	 */
	public function begin_response(): void {
		$this->truncations    = array();
		$this->variant_budget = self::MAX_VARIANTS_PER_RESPONSE;
	}

	/**
	 * Take the truncations recorded while building the current response.
	 *
	 * Raw data, not messages: only the caller knows the JSONPath to point at.
	 *
	 * @return array Keyed by product id, each `{ title, returned, total }`.
	 */
	public function take_truncations(): array {
		$truncations       = $this->truncations;
		$this->truncations = array();

		return $truncations;
	}

	/**
	 * Map a page of products, skipping those with no catalog variant.
	 *
	 * @param array $products WooCommerce products.
	 * @return array
	 */
	public function map_products( array $products ): array {
		$this->begin_response();

		$mapped = array();

		foreach ( $products as $product ) {
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			if ( ! $this->has_catalog_variants( $product ) ) {
				continue;
			}

			$mapped[] = $this->map_product( $product );
		}

		return $mapped;
	}

	/**
	 * Map a product to the UCP catalog product shape.
	 *
	 * @param \WC_Product $product The product to map.
	 * @param array       $required_variation_ids Variation ids the request named directly, which the
	 *                                            per-product variant cap must not drop.
	 * @param bool        $only_required Build only the named variations, because the caller will
	 *                                   discard everything else.
	 * @return array The mapped product data.
	 */
	public function map_product( \WC_Product $product, array $required_variation_ids = array(), bool $only_required = false ): array {
		$currency = $this->get_currency();

		$mapped = array(
			'id'          => (string) $product->get_id(),
			'title'       => $product->get_name(),
			'description' => $this->build_description( $product ),
			'price_range' => $this->build_price_range( $product, $currency ),
			'variants'    => $this->build_variants( $product, $currency, $required_variation_ids, $only_required ),
		);

		$handle = $product->get_slug();
		if ( '' !== $handle ) {
			$mapped['handle'] = $handle;
		}

		$url = $product->get_permalink();
		if ( is_string( $url ) && '' !== $url ) {
			$mapped['url'] = $url;
		}

		$categories = $this->build_categories( $product );
		if ( ! empty( $categories ) ) {
			$mapped['categories'] = $categories;
		}

		$media = $this->build_media( $product );
		if ( ! empty( $media ) ) {
			$mapped['media'] = $media;
		}

		$options = $this->build_options( $product );
		if ( ! empty( $options ) ) {
			$mapped['options'] = $options;
		}

		$list_price_range = $this->build_list_price_range( $product, $currency );
		if ( null !== $list_price_range ) {
			$mapped['list_price_range'] = $list_price_range;
		}

		$rating = $this->build_rating( $product );
		if ( null !== $rating ) {
			$mapped['rating'] = $rating;
		}

		$tags = $this->build_tags( $product );
		if ( ! empty( $tags ) ) {
			$mapped['tags'] = $tags;
		}

		return $mapped;
	}

	/**
	 * Map a product for `POST /catalog/lookup`.
	 *
	 * Every returned variant must be justified by a request identifier via the
	 * required `inputs` metadata. A product-level identifier resolves to all of
	 * the product's variants as `featured` — server-selected representatives —
	 * while an identifier naming a variant directly (variant id or SKU) resolves
	 * only to that variant, as `exact`. Variants no identifier resolved to are
	 * dropped, so a request naming only variations gets only those variations
	 * back; several identifiers resolving to one variant all appear in its
	 * `inputs`.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param array       $inputs Identifiers from the lookup request that resolved to this product,
	 *                            each `{ id, variation_id }` with the variation it named or 0 for
	 *                            the product itself.
	 * @return array
	 */
	public function map_lookup_product( \WC_Product $product, array $inputs ): array {
		// A variation named by id must survive the per-product cap.
		$required      = array();
		$names_product = false;
		foreach ( $inputs as $input ) {
			if ( $input['variation_id'] > 0 ) {
				$required[] = (int) $input['variation_id'];
				continue;
			}

			// A product-level identifier (product id or product SKU) needs the
			// full variant list.
			$names_product = true;
		}

		// With only variation-level identifiers, build only those variants: the
		// loop below discards the rest anyway.
		$payload = $this->map_product( $product, $required, ! $names_product );

		if ( ! isset( $payload['variants'] ) || ! is_array( $payload['variants'] ) ) {
			return $payload;
		}

		$variants = array();

		foreach ( $payload['variants'] as $variant ) {
			$correlations = array();

			foreach ( $inputs as $input ) {
				if ( $input['variation_id'] > 0 && (string) $input['variation_id'] !== (string) $variant['id'] ) {
					continue;
				}

				$names_variant = $input['id'] === (string) $variant['id']
					|| ( isset( $variant['sku'] ) && $input['id'] === $variant['sku'] );

				$correlations[] = array(
					'id'    => $input['id'],
					'match' => $names_variant ? 'exact' : 'featured',
				);
			}

			if ( empty( $correlations ) ) {
				continue;
			}

			$variant['inputs'] = $correlations;
			$variants[]        = $variant;
		}

		$payload['variants'] = $variants;

		return $payload;
	}

	/**
	 * Map a product for `POST /catalog/product`.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param array       $selected Selected option values, each `{ name, label, id? }`.
	 * @return array
	 */
	public function map_detail_product( \WC_Product $product, array $selected = array() ): array {
		$base = $this->map_product( $product );

		if ( ! empty( $selected ) && $product instanceof \WC_Product_Variable ) {
			$base['selected'] = $selected;

			$matched_variants = $this->filter_variants_by_selection( $product, $selected, $this->get_currency() );
			if ( ! empty( $matched_variants ) ) {
				$base['variants'] = $matched_variants;
			}
		}

		return $base;
	}

	/**
	 * Determine whether a product exposes at least one purchasable catalog variant.
	 *
	 * Single source of truth for catalog eligibility: a product without variants
	 * would violate the catalog `product` schema (`variants` requires
	 * `minItems: 1`), so it is excluded from listings and treated as not found on
	 * detail lookups. Simple products always qualify; a variable product needs at
	 * least one visible variation: enabled and, when the store hides out-of-stock
	 * items, in stock. That is the set the storefront and the Store API offer, read
	 * through the data store's cached children list.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return bool
	 */
	public function has_catalog_variants( \WC_Product $product ): bool {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return true;
		}

		return ! empty( $product->get_visible_children() );
	}

	/**
	 * Convert a major-unit store amount to the integer minor units UCP requires.
	 *
	 * @param mixed $amount Monetary amount with decimals.
	 * @return int
	 */
	public function to_minor_units( $amount ): int {
		return (int) $this->money->format( is_scalar( $amount ) ? (string) $amount : '0' );
	}

	/**
	 * Resolve the currency code used to label amounts.
	 *
	 * @return string
	 */
	private function get_currency(): string {
		return '' !== $this->currency ? $this->currency : (string) get_woocommerce_currency();
	}

	/**
	 * Build a `price` object.
	 *
	 * @param mixed  $amount Monetary amount with decimals.
	 * @param string $currency ISO 4217 currency code.
	 * @return array
	 */
	private function price( $amount, string $currency ): array {
		return array(
			'amount'   => $this->to_minor_units( $amount ),
			'currency' => $currency,
		);
	}

	/**
	 * Build description object from product.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array
	 */
	private function build_description( \WC_Product $product ): array {
		$short = $product->get_short_description();
		$full  = $product->get_description();

		// Entities would reach the agent literally, so decode after stripping tags.
		$plain = html_entity_decode( wp_strip_all_tags( '' !== $short ? $short : $full ), ENT_QUOTES, get_bloginfo( 'charset' ) );

		// Decoded `&nbsp;` is U+00A0. Agents match on this text, so normalize it to a
		// plain space rather than leaving a character that breaks string comparison.
		$plain = str_replace( "\xc2\xa0", ' ', $plain );

		return array(
			'plain' => trim( $plain ),
		);
	}

	/**
	 * Build price range from product.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param string      $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_price_range( \WC_Product $product, string $currency ): array {
		if ( $product instanceof \WC_Product_Variable ) {
			$prices     = $product->get_variation_prices( true );
			$price_list = isset( $prices['price'] ) && is_array( $prices['price'] ) ? $prices['price'] : array();

			if ( ! empty( $price_list ) ) {
				return array(
					'min' => $this->price( min( $price_list ), $currency ),
					'max' => $this->price( max( $price_list ), $currency ),
				);
			}
		}

		$price = $product->get_price();

		return array(
			'min' => $this->price( $price, $currency ),
			'max' => $this->price( $price, $currency ),
		);
	}

	/**
	 * Build list price range (sale price comparison) from product.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param string      $currency ISO 4217 currency code.
	 * @return array|null
	 */
	private function build_list_price_range( \WC_Product $product, string $currency ) {
		if ( $product instanceof \WC_Product_Variable ) {
			$prices       = $product->get_variation_prices( true );
			$regular_list = isset( $prices['regular_price'] ) && is_array( $prices['regular_price'] ) ? $prices['regular_price'] : array();
			$price_list   = isset( $prices['price'] ) && is_array( $prices['price'] ) ? $prices['price'] : array();

			if ( empty( $regular_list ) ) {
				return null;
			}

			// Compare against the effective prices so expired or scheduled sales are excluded.
			$has_discount = false;
			foreach ( $regular_list as $variation_id => $regular_price ) {
				if ( isset( $price_list[ $variation_id ] ) && (float) $regular_price > (float) $price_list[ $variation_id ] ) {
					$has_discount = true;
					break;
				}
			}

			if ( ! $has_discount ) {
				return null;
			}

			return array(
				'min' => $this->price( min( $regular_list ), $currency ),
				'max' => $this->price( max( $regular_list ), $currency ),
			);
		}

		$list_price = $this->build_variant_list_price( $product, $currency );

		return null === $list_price ? null : array(
			'min' => $list_price,
			'max' => $list_price,
		);
	}

	/**
	 * Build variants from product, bounded by the per-product cap.
	 *
	 * The loop stops at the cap rather than building everything and slicing, so
	 * variations past it are never loaded. Named variations go first, so the cap
	 * can only drop variants nobody asked for by id.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param string      $currency ISO 4217 currency code.
	 * @param array       $required_variation_ids Variation ids the request named directly.
	 * @param bool        $only_required Build nothing beyond $required_variation_ids.
	 * @return array
	 */
	private function build_variants( \WC_Product $product, string $currency, array $required_variation_ids = array(), bool $only_required = false ): array {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return array( $this->build_simple_variant( $product, $currency ) );
		}

		$children = array_map( 'intval', $product->get_visible_children() );
		$required = array_values( array_intersect( $children, array_map( 'intval', $required_variation_ids ) ) );

		if ( $only_required && ! empty( $required ) ) {
			// Bounded by the request's own id cap; nothing left out, nothing to
			// disclose.
			$variants              = $this->build_variation_list( $required, $currency );
			$this->variant_budget -= count( $variants );

			return $variants;
		}

		$ordered = array_merge( $required, array_values( array_diff( $children, $required ) ) );

		// The floor keeps named variations and at least one variant: an empty
		// `variants` array would violate the schema's `minItems: 1`.
		$floor    = max( 1, count( $required ) );
		$cap      = max( $floor, min( self::MAX_VARIANTS_PER_PRODUCT, $this->variant_budget ) );
		$variants = $this->build_variation_list( $ordered, $currency, $cap );

		$this->variant_budget -= count( $variants );

		$this->record_truncation( $product, count( $variants ) );

		return $variants;
	}

	/**
	 * Hydrate and transform variations in order, stopping at an optional limit.
	 *
	 * The limit is applied while iterating rather than by slicing afterwards, so
	 * variations past it are never loaded.
	 *
	 * @param array    $variation_ids Variation ids, in the order to build them.
	 * @param string   $currency ISO 4217 currency code.
	 * @param int|null $limit Maximum variants to build, or null for no limit.
	 * @return array
	 */
	private function build_variation_list( array $variation_ids, string $currency, ?int $limit = null ): array {
		$variants = array();

		foreach ( $variation_ids as $variation_id ) {
			if ( null !== $limit && count( $variants ) >= $limit ) {
				break;
			}

			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			$variants[] = $this->build_variation_variant( $variation, $currency );
		}

		return $variants;
	}

	/**
	 * Note that a product's variant list came back short, for the caller to report.
	 *
	 * @param \WC_Product_Variable $product WooCommerce variable product.
	 * @param int                  $returned Number of variants actually built.
	 */
	private function record_truncation( \WC_Product_Variable $product, int $returned ): void {
		$total = count( $product->get_visible_children() );

		if ( $returned >= $total ) {
			return;
		}

		$this->truncations[ (int) $product->get_id() ] = array(
			'title'    => $product->get_name(),
			'returned' => $returned,
			'total'    => $total,
		);
	}

	/**
	 * Build a variant entry from a simple product.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param string      $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_simple_variant( \WC_Product $product, string $currency ): array {
		$variant = array(
			'id'           => (string) $product->get_id(),
			'title'        => $product->get_name(),
			'description'  => $this->build_description( $product ),
			'price'        => $this->price( $product->get_price(), $currency ),
			'availability' => array( 'available' => $product->is_purchasable() && $product->is_in_stock() ),
		);

		$sku = $product->get_sku();
		if ( '' !== $sku ) {
			$variant['sku'] = $sku;
		}

		$list_price = $this->build_variant_list_price( $product, $currency );
		if ( null !== $list_price ) {
			$variant['list_price'] = $list_price;
		}

		return $variant;
	}

	/**
	 * Build a variant entry from a product variation.
	 *
	 * @param \WC_Product_Variation $variation WooCommerce variation.
	 * @param string                $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_variation_variant( \WC_Product_Variation $variation, string $currency ): array {
		$attributes = $variation->get_attributes();

		$option_labels = array();
		foreach ( $attributes as $value ) {
			if ( '' !== (string) $value ) {
				$option_labels[] = (string) $value;
			}
		}

		$variant = array(
			'id'           => (string) $variation->get_id(),
			'title'        => empty( $option_labels ) ? $variation->get_name() : implode( ' / ', $option_labels ),
			'description'  => $this->build_description( $variation ),
			'price'        => $this->price( $variation->get_price(), $currency ),
			'availability' => array( 'available' => $variation->is_purchasable() && $variation->is_in_stock() ),
		);

		$sku = $variation->get_sku();
		if ( '' !== $sku ) {
			$variant['sku'] = $sku;
		}

		$list_price = $this->build_variant_list_price( $variation, $currency );
		if ( null !== $list_price ) {
			$variant['list_price'] = $list_price;
		}

		$options = $this->build_variation_options( $variation );
		if ( ! empty( $options ) ) {
			$variant['options'] = $options;
		}

		$media = $this->build_image_media( (int) $variation->get_image_id() );
		if ( ! empty( $media ) ) {
			$variant['media'] = $media;
		}

		return $variant;
	}

	/**
	 * Build variant list price for sale comparison.
	 *
	 * @param \WC_Product $product WooCommerce product or variation.
	 * @param string      $currency ISO 4217 currency code.
	 * @return array|null
	 */
	private function build_variant_list_price( \WC_Product $product, string $currency ) {
		$regular_price = $product->get_regular_price();

		// Compare against the effective price so expired or scheduled sales are excluded.
		if ( '' === $regular_price || (float) $regular_price <= (float) $product->get_price() ) {
			return null;
		}

		return $this->price( $regular_price, $currency );
	}

	/**
	 * Build selected options from a variation's attributes.
	 *
	 * @param \WC_Product_Variation $variation WooCommerce variation.
	 * @return array
	 */
	private function build_variation_options( \WC_Product_Variation $variation ): array {
		$options = array();

		foreach ( $variation->get_attributes() as $attribute_name => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}

			$options[] = array(
				'name'  => $this->option_name( (string) $attribute_name ),
				'label' => (string) $value,
			);
		}

		return $options;
	}

	/**
	 * Turn a WooCommerce attribute key into a UCP option name.
	 *
	 * @param string $attribute_name Attribute key, e.g. `pa_shirt-size`.
	 * @return string
	 */
	private function option_name( string $attribute_name ): string {
		return ucfirst( str_replace( array( '-', '_' ), ' ', str_replace( 'pa_', '', $attribute_name ) ) );
	}

	/**
	 * Build a one-entry media list from an attachment id.
	 *
	 * @param int $image_id Attachment id, or 0 for none.
	 * @return array
	 */
	private function build_image_media( int $image_id ): array {
		if ( $image_id <= 0 ) {
			return array();
		}

		$url = wp_get_attachment_url( $image_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return array();
		}

		$media = array(
			'type' => 'image',
			'url'  => $url,
		);

		$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		if ( is_string( $alt ) && '' !== $alt ) {
			$media['alt_text'] = $alt;
		}

		return array( $media );
	}

	/**
	 * Build product categories.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array
	 */
	private function build_categories( \WC_Product $product ): array {
		$categories = array();

		foreach ( array_slice( $product->get_category_ids(), 0, self::MAX_TERMS_PER_PRODUCT ) as $term_id ) {
			$term = get_term( $term_id, 'product_cat' );
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$categories[] = array(
				'id'    => (string) $term->term_id,
				'name'  => $term->name,
				'value' => $term->slug,
			);
		}

		return $categories;
	}

	/**
	 * Build product media from the featured image and gallery.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array
	 */
	private function build_media( \WC_Product $product ): array {
		$media = $this->build_image_media( (int) $product->get_image_id() );

		// The featured image is already in $media, hence the remaining room.
		$gallery_ids = array_slice(
			$product->get_gallery_image_ids(),
			0,
			max( 0, self::MAX_MEDIA_PER_PRODUCT - count( $media ) )
		);

		foreach ( $gallery_ids as $gallery_id ) {
			$media = array_merge( $media, $this->build_image_media( (int) $gallery_id ) );
		}

		return $media;
	}

	/**
	 * Build product options for variable products.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array
	 */
	private function build_options( \WC_Product $product ): array {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return array();
		}

		$options = array();

		foreach ( $product->get_variation_attributes() as $attribute_name => $values ) {
			$option_values = array();
			foreach ( $values as $value ) {
				$option_values[] = array( 'label' => (string) $value );
			}

			if ( ! empty( $option_values ) ) {
				$options[] = array(
					'name'   => $this->option_name( (string) $attribute_name ),
					'values' => $option_values,
				);
			}
		}

		return $options;
	}

	/**
	 * Build product rating.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array|null
	 */
	private function build_rating( \WC_Product $product ) {
		$average = (float) $product->get_average_rating();
		if ( $average <= 0 ) {
			return null;
		}

		$rating = array(
			'value'     => $average,
			'scale_min' => 1,
			'scale_max' => 5,
		);

		$count = (int) $product->get_review_count();
		if ( $count > 0 ) {
			$rating['count'] = $count;
		}

		return $rating;
	}

	/**
	 * Build product tags.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array
	 */
	private function build_tags( \WC_Product $product ): array {
		$tags = array();

		foreach ( array_slice( $product->get_tag_ids(), 0, self::MAX_TERMS_PER_PRODUCT ) as $term_id ) {
			$term = get_term( $term_id, 'product_tag' );
			if ( $term instanceof \WP_Term ) {
				$tags[] = $term->name;
			}
		}

		return $tags;
	}

	/**
	 * Filter variants by selected option values.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @param array                $selected Selected option values.
	 * @param string               $currency ISO 4217 currency code.
	 * @return array
	 */
	private function filter_variants_by_selection( \WC_Product_Variable $product, array $selected, string $currency ): array {
		$selection_map = array();
		foreach ( $selected as $option ) {
			if ( isset( $option['name'], $option['label'] ) ) {
				$selection_map[ strtolower( (string) $option['name'] ) ] = strtolower( (string) $option['label'] );
			}
		}

		if ( empty( $selection_map ) ) {
			return array();
		}

		$variants = array();

		foreach ( $product->get_visible_children() as $child_id ) {
			// Same output bound as build_variants(). Matching is on hydrated
			// attributes, so a selection matching nothing still walks every
			// child — bounding that needs the match pushed into the query.
			if ( count( $variants ) >= self::MAX_VARIANTS_PER_PRODUCT ) {
				$this->record_truncation( $product, count( $variants ) );
				break;
			}

			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			if ( $this->variation_matches_selection( $variation, $selection_map ) ) {
				$variants[] = $this->build_variation_variant( $variation, $currency );
			}
		}

		return $variants;
	}

	/**
	 * Whether a variation carries every selected option value.
	 *
	 * @param \WC_Product_Variation $variation WooCommerce variation.
	 * @param array                 $selection_map Lowercased option name => lowercased label.
	 * @return bool
	 */
	private function variation_matches_selection( \WC_Product_Variation $variation, array $selection_map ): bool {
		$attributes = $variation->get_attributes();

		foreach ( $selection_map as $name => $label ) {
			$found = false;

			foreach ( $attributes as $attr_name => $attr_value ) {
				$clean = strtolower( str_replace( array( 'pa_', '-', '_' ), array( '', ' ', ' ' ), (string) $attr_name ) );
				if ( $clean === $name && strtolower( (string) $attr_value ) === $label ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		}

		return true;
	}
}
