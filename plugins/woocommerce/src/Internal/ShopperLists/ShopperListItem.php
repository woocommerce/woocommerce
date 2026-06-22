<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * A single saved item within a shopper list.
 */
class ShopperListItem {
	/**
	 * Storage key (md5 of identity tuple).
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Product ID at the time the item was saved.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Variation ID at the time the item was saved (0 for non-variable products).
	 *
	 * @var int
	 */
	private $variation_id;

	/**
	 * Variation attributes captured at save time.
	 *
	 * @var array
	 */
	private $variation;

	/**
	 * Saved quantity (always 1 in the current contract).
	 *
	 * @var int
	 */
	private $quantity;

	/**
	 * MySQL DATETIME the item was saved, in GMT.
	 *
	 * @var string
	 */
	private $date_added_gmt;

	/**
	 * Snapshot of the product title at save time.
	 *
	 * @var string
	 */
	private $product_title_at_save;

	/**
	 * Resolved product, cached on the instance.
	 *
	 * @var \WC_Product|null
	 */
	private $product = null;

	/**
	 * Private constructor. Use the static factories to obtain concrete instances.
	 *
	 * @param string $key                   Storage key (md5 of identity tuple).
	 * @param int    $product_id            Product ID.
	 * @param int    $variation_id          Variation ID, or 0.
	 * @param array  $variation             Variation attributes.
	 * @param int    $quantity              Saved quantity.
	 * @param string $date_added_gmt        MySQL DATETIME, GMT.
	 * @param string $product_title_at_save Title snapshot.
	 */
	private function __construct(
		string $key,
		int $product_id,
		int $variation_id,
		array $variation,
		int $quantity,
		string $date_added_gmt,
		string $product_title_at_save
	) {
		$this->key                   = $key;
		$this->product_id            = $product_id;
		$this->variation_id          = $variation_id;
		$this->variation             = $variation;
		$this->quantity              = $quantity;
		$this->date_added_gmt        = $date_added_gmt;
		$this->product_title_at_save = $product_title_at_save;
	}

	/**
	 * Construct from a stored item array (from user_meta).
	 *
	 * @throws \Exception When the stored payload is missing required fields.
	 *
	 * @param array $data Stored item record.
	 */
	public static function from_array( array $data ): self {
		if (
			empty( $data['key'] ) || ! is_string( $data['key'] )
			|| empty( $data['product_id'] ) || ! is_int( $data['product_id'] )
			|| empty( $data['quantity'] ) || ! is_int( $data['quantity'] )
		) {
			throw new \Exception( 'Shopper list item requires "key" (string), "product_id" (int), and "quantity" (int).' );
		}

		return new self(
			$data['key'],
			absint( $data['product_id'] ),
			absint( $data['variation_id'] ?? 0 ),
			$data['variation'] ?? array(),
			absint( $data['quantity'] ),
			$data['date_added_gmt'] ?? current_time( 'mysql', true ),
			$data['product_title_at_save'] ?? ''
		);
	}

	/**
	 * Construct from a product (or variation) ID and optional payload fields.
	 *
	 * @throws \InvalidArgumentException When the provided variation attributes do not match the variation product.
	 *
	 * @param int   $product_or_variation_id Product or variation ID.
	 * @param array $variation               Variation attributes keyed by attribute name.
	 * @param int   $quantity                Saved quantity. Coerced to a minimum of 1.
	 * @return self|null Null if the underlying product can't be resolved or isn't published.
	 */
	public static function from_product( int $product_or_variation_id, array $variation = array(), int $quantity = 1 ): ?self {
		$product = wc_get_product( absint( $product_or_variation_id ) );
		if ( ! $product || ! self::product_is_live( $product ) ) {
			return null;
		}

		if ( $product->is_type( ProductType::VARIATION ) ) {
			$variation_id = $product->get_id();
			$product_id   = $product->get_parent_id();
			$variation    = self::resolve_variation_attributes( $product, $variation );
		} elseif ( $product->is_type( ProductType::VARIABLE ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'When saving a variation, product_id must be the variation ID, not the parent product ID.', 'woocommerce' )
			);
		} else {
			$product_id   = $product->get_id();
			$variation_id = 0;
			$variation    = array();
		}

