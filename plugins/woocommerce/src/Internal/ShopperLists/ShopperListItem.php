<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

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
	 * Snapshot of the product price at save time.
	 *
	 * @var string
	 */
	private $price_at_save;

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
	 * @param string $price_at_save         Price snapshot.
	 */
	private function __construct(
		string $key,
		int $product_id,
		int $variation_id,
		array $variation,
		int $quantity,
		string $date_added_gmt,
		string $product_title_at_save,
		string $price_at_save
	) {
		$this->key                   = $key;
		$this->product_id            = $product_id;
		$this->variation_id          = $variation_id;
		$this->variation             = $variation;
		$this->quantity              = $quantity;
		$this->date_added_gmt        = $date_added_gmt;
		$this->product_title_at_save = $product_title_at_save;
		$this->price_at_save         = $price_at_save;
	}

	/**
	 * Construct from a stored item array (from user_meta).
	 *
	 * @param array $data Stored item record.
	 */
	public static function from_array( array $data ): self {
		return new self(
			$data['key'] ?? '',
			$data['product_id'] ?? 0,
			$data['variation_id'] ?? 0,
			isset( $data['variation'] ) && is_array( $data['variation'] ) ? $data['variation'] : array(),
			$data['quantity'] ?? 1,
			$data['date_added_gmt'] ?? '',
			$data['product_title_at_save'] ?? '',
			$data['price_at_save'] ?? ''
		);
	}

	/**
	 * Construct from a product (or variation) ID and optional payload fields.
	 *
	 * @param int   $product_or_variation_id Product or variation ID.
	 * @param array $variation               Variation attributes keyed by attribute name.
	 * @param int   $quantity                Saved quantity. Coerced to a minimum of 1.
	 * @return self|null Null if the underlying product can't be resolved.
	 */
	public static function from_product( int $product_or_variation_id, array $variation = array(), int $quantity = 1 ): ?self {
		$product = wc_get_product( absint( $product_or_variation_id ) );
		if ( ! $product ) {
			return null;
		}

		$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;
		$product_id   = $variation_id ? $product->get_parent_id() : $product->get_id();

		return new self(
			self::generate_key( $product_id, $variation_id, $variation ),
			$product_id,
			$variation_id,
			$variation,
			max( 1, $quantity ),
			current_time( 'mysql', true ),
			$product->get_title(),
			$product->get_price()
		);
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
	 * Storage / response shape.
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
			'price_at_save'         => $this->price_at_save,
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
