<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\Utilities\Users;

/**
 * A user's saved list of products.
 */
class ShopperList {
	/**
	 * Prefix for per-list usermeta key for list details.
	 */
	const META_KEY_PREFIX = '_wc_shopper_list_';

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
	 * List name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Whether the list is public.
	 *
	 * @var bool
	 */
	private $is_public;

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
	 * @param string                         $name             Human-readable name.
	 * @param bool                           $is_public        Whether the list is shareable.
	 * @param string                         $date_created_gmt MySQL DATETIME, GMT.
	 * @param array<string, ShopperListItem> $items            Items keyed by storage key.
	 */
	private function __construct(
		int $user_id,
		string $slug,
		string $name,
		bool $is_public,
		string $date_created_gmt,
		array $items
	) {
		$this->user_id          = $user_id;
		$this->slug             = $slug;
		$this->name             = $name;
		$this->is_public        = $is_public;
		$this->date_created_gmt = $date_created_gmt;
		$this->items            = $items;
	}

	/**
	 * Load a list by slug. Returns false for any other list that doesn't exist.
	 *
	 * @throws \RuntimeException When the stored data is corrupt (non-array meta value).
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

		$stored = Users::get_site_user_meta( $user_id, self::META_KEY_PREFIX . $slug );

		if ( is_array( $stored ) ) {
			return self::from_array( $stored, $user_id );
		}

		// Anything other than the empty default means a row exists but isn't an array, signaling corrupt data.
		if ( '' !== $stored && false !== $stored && null !== $stored ) {
			throw new \RuntimeException(
				esc_html( sprintf( 'Corrupt shopper list data for user %d list %s', $user_id, $slug ) )
			);
		}

		// Auto-create saved-for-later when necessary.
		if ( 'saved-for-later' === $slug ) {
			$list = new self(
				$user_id,
				'saved-for-later',
				'Saved for Later',
				false,
				current_time( 'mysql', true ),
				array()
			);
			$list->save();
			return $list;
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
		return array(
			'saved-for-later' => self::get_by_slug( 'saved-for-later', $user_id )
		);
	}

	/**
	 * The list slug (e.g. 'saved-for-later').
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Add (or replace by key) an item.
	 *
	 * @param ShopperListItem $item Item to add.
	 */
	public function add_item( ShopperListItem $item ): void {
		$this->items[ $item->get_key() ] = $item;
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
	 * Persist the current state to user meta.
	 */
	public function save(): void {
		Users::update_site_user_meta(
			$this->user_id,
			self::META_KEY_PREFIX . $this->slug,
			$this->to_array()
		);
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
			'name'             => $this->name,
			'is_public'        => $this->is_public,
			'date_created_gmt' => $this->date_created_gmt,
			'items'            => $items_array,
		);
	}

	/**
	 * Build a ShopperList from a stored array (deserialising items into ShopperListItem instances).
	 *
	 * @param array $data    Stored list record.
	 * @param int   $user_id Owning user ID.
	 */
	private static function from_array( array $data, int $user_id ): self {
		$items = array();
		if ( ! empty( $data['items'] ) && is_array( $data['items'] ) ) {
			foreach ( $data['items'] as $key => $item_data ) {
				if ( is_array( $item_data ) ) {
					$items[ (string) $key ] = ShopperListItem::from_array( $item_data );
				}
			}
		}

		return new self(
			$user_id,
			$data['slug'] ?? '',
			$data['name'] ?? '',
			$data['is_public'] ?? false,
			$data['date_created_gmt'] ?? current_time( 'mysql', true ),
			$items
		);
	}
}
