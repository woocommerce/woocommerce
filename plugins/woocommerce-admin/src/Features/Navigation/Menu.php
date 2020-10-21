<?php
/**
 * WooCommerce Navigation Menu
 *
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Features\Navigation\Screen;

/**
 * Contains logic for the WooCommerce Navigation menu.
 */
class Menu {
	/**
	 * Class instance.
	 *
	 * @var Menu instance
	 */
	protected static $instance = null;

	/**
	 * Array index of menu capability.
	 *
	 * @var int
	 */
	const CAPABILITY = 1;

	/**
	 * Array index of menu callback.
	 *
	 * @var int
	 */
	const CALLBACK = 2;

	/**
	 * Array index of menu callback.
	 *
	 * @var int
	 */
	const SLUG = 3;

	/**
	 * Array index of menu CSS class string.
	 *
	 * @var int
	 */
	const CSS_CLASSES = 4;

	/**
	 * Default parent menu
	 *
	 * @var string
	 */
	const DEFAULT_PARENT = 'settings';

	/**
	 * Store menu items.
	 *
	 * @var array
	 */
	protected static $menu_items = array();

	/**
	 * Registered callbacks or URLs with migration boolean as key value pairs.
	 *
	 * @var array
	 */
	protected static $callbacks = array();

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
	 * Init.
	 */
	public function init() {
		add_filter( 'admin_enqueue_scripts', array( $this, 'enqueue_data' ), 20 );
		add_filter( 'add_menu_classes', array( $this, 'migrate_menu_items' ), 30 );
	}

	/**
	 * Convert a WordPress menu callback to a URL.
	 *
	 * @param string $callback Menu callback.
	 * @return string
	 */
	public static function get_callback_url( $callback ) {
		$pos  = strpos( $callback, '?' );
		$file = $pos > 0 ? substr( $callback, 0, $pos ) : $callback;
		if ( file_exists( ABSPATH . "/wp-admin/$file" ) ) {
			return $callback;
		}
		return 'admin.php?page=' . $callback;
	}

	/**
	 * Get the parent key if one exists.
	 *
	 * @param string $callback Callback or URL.
	 * @return string|null
	 */
	public static function get_parent_key( $callback ) {
		global $submenu;

		if ( ! $submenu ) {
			return null;
		}

		// This is already a parent item.
		if ( isset( $submenu[ $callback ] ) ) {
			return null;
		}

		foreach ( $submenu as $key => $menu ) {
			foreach ( $menu as $item ) {
				if ( $item[ self::CALLBACK ] === $callback ) {
					return $key;
				}
			}
		}

		return null;
	}

	/**
	 * Adds a top level menu item to the navigation.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'         => (string) The unique ID of the menu item. Required.
	 *      'title'      => (string) Title of the menu item. Required.
	 *      'capability' => (string) Capability to view this menu item.
	 *      'url'        => (string) URL or callback to be used. Required.
	 *      'order'      => (int) Menu item order.
	 *      'migrate'    => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'menuId'     => (string) The ID of the menu to add the category to.
	 *    ).
	 */
	public static function add_category( $args ) {
		if ( ! isset( $args['id'] ) || isset( self::$menu_items[ $args['id'] ] ) ) {
			return;
		}

		$defaults           = array(
			'id'              => '',
			'title'           => '',
			'capability'      => 'manage_woocommerce',
			'order'           => 100,
			'migrate'         => true,
			'menuId'          => 'primary',
			'isCategory'      => true,
			'parent'          => self::DEFAULT_PARENT,
			'backButtonLabel' => __(
				'WooCommerce Home',
				'woocommerce-admin'
			),
			'is_top_level'    => false,
		);
		$menu_item          = wp_parse_args( $args, $defaults );
		$menu_item['title'] = wp_strip_all_tags( wp_specialchars_decode( $menu_item['title'] ) );
		unset( $menu_item['url'] );

		if ( true === $menu_item['is_top_level'] ) {
			$menu_item['parent'] = 'woocommerce';
		} else {
			$menu_item['parent'] = 'woocommerce' === $menu_item['parent'] ? self::DEFAULT_PARENT : $menu_item['parent'];
		}

		self::$menu_items[ $menu_item['id'] ] = $menu_item;

		if ( isset( $args['url'] ) ) {
			self::$callbacks[ $args['url'] ] = $menu_item['migrate'];
		}
	}

	/**
	 * Adds a child menu item to the navigation.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'         => (string) The unique ID of the menu item. Required.
	 *      'title'      => (string) Title of the menu item. Required.
	 *      'parent'     => (string) Parent menu item ID.
	 *      'capability' => (string) Capability to view this menu item.
	 *      'url'        => (string) URL or callback to be used. Required.
	 *      'order'      => (int) Menu item order.
	 *      'migrate'    => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'menuId'     => (string) The ID of the menu to add the item to.
	 *    ).
	 */
	public static function add_item( $args ) {
		if ( ! isset( $args['id'] ) || isset( self::$menu_items[ $args['id'] ] ) ) {
			return;
		}

		$defaults           = array(
			'id'           => '',
			'title'        => '',
			'parent'       => self::DEFAULT_PARENT,
			'capability'   => 'manage_woocommerce',
			'url'          => '',
			'order'        => 100,
			'migrate'      => true,
			'menuId'       => 'primary',
			'is_top_level' => false,
		);
		$menu_item          = wp_parse_args( $args, $defaults );
		$menu_item['title'] = wp_strip_all_tags( wp_specialchars_decode( $menu_item['title'] ) );
		$menu_item['url']   = self::get_callback_url( $menu_item['url'] );

		if ( true === $menu_item['is_top_level'] ) {
			$menu_item['parent'] = 'woocommerce';
		} else {
			$menu_item['parent'] = 'woocommerce' === $menu_item['parent'] ? self::DEFAULT_PARENT : $menu_item['parent'];
		}

		self::$menu_items[ $menu_item['id'] ] = $menu_item;

		if ( isset( $args['url'] ) ) {
			self::$callbacks[ $args['url'] ] = $menu_item['migrate'];
		}
	}

