<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

/**
 * A user's saved list of products.
 */
class ShopperList {
	/**
	 * Per-(user, slug) wp_options key prefix. Stored with autoload=no.
	 */
	const OPTION_PREFIX = 'wc_shopper_list_';

	/**
	 * Slugs cleaned up on user deletion.
	 */
	private const KNOWN_SLUGS = array( 'saved-for-later' );

	/**
	 * Maximum distinct items per list. Revisit when storage moves to a dedicated table.
	 */
	const MAX_ITEMS = 100;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * List slug.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Datetime the list was created.
	 *
	 * @var string
	 */
	private $date_created_gmt;

	/**
	 * Items in the list.
	 *
	 * @var array<string, ShopperListItem>
	 */
	private $items;

	/**
	 * Private constructor. Use the static factories to obtain concrete instances.
	 *
	 * @param int                            $user_id          Owning user ID.
	 * @param string                         $slug             List slug.
	 * @param string                         $date_created_gmt MySQL DATETIME, GMT.
	 * @param array<string, ShopperListItem> $items            Items keyed by storage key.
	 */
	private function __construct(
		int $user_id,
		string $slug,
		string $date_created_gmt,
		array $items
	) {
		$this->user_id          = $user_id;
		$this->slug             = $slug;
		$this->date_created_gmt = $date_created_gmt;
		$this->items            = $items;
	}

	/**
	 * Load a list by slug. Returns false for any other list that doesn't exist.
	 *
	 * @param string   $slug List identifier.
	 * @param int|null $user_id Defaults to the current user.
	 * @return self|false
	 */
	public static function get_by_slug( string $slug, ?int $user_id = null ) {
		$user_id = absint( $user_id ? $user_id : get_current_user_id() );
		if ( ! $user_id ) {
			return false;
		}

		$stored = get_option( self::get_option_name( $user_id, $slug ), false );

		if ( is_array( $stored ) ) {
			return self::from_array( $stored, $user_id );
		}

		// In-memory saved-for-later; persisted lazily on the first save().
		if ( 'saved-for-later' === $slug ) {
			return new self(
				$user_id,
				'saved-for-later',
				current_time( 'mysql', true ),
				array()
			);
		}

		return false;
	}

	/**
	 * Get all of the user's lists.
	 *
	 * @param int|null $user_id Defaults to the current user.
	 * @return array<string, self>
	 */
	public static function get_all_for_user( ?int $user_id = null ): array {
		// For now, only saved-for-later exists.
		return array_filter(
			array(
				'saved-for-later' => self::get_by_slug( 'saved-for-later', $user_id ),
			)
		);
	}

	/**
	 * The list slug (e.g. 'saved-for-later').
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Creation time as a MySQL DATETIME in GMT.
	 */
	public function get_date_created_gmt(): string {
		return $this->date_created_gmt;
	}

	/**
	 * Add an item, or merge quantities if it already exists.
	 *
	 * @throws ShopperListFullException When the list is at MAX_ITEMS and the item would be a new entry.
	 *
	 * @param ShopperListItem $item Item to add.
	 */
	public function add_item( ShopperListItem $item ): void {
		$key = $item->get_key();

		if ( isset( $this->items[ $key ] ) ) {
			$this->items[ $key ] = ShopperListItem::from_array(
				array_merge(
					$this->items[ $key ]->to_array(),
					array( 'quantity' => $this->items[ $key ]->get_quantity() + $item->get_quantity() )
				)
			);
			return;
		}

		if ( count( $this->items ) >= self::MAX_ITEMS ) {
			throw new ShopperListFullException(
				esc_html( sprintf( 'Shopper list "%s" is at capacity (%d items).', $this->slug, self::MAX_ITEMS ) )
			);
		}

		$this->items[ $key ] = $item;
	}

	/**
	 * Remove an item by key. Returns false if the key wasn't present.
	 *
	 * @param string $key Storage key of the item to remove.
	 */
	public function remove_item( string $key ): bool {
		if ( ! isset( $this->items[ $key ] ) ) {
			return false;
		}
		unset( $this->items[ $key ] );
		return true;
	}

	/**
	 * Get all items currently in the list.
	 *
	 * @return array<string, ShopperListItem>
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Find an item by key.
	 *
	 * @param string $key Storage key.
	 */
	public function find_item( string $key ): ?ShopperListItem {
		return $this->items[ $key ] ?? null;
	}

	/**
	 * Persist the current state.
	 */
	public function save(): void {
		update_option( self::get_option_name( $this->user_id, $this->slug ), $this->to_array(), false );
	}

	/**
	 * Delete every stored list for a user.
	 *
	 * @param int $user_id Owning user ID.
	 */
	public static function delete_all_for_user( int $user_id ): void {
		if ( $user_id > 0 ) {
			foreach ( self::KNOWN_SLUGS as $slug ) {
				delete_option( self::get_option_name( $user_id, $slug ) );
			}
		}
	}

	/**
	 * Storage option name for a (user, slug) pair.
	 *
	 * @param int    $user_id Owning user ID.
	 * @param string $slug    List slug.
	 */
	private static function get_option_name( int $user_id, string $slug ): string {
		return self::OPTION_PREFIX . $user_id . '_' . $slug;
	}

	/**
	 * Storage / response shape.
	 */
	public function to_array(): array {
		$items_array = array();
		foreach ( $this->items as $key => $item ) {
			$items_array[ $key ] = $item->to_array();
		}

		return array(
			'slug'             => $this->slug,
			'date_created_gmt' => $this->date_created_gmt,
			'items'            => $items_array,
		);
	}

	/**
	 * Build a ShopperList from a stored array.
	 *
	 * @param array $data    Stored list record.
	 * @param int   $user_id Owning user ID.
	 */
	private static function from_array( array $data, int $user_id ): self {
		$items = array();
		if ( ! empty( $data['items'] ) && is_array( $data['items'] ) ) {
			foreach ( $data['items'] as $key => $item_data ) {
				if ( ! is_array( $item_data ) ) {
					continue;
				}

				try {
					$items[ (string) $key ] = ShopperListItem::from_array( $item_data );
				} catch ( \Throwable $e ) {
					continue;
				}
			}
		}

		return new self(
			$user_id,
			$data['slug'] ?? '',
			$data['date_created_gmt'] ?? current_time( 'mysql', true ),
			$items
		);
	}
}
