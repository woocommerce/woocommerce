<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

/**
 * A single saved item within a shopper list.
 *
 * Identity is the deterministic md5 of product + variation + item_data, so
 * adding the same payload twice always lands in the same slot (idempotent UPSERT).
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
	 * Custom item data captured at save time (extension fields).
	 *
	 * @var array
	 */
	private $item_data;

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
	 * Construct from already-validated values. Use the static factories instead.
	 *
	 * @param string $key                   Storage key (md5 of identity tuple).
	 * @param int    $product_id            Product ID.
	 * @param int    $variation_id          Variation ID, or 0.
	 * @param array  $variation             Variation attributes.
	 * @param int    $quantity              Saved quantity.
	 * @param array  $item_data             Custom item data.
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
		array $item_data,
		string $date_added_gmt,
		string $product_title_at_save,
		string $price_at_save
	) {
		$this->key                   = $key;
		$this->product_id            = $product_id;
		$this->variation_id          = $variation_id;
		$this->variation             = $variation;
		$this->quantity              = $quantity;
		$this->item_data             = $item_data;
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
			isset( $data['item_data'] ) && is_array( $data['item_data'] ) ? $data['item_data'] : array(),
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
	 * @param array $item_data               Custom item data.
	 * @return self|null Null if the underlying product can't be resolved.
	 */
	public static function from_product( int $product_or_variation_id, array $variation = array(), array $item_data = array() ): ?self {
		$product = $product_or_variation_id > 0 ? wc_get_product( $product_or_variation_id ) : false;

		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;
		$product_id   = $variation_id > 0 ? $product->get_parent_id() : $product->get_id();

		return new self(
			self::generate_key( $product_id, $variation_id, $variation, $item_data ),
			$product_id,
			$variation_id,
			$variation,
			1,
			$item_data,
			current_time( 'mysql', true ),
			$product->get_title(),
			$product->get_price()
		);
	}

	/**
	 * Storage key — also used as the response identifier.
	 */
	public function key(): string {
		return $this->key;
	}

	/**
	 * Product ID at save time.
	 */
	public function product_id(): int {
		return $this->product_id;
	}

	/**
	 * Variation ID at save time, or 0 for non-variable products.
	 */
	public function variation_id(): int {
		return $this->variation_id;
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
			'item_data'             => $this->item_data,
			'date_added_gmt'        => $this->date_added_gmt,
			'product_title_at_save' => $this->product_title_at_save,
			'price_at_save'         => $this->price_at_save,
		);
	}

	/**
	 * Compute a deterministic item key. Mirrors WC_Cart::generate_cart_id() so the same
	 * product+variation+item_data always hashes to the same key.
	 *
	 * @param int   $product_id   Product ID.
	 * @param int   $variation_id Variation ID, or 0.
	 * @param array $variation    Variation attributes.
	 * @param array $item_data    Custom item data.
	 */
	private static function generate_key( int $product_id, int $variation_id, array $variation, array $item_data ): string {
		$id_parts = array( $product_id );

		if ( $variation_id > 0 ) {
			$id_parts[] = $variation_id;
		}

		if ( ! empty( $variation ) ) {
			$variation_key = '';
			foreach ( $variation as $k => $v ) {
				$variation_key .= trim( (string) $k ) . trim( (string) $v );
			}
			$id_parts[] = $variation_key;
		}

		if ( ! empty( $item_data ) ) {
			$item_data_key = '';
			foreach ( $item_data as $k => $v ) {
				if ( is_array( $v ) || is_object( $v ) ) {
					$v = http_build_query( (array) $v );
				}
				$item_data_key .= trim( (string) $k ) . trim( (string) $v );
			}
			$id_parts[] = $item_data_key;
		}

		return md5( implode( '_', $id_parts ) );
	}
}