	/**
	 * Adds a post type as a menu category.
	 *
	 * @param string $post_type Post type.
	 * @param array  $args Array of menu item args.
	 */
	public static function add_post_type_category( $post_type, $args = array() ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object ) {
			return;
		}

		self::add_category(
			array_merge(
				array(
					'title'        => esc_attr( $post_type_object->labels->menu_name ),
					'capability'   => $post_type_object->cap->edit_posts,
					'id'           => $post_type,
					'is_top_level' => true,
				),
				$args
			)
		);
		self::add_item(
			array(
				'parent'     => $post_type,
				'title'      => esc_attr( $post_type_object->labels->all_items ),
				'capability' => $post_type_object->cap->edit_posts,
				'id'         => "{$post_type}-all-items",
				'url'        => "edit.php?post_type={$post_type}",
			)
		);
		self::add_item(
			array(
				'parent'     => $post_type,
				'title'      => esc_attr( $post_type_object->labels->add_new ),
				'capability' => $post_type_object->cap->create_posts,
				'id'         => "{$post_type}-add-new",
				'url'        => "post-new.php?post_type={$post_type}",
			)
		);
	}

	/**
	 * Hides all WP admin menus items and adds screen IDs to check for new items.
	 *
	 * @param array $menu Menu items.
	 * @return array
	 */
	public static function migrate_menu_items( $menu ) {
		global $submenu;

		foreach ( $menu as $key => $menu_item ) {
			if (
				isset( self::$callbacks[ $menu_item[ self::CALLBACK ] ] ) &&
				self::$callbacks[ $menu_item[ self::CALLBACK ] ]
			) {
				$menu[ $key ][ self::CSS_CLASSES ] .= ' hide-if-js';
			}
		}

		foreach ( $submenu as $parent_key => $parent ) {
			foreach ( $parent as $key => $menu_item ) {
				if (
					(
						isset( self::$callbacks[ $menu_item[ self::CALLBACK ] ] ) &&
						self::$callbacks[ $menu_item[ self::CALLBACK ] ]
					) ||
					(
						isset( self::$callbacks[ self::get_callback_url( $menu_item[ self::CALLBACK ] ) ] ) &&
						self::$callbacks[ self::get_callback_url( $menu_item[ self::CALLBACK ] ) ]
					)
				) {
					// Disable phpcs since we need to override submenu classes.
					// Note that `phpcs:ignore WordPress.Variables.GlobalVariables.OverrideProhibited` does not work to disable this check.
					// phpcs:disable
					if ( ! isset( $menu_item[ self::SLUG ] ) ) {
						$submenu[ $parent_key ][ $key ][] = '';
					}
					if ( ! isset( $menu_item[ self::CSS_CLASSES ] ) ) {
						$submenu[ $parent_key ][ $key ][] .= ' hide-if-js';
					} else {
						$submenu[ $parent_key ][ $key ][ self::CSS_CLASSES ] .= ' hide-if-js';
					}
					// phps:enable
				}
			}
		}

		foreach ( array_keys( self::$callbacks ) as $callback ) {
			Screen::add_screen( $callback );
		}

		return $menu;
	}

	/**
	 * Get registered menu items.
	 *
	 * @return array
	 */
	public static function get_items() {
		return apply_filters( 'woocommerce_navigation_menu_items', self::$menu_items );
	}

	/**
	 * Get registered callbacks.
	 *
	 * @return array
	 */
	public static function get_callbacks() {
		return apply_filters( 'woocommerce_navigation_callbacks', self::$callbacks );
	}

	/**
	 * Gets the menu item data for use in the client.
	 *
	 * @return array
	 */
	public static function get_prepared_menu_item_data() {
		$menu_items = self::get_items();
		foreach ( $menu_items as $index => $menu_item ) {
			if ( $menu_item[ 'capability' ] && ! current_user_can( $menu_item[ 'capability' ] ) ) {
				unset( $menu_items[ $index ] );
			}
		}

		// Sort the menu items.
		$order = array_column( $menu_items, 'order' );
		array_multisort( $order, SORT_ASC, $menu_items );

		return array_values( $menu_items );
	}

	/**
	 * Add the menu to the page output.
	 *
	 * @param array $menu Menu items.
	 * @return array
	 */
	public function enqueue_data( $menu ) {
		global $submenu, $parent_file, $typenow, $self;

		$data = array(
			'menuItems' => self::get_prepared_menu_item_data(),
		);

		$paul = wp_add_inline_script( WC_ADMIN_APP, 'window.wcNavigation = ' . wp_json_encode( $data ), 'before' );

		return $menu;
	}
}