		return new self(
			self::generate_key( $product_id, $variation_id, $variation ),
			$product_id,
			$variation_id,
			$variation,
			max( 1, $quantity ),
			current_time( 'mysql', true ),
			$product->get_title()
		);
	}

	/**
	 * Resolve and validate the variation attribute array against the variation product.
	 *
	 * Mirrors {@see CartController::parse_variation_data()}: specific values come from
	 * the variation (server-authoritative); "any" slots must be supplied by the caller
	 * with a value that exists on the parent product.
	 *
	 * @throws \InvalidArgumentException When the supplied variation attributes are
	 *                                   missing required values or don't match the
	 *                                   variation product.
	 *
	 * @param \WC_Product $variation_product     Variation product.
	 * @param array       $requested_attributes  Variation attributes supplied by the caller, keyed by `attribute_<slug>`.
	 * @return array
	 */
	private static function resolve_variation_attributes( \WC_Product $variation_product, array $requested_attributes ): array {
		$parent = wc_get_product( $variation_product->get_parent_id() );
		if ( ! $parent || ! $parent->is_type( ProductType::VARIABLE ) || ! $variation_product->is_type( ProductType::VARIATION ) ) {
			return array();
		}

		$result = array();

		$all_attributes       = array_filter( $parent->get_attributes(), fn( $attribute ) => $attribute->get_variation() );
		$variation_attributes = wc_get_product_variation_attributes( $variation_product->get_id() );

		foreach ( $all_attributes as $name => $attribute ) {
			$key      = 'attribute_' . $name;
			$expected = $variation_attributes[ $key ] ?? '';

			// Variation doesn't provide attribute ('any' attribute).
			if ( '' === $expected ) {
				if ( ! isset( $requested_attributes[ $key ] ) ) {
					throw new \InvalidArgumentException(
						esc_html(
							sprintf(
								/* translators: %s: attribute name. */
								__( 'Attribute "%s" is required.', 'woocommerce' ),
								$name
							)
						)
					);
				}

				if ( ! in_array( $requested_attributes[ $key ], $attribute->get_slugs(), true ) ) {
					throw new \InvalidArgumentException(
						esc_html(
							sprintf(
								/* translators: 1: attribute name, 2: comma-separated allowed values. */
								__( 'Invalid value posted for "%1$s". Allowed values: %2$s', 'woocommerce' ),
								$name,
								implode( ', ', $attribute->get_slugs() )
							)
						)
					);
				}

				$result[ $key ] = $requested_attributes[ $key ];
				continue;
			}//end if

			// Variation provides attribute.
			if ( isset( $requested_attributes[ $key ] ) && $requested_attributes[ $key ] !== $expected ) {
				throw new \InvalidArgumentException(
					esc_html(
						sprintf(
							/* translators: 1: attribute name, 2: expected value. */
							__( 'Invalid value posted for "%1$s". Expected "%2$s".', 'woocommerce' ),
							$name,
							$expected
						)
					)
				);
			}

			$result[ $key ] = $expected;
		}//end foreach

		return $result;
	}

	/**
	 * Storage key — also used as the response identifier.
	 */
	public function get_key(): string {
		return $this->key;
	}

	/**
	 * Product ID at save time.
	 */
	public function get_product_id(): int {
		return $this->product_id;
	}

	/**
	 * Variation ID at save time, or 0 for non-variable products.
	 */
	public function get_variation_id(): int {
		return $this->variation_id;
	}

	/**
	 * Saved quantity.
	 */
	public function get_quantity(): int {
		return $this->quantity;
	}

	/**
	 * Variation attributes captured at save time.
	 */
	public function get_variation_attributes(): array {
		return $this->variation;
	}

	/**
	 * Save time as a MySQL DATETIME in GMT.
	 */
	public function get_date_added_gmt(): string {
		return $this->date_added_gmt;
	}

	/**
	 * Snapshot of the product title at save time.
	 */
	public function get_product_title_at_save(): string {
		return $this->product_title_at_save;
	}

	/**
	 * Resolve the live product (or variation) backing this saved item.
	 */
	public function get_product(): ?\WC_Product {
		if ( $this->product instanceof \WC_Product ) {
			return $this->product;
		}
		$id            = $this->variation_id > 0 ? $this->variation_id : $this->product_id;
		$product       = $id > 0 ? wc_get_product( $id ) : false;
		$this->product = $product instanceof \WC_Product ? $product : null;
		return $this->product;
	}

	/**
	 * Whether the row serves live product data. True when the product (and its
	 * parent, for variations) is `publish`; password-gated products still
	 * qualify since their page renders behind a prompt.
	 */
	public function is_live(): bool {
		$product = $this->get_product();
		return $product instanceof \WC_Product && self::product_is_live( $product );
	}

	/**
	 * Whether a resolved product (and its parent, for variations) is `publish`.
	 *
	 * @param \WC_Product $product Resolved product or variation.
	 */
	private static function product_is_live( \WC_Product $product ): bool {
		if ( ProductStatus::PUBLISH !== $product->get_status() ) {
			return false;
		}

		$parent_id = $product->get_parent_id();
		if ( $parent_id > 0 ) {
			$parent = wc_get_product( $parent_id );

			if ( ! $parent instanceof \WC_Product || ProductStatus::PUBLISH !== $parent->get_status() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the product can be added to the cart. Mirrors the catalog gate
	 * (`is_purchasable()` && `is_in_stock()`), but additionally requires the
	 * row to be live and rejects password-gated products (self or parent) —
	 * cart-add can't prompt for a password.
	 */
	public function is_purchasable(): bool {
		$product = $this->get_product();
		if ( ! $this->is_live() || ! $product ) {
			return false;
		}
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return false;
		}

		if ( ! empty( $product->get_post_password() ) ) {
			return false;
		}

		$parent_id = $product->get_parent_id();
		if ( $parent_id > 0 ) {
			$parent = wc_get_product( $parent_id );
			if ( $parent instanceof \WC_Product && ! empty( $parent->get_post_password() ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Storage shape used to persist into user_meta.
	 */
	public function to_array(): array {
		return array(
			'key'                   => $this->key,
			'product_id'            => $this->product_id,
			'variation_id'          => $this->variation_id,
			'variation'             => $this->variation,
			'quantity'              => $this->quantity,
			'date_added_gmt'        => $this->date_added_gmt,
			'product_title_at_save' => $this->product_title_at_save,
		);
	}

	/**
	 * Compute a deterministic item key. Mirrors WC_Cart::generate_cart_id() so the same
	 * product+variation always hashes to the same key, regardless of the input key order
	 * for variation attributes.
	 *
	 * @param int   $product_id   Product ID.
	 * @param int   $variation_id Variation ID, or 0.
	 * @param array $variation    Variation attributes.
	 */
	private static function generate_key( int $product_id, int $variation_id, array $variation ): string {
		$id_parts = array( $product_id );

		if ( $variation_id ) {
			$id_parts[] = $variation_id;
		}

		if ( ! empty( $variation ) ) {
			ksort( $variation );
			$variation_key = '';
			foreach ( $variation as $k => $v ) {
				$variation_key .= trim( (string) $k ) . trim( (string) $v );
			}
			$id_parts[] = $variation_key;
		}

		return md5( implode( '_', $id_parts ) );
	}
}
