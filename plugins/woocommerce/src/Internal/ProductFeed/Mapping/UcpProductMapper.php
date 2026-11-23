<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductFeed\Mapping;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp\UcpProductSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\Internal\ProductVariations\VariationMatcher;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

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
	 *                            each `{ id, variant_id }` with the variant it named, or '' for
	 *                            the product itself.
	 * @return array
	 */
	public function map_lookup_product( \WC_Product $product, array $inputs ): array {
		// A variation named by id must survive the per-product cap.
		$required      = array();
		$names_product = false;
		foreach ( $inputs as $input ) {
			if ( '' !== $input['variant_id'] ) {
				$required[] = $input['variant_id'];
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
				// The resolved id decides the match: the raw id may carry leading
				// zeros the resolver already normalized.
				$names_variant = '' !== $input['variant_id'];
				$exact         = $input['variant_id'] === $variant['id'];

				// A bare variation id stands for every value of its "Any" attributes.
				if ( $names_variant && ! $exact && 0 !== strpos( $variant['id'], $input['variant_id'] . ':' ) ) {
					continue;
				}

				$correlations[] = array(
					'id'    => $input['id'],
					'match' => $exact ? 'exact' : 'featured',
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
	 * and `selected` reports the options kept, as UCP requires. A request naming
	 * a variation returns it first, with its own options as the selection.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param array       $selected Selected option values, each `{ name, label, id? }`.
	 * @param string      $variant_id Variant the request named, or '' when it named the product.
	 * @return array
	 */
	public function map_detail_product( \WC_Product $product, array $selected = array(), string $variant_id = '' ): array {
		if ( ! $product instanceof \WC_Product_Variable || ( empty( $selected ) && '' === $variant_id ) ) {
			return $this->map_product( $product );
		}

		$item                      = $this->item( $product );
		list( $axes, $attributes ) = $this->variation_data( $product, $item );

		// UCP: a variant id fully determines the selection state, so the
		// request's `selected` is ignored rather than merged.
		if ( '' !== $variant_id ) {
			list( $variation_id, $values ) = Utils::parse_variant_id( $variant_id );
			$selected                      = $this->options( array_diff( array_merge( $attributes[ $variation_id ] ?? array(), $values ), array( '' ) ), $axes );
		}

		$matching = array();

		while ( ! empty( $selected ) ) {
			$matching = $this->match_selection( $attributes, $this->selected_values( $selected, $axes ) );

			if ( ! empty( $matching ) ) {
				break;
			}

			array_pop( $selected );
		}

		$required           = '' !== $variant_id ? array( $variant_id ) : array();
		$mapped             = $this->map_item( $product, $item, $required, false, empty( $matching ) ? null : $matching, (array) $this->selected_values( $selected, $axes ) );
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
	 * @param array       $required_variation_ids Variant ids the per-product cap must not drop.
	 * @param bool        $only_required Build only the named variations.
	 * @param array|null  $candidates Variation ids to build from, or null for every visible one.
	 * @param array       $selected Selected values by attribute key, narrowing "Any" attributes.
	 * @return array
	 */
	private function map_item( \WC_Product $product, array $item, array $required_variation_ids, bool $only_required, ?array $candidates = null, array $selected = array() ): array {
		$currency = $this->get_currency();
		$variants = $this->build_variants( $product, $item, $currency, $required_variation_ids, $only_required, $candidates, $selected );

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
	 * A variation leaving attributes as "Any" yields one variant per value it can be
	 * sold with. Variants are listed lazily and the loop stops at the cap, so nothing
	 * past it is built. Named variants go first, so the cap can only drop variants
	 * nobody asked for by id.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param array       $item Its Store API item response.
	 * @param string      $currency ISO 4217 currency code.
	 * @param array       $required_variant_ids Variant ids the request named directly.
	 * @param bool        $only_required Build nothing beyond what the request named.
	 * @param array|null  $candidates Variation ids to build from, or null for every visible one.
	 * @param array       $selected Selected values by attribute key, narrowing "Any" attributes.
	 * @return array
	 */
	private function build_variants( \WC_Product $product, array $item, string $currency, array $required_variant_ids, bool $only_required, ?array $candidates, array $selected = array() ): array {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return array( $this->build_simple_variant( $product, $item, $currency ) );
		}

		list( $axes, $attributes ) = $this->variation_data( $product, $item );
		if ( null !== $candidates ) {
			$attributes = array_intersect_key( $attributes, array_flip( $candidates ) );
		}

		// Only an "Any" variation can lose values to an earlier one, so only a product with one pays for the cart's rules.
		$matcher = wc_get_container()->get( VariationMatcher::class );
		$earlier = array_filter( $attributes, static fn( $own ) => in_array( '', $own, true ) )
			? $matcher->get_variation_attributes( $product )
			: array();
		$specs   = array();
		$stream  = array();

		// Availability loads the variation and runs the storefront's filters, so it is asked once, and only of a variation about to take a value.
		$checked   = array();
		$available = static function ( int $id ) use ( &$checked, $matcher, $product ): bool {
			if ( ! isset( $checked[ $id ] ) ) {
				$variation      = wc_get_product( $id );
				$checked[ $id ] = $variation instanceof \WC_Product_Variation && $matcher->is_available( $variation, $product );
			}

			return $checked[ $id ];
		};

		foreach ( $required_variant_ids as $variant_id ) {
			list( $variation_id, $values ) = Utils::parse_variant_id( (string) $variant_id );

			foreach ( $this->variant_specs( $variation_id, $attributes[ $variation_id ] ?? null, $axes, $values + $selected, $earlier, $available ) as $spec ) {
				$specs[ $spec['id'] ] = $spec;
				break;
			}

			// A bare variation id leads with its first variant and stands for the others too.
			if ( empty( $values ) ) {
				$stream[] = $variation_id;
			}
		}

		if ( ! $only_required ) {
			$stream = array_merge( $stream, array_keys( $attributes ) );
		}

		// The floor keeps named variants and at least one variant: an empty
		// `variants` array would violate the schema's `minItems: 1`.
		$cap   = max( 1, count( $specs ), min( self::MAX_VARIANTS_PER_PRODUCT, $this->variant_budget ) );
		$total = 0;

		foreach ( array_unique( $stream ) as $variation_id ) {
			foreach ( $this->variant_specs( $variation_id, $attributes[ $variation_id ] ?? null, $axes, $selected, $earlier, $available ) as $spec ) {
				if ( isset( $specs[ $spec['id'] ] ) ) {
					continue;
				}

				if ( count( $specs ) >= $cap ) {
					// An upper bound, counted without listing: values an earlier variation takes are not netted out.
					foreach ( array_unique( $stream ) as $id ) {
						$sizes = array();
						foreach ( $attributes[ $id ] ?? array() as $key => $value ) {
							$sizes[] = '' === $value && ! isset( $selected[ $key ] ) ? count( $axes[ $key ]['terms'] ) : 1;
						}
						$total += (int) array_product( $sizes );
					}
					break 2;
				}

				$specs[ $spec['id'] ] = $spec;
			}
		}

		$variants = $this->build_variation_list( $specs, $currency );

		$this->variant_budget -= count( $variants );

		$this->record_truncation( $item, count( $variants ), 0 === $total ? 0 : max( $total, count( $variants ) + 1 ) );

		return $variants;
	}

	/**
	 * Hydrate and transform variants in order, loading each variation once.
	 *
	 * @param array  $specs Variants to build, each `{ id, variation_id, options }`.
	 * @param string $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_variation_list( array $specs, string $currency ): array {
		$variants = array();
		$loaded   = array();

		foreach ( $specs as $spec ) {
			$variation_id = $spec['variation_id'];

			if ( ! isset( $loaded[ $variation_id ] ) ) {
				$variation               = wc_get_product( $variation_id );
				$loaded[ $variation_id ] = $variation instanceof \WC_Product_Variation ? array( $variation, $this->item( $variation ) ) : array();
			}

			if ( empty( $loaded[ $variation_id ] ) ) {
				continue;
			}

			$variant       = $this->build_variation_variant( $loaded[ $variation_id ][0], $loaded[ $variation_id ][1], $spec['options'], $currency );
			$variant['id'] = $spec['id'];
			$variants[]    = $variant;
		}

		return $variants;
	}

	/**
	 * Read a variable product's variation attributes and each visible variation's values for them.
	 *
	 * @param \WC_Product $product The variable product.
	 * @param array       $item Its Store API item response.
	 * @return array{0: array<string, array{name: string, terms: array<string>}>, 1: array<int, array<string, string>>}
	 *         `{ name, terms }` by attribute key as the cart reads it, terms being names by slug; and
	 *         values by attribute key for each variation id, '' for "Any".
	 */
	private function variation_data( \WC_Product $product, array $item ): array {
		$names = $this->term_names( $item );
		$axes  = array();
		$keys  = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute && $attribute->get_variation() ) {
				$name          = wc_attribute_label( $attribute->get_name(), $product );
				$key           = wc_variation_attribute_name( $attribute->get_name() );
				$keys[ $name ] = $key;
				$axes[ $key ]  = array(
					'name'  => $name,
					'terms' => $names[ $name ] ?? array(),
				);
			}
		}

		$attributes = array();

		foreach ( (array) $item['variations'] as $variation ) {
			$attributes[ (int) $variation['id'] ] = array();

			foreach ( (array) $variation['attributes'] as $attribute ) {
				if ( isset( $keys[ $attribute['name'] ] ) ) {
					$attributes[ (int) $variation['id'] ][ $keys[ $attribute['name'] ] ] = (string) $attribute['value'];
				}
			}
		}

		return array( $axes, $attributes );
	}

	/**
	 * Present attribute values as options.
	 *
	 * @param array $values Values by attribute key.
	 * @param array $axes Variation attributes, from variation_data().
	 * @return array List of `{ name, label }`.
	 */
	private function options( array $values, array $axes ): array {
		$options = array();

		foreach ( $values as $key => $value ) {
			$options[] = array(
				'name'  => $axes[ $key ]['name'] ?? (string) $key,
				'label' => $axes[ $key ]['terms'][ $value ] ?? (string) $value,
			);
		}

		return $options;
	}

	/**
	 * Resolve selected options to attribute values, a label naming a term by its name or slug.
	 *
	 * @param array $selected Selected option values, each `{ name, label }`.
	 * @param array $axes Variation attributes, from variation_data().
	 * @return array|null Values by attribute key, or null when an option names no value the product has.
	 */
	private function selected_values( array $selected, array $axes ): ?array {
		$values = array();

		foreach ( $selected as $option ) {
			if ( ! isset( $option['name'], $option['label'] ) ) {
				continue;
			}

			foreach ( $axes as $key => $axis ) {
				foreach ( 0 === strcasecmp( $axis['name'], (string) $option['name'] ) ? $axis['terms'] : array() as $slug => $name ) {
					if ( 0 === strcasecmp( (string) $slug, (string) $option['label'] ) || 0 === strcasecmp( $name, (string) $option['label'] ) ) {
						$values[ $key ] = (string) $slug;
						continue 3;
					}
				}
			}

			return null;
		}

		return $values;
	}

	/**
	 * List the variants a variation is sold as: one per combination of values for its "Any"
	 * attributes, less those an earlier variation takes, as on the storefront and in the cart.
	 *
	 * @param int        $variation_id Variation id.
	 * @param array|null $own Its values by attribute key, '' for "Any"; null yields nothing.
	 * @param array      $axes Variation attributes, from variation_data().
	 * @param array      $narrow Values to hold attributes to, by attribute key.
	 * @param array      $earlier The product's variations in matching order, from VariationMatcher::get_variation_attributes().
	 * @param callable   $available Whether the variation with the given id is available; one that is not takes nothing.
	 * @return \Generator Yields `{ id, variation_id, options }`.
	 */
	private function variant_specs( int $variation_id, ?array $own, array $axes, array $narrow, array $earlier, callable $available ): \Generator {
		if ( null === $own ) {
			return;
		}

		$sets = array();
		foreach ( $own as $key => $value ) {
			$values       = '' !== $value ? array( $value ) : array_map( 'strval', array_keys( $axes[ $key ]['terms'] ) );
			$sets[ $key ] = isset( $narrow[ $key ] ) ? array_intersect( $values, array( $narrow[ $key ] ) ) : $values;
		}

		$claims = array();
		foreach ( in_array( '', $own, true ) ? $earlier : array() as $earlier_id => $fixed ) {
			if ( $earlier_id === $variation_id ) {
				break;
			}

			$claim = array();
			foreach ( array_diff( $fixed, array( '' ) ) as $key => $value ) {
				if ( ! in_array( $value, $sets[ $key ] ?? array(), true ) ) {
					continue 2;
				}

				if ( count( $sets[ $key ] ) > 1 ) {
					$claim[ $key ] = $value;
				}
			}

			if ( ! $available( $earlier_id ) ) {
				continue;
			}

			$claims[] = $claim;
		}

		foreach ( $this->combinations( $sets, $claims ) as $values ) {
			yield array(
				'id'           => Utils::variant_id( $variation_id, $values ),
				'variation_id' => $variation_id,
				'options'      => $this->options( $values, $axes ),
			);
		}
	}

	/**
	 * Walk the cartesian product of value sets, last set fastest, less the combinations claimed.
	 *
	 * Claimed branches are cut rather than walked: a claim with one key left takes its value
	 * out of that set, and one with none left takes the whole branch. Claims that cover a
	 * branch only between them, several keys deep, are still walked into before they cut.
	 *
	 * @param array $sets Values by key.
	 * @param array $claims Combinations to leave out, each a value by key for some of the keys.
	 * @return \Generator Yields one value by key.
	 */
	private function combinations( array $sets, array $claims = array() ): \Generator {
		foreach ( $claims as $index => $claim ) {
			if ( empty( $claim ) ) {
				return;
			}

			if ( 1 === count( $claim ) ) {
				$sets[ key( $claim ) ] = array_diff( $sets[ key( $claim ) ], $claim );
				unset( $claims[ $index ] );
			}
		}

		if ( in_array( array(), $sets, true ) ) {
			return;
		}

		if ( empty( $sets ) ) {
			yield array();
			return;
		}

		$key    = array_key_first( $sets );
		$values = $sets[ $key ];
		unset( $sets[ $key ] );

		foreach ( $values as $value ) {
			// A claim naming another value for this key is out of the branch; the rest carry on without the key.
			$open = array();
			foreach ( $claims as $claim ) {
				if ( ( $claim[ $key ] ?? $value ) === $value ) {
					unset( $claim[ $key ] );
					$open[] = $claim;
				}
			}

			foreach ( $this->combinations( $sets, $open ) as $rest ) {
				yield array( $key => $value ) + $rest;
			}
		}
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
	 * @param \WC_Product $product The product.
	 * @param array       $item Store API item response.
	 * @param string      $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_simple_variant( \WC_Product $product, array $item, string $currency ): array {
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

		$barcodes = $this->build_barcodes( $product );
		if ( ! empty( $barcodes ) ) {
			$variant['barcodes'] = $barcodes;
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
	 * @param \WC_Product_Variation $variation The variation.
	 * @param array                 $item Store API item response of the variation.
	 * @param array                 $options Selected options, each `{ name, label }`.
	 * @param string                $currency ISO 4217 currency code.
	 * @return array
	 */
	private function build_variation_variant( \WC_Product_Variation $variation, array $item, array $options, string $currency ): array {
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

		$barcodes = $this->build_barcodes( $variation );
		if ( ! empty( $barcodes ) ) {
			$variant['barcodes'] = $barcodes;
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
	 * Build a variant's barcodes from its GTIN, normalized and validated by the product structured data,
	 * so the catalog and the structured data never disagree on a GTIN.
	 *
	 * A variation's GTIN is its own: the parent's identifies a different item.
	 *
	 * @param \WC_Product $product The product or variation.
	 * @return array Empty, or one `{ type, value }` barcode.
	 */
	private function build_barcodes( \WC_Product $product ): array {
		$structured_data = WC()->structured_data;
		if ( ! $structured_data instanceof \WC_Structured_Data ) {
			return array();
		}
		$gtin = (string) $structured_data->prepare_gtin( (string) $product->get_global_unique_id() );
		if ( ! $structured_data->is_valid_gtin( $gtin ) ) {
			return array();
		}

		return array(
			array(
				'type'  => 'GTIN',
				'value' => $gtin,
			),
		);
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
	 * Find the visible variations a selection can be bought as.
	 *
	 * A variation left as "Any" for an attribute accepts each of its values, as the
	 * storefront does.
	 *
	 * @param array      $attributes Values by attribute key for each variation id, from variation_data().
	 * @param array|null $selected Selected values by attribute key, from selected_values().
	 * @return array Matching variation ids.
	 */
	private function match_selection( array $attributes, ?array $selected ): array {
		$matching = array();

		foreach ( $attributes as $variation_id => $own ) {
			if ( ! empty( $selected ) && ! array_diff_assoc( array_intersect_key( array_diff( $own, array( '' ) ), $selected ), $selected ) ) {
				$matching[] = $variation_id;
			}
		}

		return $matching;
	}
}
