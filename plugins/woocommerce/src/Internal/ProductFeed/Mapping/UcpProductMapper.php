<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductFeed\Mapping;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp\UcpProductSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Maps WooCommerce products to the UCP catalog product shape.
 *
 * Field values are read from Store API product item responses, so tax display,
 * password protection, description formatting and attribute labels follow the
 * storefront. Instances carry per-response state (variant budget, truncation
 * records), so a caller assembling one response reuses one instance and calls
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
	 * Store API product schema the item responses are read from.
	 *
	 * @var ProductSchema|null
	 */
	private $schema;

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
	 * @param string             $currency ISO 4217 currency code; '' uses the store currency.
	 * @param ProductSchema|null $schema Product schema; null resolves the UCP product schema.
	 */
	public function __construct( string $currency = '', ?ProductSchema $schema = null ) {
		$this->currency = $currency;
		$this->schema   = $schema;
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
		return $this->map_item( $product, $this->item( $product ), $required_variation_ids, $only_required );
	}

	/**
	 * Map a product for `POST /catalog/lookup`.
	 *
	 * Every returned variant must be justified by a request identifier via the
	 * required `inputs` metadata. A product-level identifier resolves to all of
	 * the product's variants as `featured` — server-selected representatives —
	 * while an identifier naming a variant directly by id resolves
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

			// A product-level identifier needs the full variant list.
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
				// The resolved variation decides the match: the raw id may carry
				// leading zeros the resolver already normalized.
				$names_variant = $input['variation_id'] > 0;

				if ( $names_variant && (int) $input['variation_id'] !== (int) $variant['id'] ) {
					continue;
				}

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
	 * A selection narrows the variants to those matching every selected option.
	 * One matching no variation is relaxed from its last option until one does,
	 * and `selected` reports the options kept, as UCP requires.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param array       $selected Selected option values, each `{ name, label, id? }`.
	 * @return array
	 */
	public function map_detail_product( \WC_Product $product, array $selected = array() ): array {
		if ( empty( $selected ) || ! $product instanceof \WC_Product_Variable ) {
			return $this->map_product( $product );
		}

		$item     = $this->item( $product );
		$matching = array();

		while ( ! empty( $selected ) ) {
			$matching = $this->match_selection( $item, $selected );

			if ( ! empty( $matching ) ) {
				break;
			}

			array_pop( $selected );
		}

		$mapped             = $this->map_item( $product, $item, array(), false, empty( $matching ) ? null : $matching );
		$mapped['selected'] = $selected;

		return $mapped;
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
	 * Get the product schema, resolving the UCP one from the Store API container on first use.
	 *
	 * @return ProductSchema
	 */
	private function schema(): ProductSchema {
		if ( null === $this->schema ) {
			$this->schema = StoreApi::container()->get( SchemaController::class )->get( UcpProductSchema::IDENTIFIER );
		}

		return $this->schema;
	}

	/**
	 * Build the Store API item response for a product or variation, as nested arrays.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array
	 */
	private function item( \WC_Product $product ): array {
		// Nested values are stdClass; decode through JSON for uniform array access.
		return (array) json_decode( (string) wp_json_encode( $this->schema()->get_item_response( $product ) ), true );
	}

	/**
	 * Map a product from its item response.
	 *
	 * @param \WC_Product $product The product to map.
	 * @param array       $item Its Store API item response.
	 * @param array       $required_variation_ids Variation ids the per-product cap must not drop.
	 * @param bool        $only_required Build only the named variations.
	 * @param array|null  $candidates Variation ids to build from, or null for every visible one.
	 * @return array
	 */
	private function map_item( \WC_Product $product, array $item, array $required_variation_ids, bool $only_required, ?array $candidates = null ): array {
		$currency = $this->get_currency();
		$variants = $this->build_variants( $product, $item, $currency, $required_variation_ids, $only_required, $candidates );

		$mapped = array(
			'id'          => (string) $item['id'],
			'title'       => (string) $item['name'],
			'description' => $this->build_description( $item ),
			'price_range' => $this->build_price_range( $item['prices'], $currency ),
			'variants'    => $variants,
		);

		$handle = (string) $item['slug'];
		if ( '' !== $handle ) {
			$mapped['handle'] = $handle;
		}

		$url = $item['permalink'];
		if ( is_string( $url ) && '' !== $url ) {
			$mapped['url'] = $url;
		}

		$categories = $this->build_categories( (array) $item['categories'] );
		if ( ! empty( $categories ) ) {
			$mapped['categories'] = $categories;
		}

		$media = $this->build_media( (array) $item['images'] );
		if ( ! empty( $media ) ) {
			$mapped['media'] = $media;
		}

		$options = $this->build_options( $item );
		if ( ! empty( $options ) ) {
			$mapped['options'] = $options;
		}

		$list_price_range = $this->build_list_price_range( $variants );
		if ( null !== $list_price_range ) {
			$mapped['list_price_range'] = $list_price_range;
		}

		$rating = $this->build_rating( $item );
		if ( null !== $rating ) {
			$mapped['rating'] = $rating;
		}

		$tags = $this->build_tags( (array) $item['tags'] );
		if ( ! empty( $tags ) ) {
			$mapped['tags'] = $tags;
		}

		return $mapped;
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
	 * Build a `price` object from a Store API minor-unit amount.
	 *
	 * @param mixed  $amount Amount in minor units, as the Store API formats it.
	 * @param string $currency ISO 4217 currency code.
	 * @return array
	 */
	private function price( $amount, string $currency ): array {
		return array(
			'amount'   => (int) $amount,
			'currency' => $currency,
		);
	}

	/**
	 * Build the description object, preferring the short description.
	 *
	 * @param array $item Store API item response.
	 * @return array
	 */
	private function build_description( array $item ): array {
		$html = trim( (string) $item['short_description'] );
		if ( '' === $html ) {
			$html = trim( (string) $item['description'] );
		}

		// Entities would reach the agent literally, so decode after stripping tags.
		$plain = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, get_bloginfo( 'charset' ) );

		// Decoded `&nbsp;` is U+00A0. Agents match on this text, so normalize it to a
		// plain space rather than leaving a character that breaks string comparison.
		$plain = str_replace( "\xc2\xa0", ' ', $plain );

		$description = array( 'plain' => trim( $plain ) );

		if ( '' !== $html ) {
			$description['html'] = $html;
		}

		return $description;
	}

	/**
	 * Build the price range from Store API prices.
	 *
	 * `price_range` is only set for variable products whose variations differ in
	 * price; everything else is a single price.
	 *
	 * @param array  $prices Store API `prices` data.
	 * @param string $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_price_range( $prices, string $currency ): array {
		$range = $prices['price_range'] ?? null;

		if ( is_array( $range ) ) {
			return array(
				'min' => $this->price( $range['min_amount'], $currency ),
				'max' => $this->price( $range['max_amount'], $currency ),
			);
		}

		return array(
			'min' => $this->price( $prices['price'], $currency ),
			'max' => $this->price( $prices['price'], $currency ),
		);
	}

	/**
	 * Build the list price range from the variants built for the product.
	 *
	 * Spans every variant's regular price, but only when some variant is genuinely
	 * discounted; a variant carries `list_price` only in that case.
	 *
	 * @param array $variants Built variants.
	 * @return array|null
	 */
	private function build_list_price_range( array $variants ) {
		$has_discount = false;
		$regular      = array();

		foreach ( $variants as $variant ) {
			if ( isset( $variant['list_price'] ) ) {
				$has_discount = true;
				$regular[]    = $variant['list_price']['amount'];
			} else {
				$regular[] = $variant['price']['amount'];
			}
		}

		if ( ! $has_discount || empty( $regular ) ) {
			return null;
		}

		$currency = $variants[0]['price']['currency'];

		return array(
			'min' => $this->price( min( $regular ), $currency ),
			'max' => $this->price( max( $regular ), $currency ),
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
	 * @param array       $item Its Store API item response.
	 * @param string      $currency ISO 4217 currency code.
	 * @param array       $required_variation_ids Variation ids the request named directly.
	 * @param bool        $only_required Build nothing beyond $required_variation_ids.
	 * @param array|null  $candidates Variation ids to build from, or null for every visible one.
	 * @return array
	 */
	private function build_variants( \WC_Product $product, array $item, string $currency, array $required_variation_ids, bool $only_required, ?array $candidates ): array {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return array( $this->build_simple_variant( $item, $currency ) );
		}

		$children = array_map( 'intval', null === $candidates ? $product->get_visible_children() : $candidates );
		$required = array_values( array_intersect( $children, array_map( 'intval', $required_variation_ids ) ) );
		$options  = $this->variation_options_by_id( $item );

		if ( $only_required && ! empty( $required ) ) {
			// Bounded by the request's own id cap; nothing left out, nothing to
			// disclose.
			$variants              = $this->build_variation_list( $required, $options, $currency );
			$this->variant_budget -= count( $variants );

			return $variants;
		}

		$required_first = array_merge( $required, array_values( array_diff( $children, $required ) ) );

		// The floor keeps named variations and at least one variant: an empty
		// `variants` array would violate the schema's `minItems: 1`.
		$floor    = max( 1, count( $required ) );
		$cap      = max( $floor, min( self::MAX_VARIANTS_PER_PRODUCT, $this->variant_budget ) );
		$variants = $this->build_variation_list( $required_first, $options, $currency, $cap );

		$this->variant_budget -= count( $variants );

		$this->record_truncation( $item, count( $variants ), count( $children ) );

		return $variants;
	}

	/**
	 * Hydrate and transform variations in order, stopping at an optional limit.
	 *
	 * The limit is applied while iterating rather than by slicing afterwards, so
	 * variations past it are never loaded.
	 *
	 * @param array    $variation_ids Variation ids, in the order to build them.
	 * @param array    $options Selected options keyed by variation id, from the parent item.
	 * @param string   $currency ISO 4217 currency code.
	 * @param int|null $limit Maximum variants to build, or null for no limit.
	 * @return array
	 */
	private function build_variation_list( array $variation_ids, array $options, string $currency, ?int $limit = null ): array {
		$variants = array();

		foreach ( $variation_ids as $variation_id ) {
			if ( null !== $limit && count( $variants ) >= $limit ) {
				break;
			}

			$variation = wc_get_product( $variation_id );
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}

			$variants[] = $this->build_variation_variant( $this->item( $variation ), $options[ (int) $variation_id ] ?? array(), $currency );
		}

		return $variants;
	}

	/**
	 * Note that a product's variant list came back short, for the caller to report.
	 *
	 * @param array $item Store API item response of the variable product.
	 * @param int   $returned Number of variants actually built.
	 * @param int   $total Number of variants there were to build.
	 */
	private function record_truncation( array $item, int $returned, int $total ): void {
		if ( $returned >= $total ) {
			return;
		}

		$this->truncations[ (int) $item['id'] ] = array(
			'title'    => (string) $item['name'],
			'returned' => $returned,
			'total'    => $total,
		);
	}

	/**
	 * Build a variant entry from a simple product's item response.
	 *
	 * @param array  $item Store API item response.
	 * @param string $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_simple_variant( array $item, string $currency ): array {
		$variant = array(
			'id'           => (string) $item['id'],
			'title'        => (string) $item['name'],
			'description'  => $this->build_description( $item ),
			'price'        => $this->price( $item['prices']['price'], $currency ),
			'availability' => array( 'available' => $item['is_purchasable'] && $item['is_in_stock'] ),
		);

		$sku = (string) $item['sku'];
		if ( '' !== $sku ) {
			$variant['sku'] = $sku;
		}

		$list_price = $this->build_variant_list_price( $item['prices'], $currency );
		if ( null !== $list_price ) {
			$variant['list_price'] = $list_price;
		}

		return $variant;
	}

	/**
	 * Build a variant entry from a variation's item response.
	 *
	 * @param array  $item Store API item response of the variation.
	 * @param array  $options Selected options, each `{ name, label }`.
	 * @param string $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_variation_variant( array $item, array $options, string $currency ): array {
		$labels = array_column( $options, 'label' );

		$variant = array(
			'id'           => (string) $item['id'],
			'title'        => empty( $labels ) ? (string) $item['name'] : implode( ' / ', $labels ),
			'description'  => $this->build_description( $item ),
			'price'        => $this->price( $item['prices']['price'], $currency ),
			'availability' => array( 'available' => $item['is_purchasable'] && $item['is_in_stock'] ),
		);

		$sku = (string) $item['sku'];
		if ( '' !== $sku ) {
			$variant['sku'] = $sku;
		}

		$list_price = $this->build_variant_list_price( $item['prices'], $currency );
		if ( null !== $list_price ) {
			$variant['list_price'] = $list_price;
		}

		if ( ! empty( $options ) ) {
			$variant['options'] = $options;
		}

		$media = $this->build_media( array_slice( (array) $item['images'], 0, 1 ) );
		if ( ! empty( $media ) ) {
			$variant['media'] = $media;
		}

		return $variant;
	}

	/**
	 * Build a variant's list price when it is genuinely discounted.
	 *
	 * Compares against the effective price so expired or scheduled sales are excluded.
	 *
	 * @param array  $prices Store API `prices` data.
	 * @param string $currency ISO 4217 currency code.
	 * @return array|null
	 */
	private function build_variant_list_price( $prices, string $currency ) {
		if ( (int) $prices['regular_price'] <= (int) $prices['price'] ) {
			return null;
		}

		return $this->price( $prices['regular_price'], $currency );
	}

	/**
	 * Index term names by slug for each variation attribute of a product.
	 *
	 * @param array $item Store API item response of the variable product.
	 * @return array Attribute label => [ slug => term name ].
	 */
	private function term_names( array $item ): array {
		$names = array();

		foreach ( (array) $item['attributes'] as $attribute ) {
			if ( empty( $attribute['has_variations'] ) ) {
				continue;
			}

			foreach ( (array) $attribute['terms'] as $term ) {
				$names[ (string) $attribute['name'] ][ (string) $term['slug'] ] = (string) $term['name'];
			}
		}

		return $names;
	}

	/**
	 * Build each visible variation's selected options from the parent item.
	 *
	 * An attribute left as "Any" on a variation has a null value and is omitted.
	 *
	 * @param array $item Store API item response of the variable product.
	 * @return array Variation id => list of `{ name, label }`.
	 */
	private function variation_options_by_id( array $item ): array {
		$names   = $this->term_names( $item );
		$options = array();

		foreach ( (array) $item['variations'] as $variation ) {
			$selected = array();

			foreach ( (array) $variation['attributes'] as $attribute ) {
				if ( null === $attribute['value'] || '' === (string) $attribute['value'] ) {
					continue;
				}

				$name       = (string) $attribute['name'];
				$value      = (string) $attribute['value'];
				$selected[] = array(
					'name'  => $name,
					'label' => $names[ $name ][ $value ] ?? $value,
				);
			}

			$options[ (int) $variation['id'] ] = $selected;
		}

		return $options;
	}

	/**
	 * Build product options for variable products.
	 *
	 * Lists the values the visible variations actually use, in the attribute's
	 * own term order; an "Any" variation offers every term.
	 *
	 * @param array $item Store API item response.
	 * @return array
	 */
	private function build_options( array $item ): array {
		$variations = (array) $item['variations'];
		if ( empty( $variations ) ) {
			return array();
		}

		$used = array();
		$any  = array();
		foreach ( $variations as $variation ) {
			foreach ( (array) $variation['attributes'] as $attribute ) {
				$name = (string) $attribute['name'];
				if ( null === $attribute['value'] || '' === (string) $attribute['value'] ) {
					$any[ $name ] = true;
					continue;
				}
				$used[ $name ][ (string) $attribute['value'] ] = true;
			}
		}

		$options = array();

		foreach ( (array) $item['attributes'] as $attribute ) {
			if ( empty( $attribute['has_variations'] ) ) {
				continue;
			}

			$name   = (string) $attribute['name'];
			$values = array();

			foreach ( (array) $attribute['terms'] as $term ) {
				if ( isset( $any[ $name ] ) || isset( $used[ $name ][ (string) $term['slug'] ] ) ) {
					$values[] = array( 'label' => (string) $term['name'] );
				}
			}

			if ( ! empty( $values ) ) {
				$options[] = array(
					'name'   => $name,
					'values' => $values,
				);
			}
		}

		return $options;
	}

	/**
	 * Build media entries from Store API images.
	 *
	 * @param array $images Store API image objects.
	 * @return array
	 */
	private function build_media( array $images ): array {
		$media = array();

		foreach ( array_slice( $images, 0, self::MAX_MEDIA_PER_PRODUCT ) as $image ) {
			$url = $image['src'] ?? '';
			if ( ! is_string( $url ) || '' === $url ) {
				continue;
			}

			$entry = array(
				'type' => 'image',
				'url'  => $url,
			);

			if ( is_string( $image['alt'] ) && '' !== $image['alt'] ) {
				$entry['alt_text'] = $image['alt'];
			}

			$media[] = $entry;
		}

		return $media;
	}

	/**
	 * Build product categories from Store API terms.
	 *
	 * @param array $terms Store API category objects.
	 * @return array
	 */
	private function build_categories( array $terms ): array {
		$categories = array();

		foreach ( array_slice( $terms, 0, self::MAX_TERMS_PER_PRODUCT ) as $term ) {
			$categories[] = array(
				'id'    => (string) $term['id'],
				'name'  => (string) $term['name'],
				'value' => (string) $term['slug'],
			);
		}

		return $categories;
	}

	/**
	 * Build product rating.
	 *
	 * @param array $item Store API item response.
	 * @return array|null
	 */
	private function build_rating( array $item ) {
		$average = (float) $item['average_rating'];
		if ( $average <= 0 ) {
			return null;
		}

		$rating = array(
			'value'     => $average,
			'scale_min' => 1,
			'scale_max' => 5,
		);

		$count = (int) $item['review_count'];
		if ( $count > 0 ) {
			$rating['count'] = $count;
		}

		return $rating;
	}

	/**
	 * Build product tags from Store API terms.
	 *
	 * @param array $terms Store API tag objects.
	 * @return array
	 */
	private function build_tags( array $terms ): array {
		$tags = array();

		foreach ( array_slice( $terms, 0, self::MAX_TERMS_PER_PRODUCT ) as $term ) {
			$tags[] = (string) $term['name'];
		}

		return $tags;
	}

	/**
	 * Find the visible variations carrying every selected option value.
	 *
	 * Matches on the parent item's variation attributes, so no variation is loaded
	 * to be rejected. A label matches a term's name or its slug, case-insensitively.
	 *
	 * @param array $item Store API item response of the variable product.
	 * @param array $selected Selected option values, each `{ name, label }`.
	 * @return array Matching variation ids.
	 */
	private function match_selection( array $item, array $selected ): array {
		$selection = array();
		foreach ( $selected as $option ) {
			if ( isset( $option['name'], $option['label'] ) ) {
				$selection[ strtolower( (string) $option['name'] ) ] = strtolower( (string) $option['label'] );
			}
		}

		if ( empty( $selection ) ) {
			return array();
		}

		$names    = $this->term_names( $item );
		$matching = array();

		foreach ( (array) $item['variations'] as $variation ) {
			$values = array();
			foreach ( (array) $variation['attributes'] as $attribute ) {
				if ( null === $attribute['value'] || '' === (string) $attribute['value'] ) {
					continue;
				}

				$name  = (string) $attribute['name'];
				$value = (string) $attribute['value'];

				$values[ strtolower( $name ) ] = array(
					strtolower( $value ),
					strtolower( $names[ $name ][ $value ] ?? $value ),
				);
			}

			foreach ( $selection as $name => $label ) {
				if ( ! isset( $values[ $name ] ) || ! in_array( $label, $values[ $name ], true ) ) {
					continue 2;
				}
			}

			$matching[] = (int) $variation['id'];
		}

		return $matching;
	}
}
