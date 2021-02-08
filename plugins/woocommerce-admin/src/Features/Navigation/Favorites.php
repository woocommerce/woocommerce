<?php
/**
 * WooCommerce Navigation Favorite
 *
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Loader;

/**
 * Contains logic for the WooCommerce Navigation menu.
 */
class Favorites {

	/**
	 * Array index of menu capability.
	 *
	 * @var int
	 */
	const META_NAME = 'navigation_favorites';

	/**
	 * Get class instance.
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Set given favorites string to the user meta data.
	 *
	 * @param string|number $user_id User id.
	 * @param array         $favorites Array of favorite values to set.
	 */
	private static function set_meta_value( $user_id, $favorites ) {
		Loader::update_user_data_field( $user_id, self::META_NAME, wp_json_encode( (array) $favorites ) );
	}

	/**
	 * Add item to favorites
	 *
	 * @param string        $item_id Identifier of item to add.
	 * @param string|number $user_id Identifier of user to add to.
	 */
	public static function add_item( $item_id, $user_id = null ) {
		$user = $user_id ? $user_id : get_current_user_id();

		if ( ! $user || ! $item_id ) {
			return;
		}

		$all_favorites = self::get_all( $user );

		if ( in_array( $item_id, $all_favorites, true ) ) {
			return;
		}

		$all_favorites[] = $item_id;

		self::set_meta_value( $user, $all_favorites );
	}

	/**
	 * Remove item from favorites
	 *
	 * @param string        $item_id Identifier of item to remove.
	 * @param string|number $user_id Identifier of user to remove from.
	 */
	public static function remove_item( $item_id, $user_id = null ) {
		$user = $user_id ? $user_id : get_current_user_id();

		if ( ! $user || ! $item_id ) {
			return;
		}

		$all_favorites = self::get_all( $user );

		if ( ! in_array( $item_id, $all_favorites, true ) ) {
			return;
		}

		$remaining = array_diff( $all_favorites, [ $item_id ] );

		self::set_meta_value( $user, array_values( $remaining ) );
	}

	/**
	 * Get all registered favorites.
	 *
	 * @param string|number $user_id Identifier of user to query.
	 */
	public static function get_all( $user_id = null ) {
		$user = $user_id ? $user_id : get_current_user_id();

		if ( ! $user ) {
			return;
		}

		$response = Loader::get_user_data_field( $user, self::META_NAME );

		return $response ? json_decode( $response, true ) : array();
	}

}
