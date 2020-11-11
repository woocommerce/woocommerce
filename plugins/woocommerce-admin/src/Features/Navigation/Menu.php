<?php
/**
 * WooCommerce Navigation Menu
 *
 * @package Woocommerce Navigation
 */

namespace Automattic\WooCommerce\Admin\Features\Navigation;

use Automattic\WooCommerce\Admin\Features\Navigation\Screen;
use Automattic\WooCommerce\Admin\Features\Navigation\CoreMenu;

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
		add_action( 'admin_menu', array( $this, 'add_core_items' ) );
		add_filter( 'admin_enqueue_scripts', array( $this, 'enqueue_data' ), 20 );
		add_filter( 'add_menu_classes', array( $this, 'migrate_menu_items' ), 30 );
		add_filter( 'add_menu_classes', array( $this, 'migrate_core_child_items' ) );
	}

	/**
	 * Convert a WordPress menu callback to a URL.
	 *
	 * @param string $callback Menu callback.
	 * @return string
	 */
	public static function get_callback_url( $callback ) {
		// Return the full URL.
		if ( strpos( $callback, 'http' ) === 0 ) {
			return $callback;
		}

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
	private static function add_category( $args ) {
		if ( ! isset( $args['id'] ) || isset( self::$menu_items[ $args['id'] ] ) ) {
			return;
		}

		$defaults           = array(
			'id'           => '',
			'title'        => '',
			'capability'   => 'manage_woocommerce',
			'order'        => 100,
			'migrate'      => true,
			'menuId'       => 'primary',
			'isCategory'   => true,
			'parent'       => self::DEFAULT_PARENT,
			'is_top_level' => false,
		);
		$menu_item          = wp_parse_args( $args, $defaults );
		$menu_item['title'] = wp_strip_all_tags( wp_specialchars_decode( $menu_item['title'] ) );
		unset( $menu_item['url'] );

		if ( true === $menu_item['is_top_level'] ) {
			$menu_item['parent']          = 'woocommerce';
			$menu_item['backButtonLabel'] = __(
				'WooCommerce Home',
				'woocommerce-admin'
			);
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
	private static function add_item( $args ) {
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
	 * Adds a plugin item.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'         => (string) The unique ID of the menu item. Required.
	 *      'title'      => (string) Title of the menu item. Required.
	 *      'parent'     => (string) Parent menu item ID.
	 *      'capability' => (string) Capability to view this menu item.
	 *      'url'        => (string) URL or callback to be used. Required.
	 *      'migrate'    => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'menuId'     => (string) The ID of the menu to add the item to.
	 *      'order'      => (int) Menu item order.
	 *    ).
	 */
	public static function add_plugin_item( $args ) {
		if ( ! isset( $args['parent'] ) ) {
			unset( $args['order'] );
		}

		$item_args = array_merge(
			$args,
			array(
				'menuId'       => 'plugins',
				'is_top_level' => ! isset( $args['parent'] ),
			)
		);
		self::add_item( $item_args );
	}

	/**
	 * Adds a plugin category.
	 *
	 * @param array $args Array containing the necessary arguments.
	 *    $args = array(
	 *      'id'         => (string) The unique ID of the menu item. Required.
	 *      'title'      => (string) Title of the menu item. Required.
	 *      'capability' => (string) Capability to view this menu item.
	 *      'url'        => (string) URL or callback to be used. Required.
	 *      'migrate'    => (bool) Whether or not to hide the item in the wp admin menu.
	 *      'menuId'     => (string) The ID of the menu to add the category to.
	 *      'order'      => (int) Menu item order.
	 *    ).
	 */
	public static function add_plugin_category( $args ) {
		if ( ! isset( $args['parent'] ) ) {
			unset( $args['order'] );
		}

		$category_args = array_merge(
			$args,
			array(
				'menuId'       => 'plugins',
				'is_top_level' => ! isset( $args['parent'] ),
			)
		);
		self::add_category( $category_args );
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
	 * Get menu item templates for a given post type.
	 *
	 * @param string $post_type Post type to add.
	 * @param array  $menu_args Arguments merged with the returned menu items.
	 * @return array
	 */
	public static function get_post_type_items( $post_type, $menu_args = array() ) {
		$post_type_object = get_post_type_object( $post_type );

		if ( ! $post_type_object || ! $post_type_object->show_in_menu ) {
			return;
		}

		return array(
			'default' => array_merge(
				array(
					'title'      => esc_attr( $post_type_object->labels->menu_name ),
					'capability' => $post_type_object->cap->edit_posts,
					'id'         => $post_type,
					'url'        => "edit.php?post_type={$post_type}",
				),
				$menu_args
			),
			'all'     => array_merge(
				array(
					'title'      => esc_attr( $post_type_object->labels->all_items ),
					'capability' => $post_type_object->cap->edit_posts,
					'id'         => "{$post_type}-all-items",
					'url'        => "edit.php?post_type={$post_type}",
					'order'      => 10,
				),
				$menu_args
			),
			'new'     => array_merge(
				array(
					'title'      => esc_attr( $post_type_object->labels->add_new ),
					'capability' => $post_type_object->cap->create_posts,
					'id'         => "{$post_type}-add-new",
					'url'        => "post-new.php?post_type={$post_type}",
					'order'      => 20,
				),
				$menu_args
			),
		);
	}

	/**
	 * Add core menu items.
	 */
	public function add_core_items() {
		$categories = CoreMenu::get_categories();
		foreach ( $categories as $category ) {
			self::add_category( $category );
		}

		$items = CoreMenu::get_items();
		foreach ( $items as $item ) {
			self::add_item( $item );
		}
	}

	/**
	 * Migrate any remaining WooCommerce child items.
	 *
	 * @param array $menu Menu items.
	 * @return array
	 */
	public function migrate_core_child_items( $menu ) {
		global $submenu;

		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce'] as $menu_item ) {
			if ( in_array( $menu_item[2], CoreMenu::get_excluded_items(), true ) ) {
				continue;
			}

			// Don't add already added items.
			$callbacks = self::get_callbacks();
			if ( array_key_exists( $menu_item[2], $callbacks ) ) {
				continue;
			}

			self::add_item(
				array(
					'parent'     => 'settings',
					'title'      => $menu_item[0],
					'capability' => $menu_item[1],
					'id'         => sanitize_title( $menu_item[0] ),
					'url'        => $menu_item[2],
				)
			);
		}

		return $menu;
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
		$title = array_column( $menu_items, 'title' );
		array_multisort( $order, SORT_ASC, $title, SORT_ASC, $menu_items );

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
			'postType'  => isset( $_GET['post'] ) ? get_post_type( $_GET['post'] ) : null,
		);

		$paul = wp_add_inline_script( WC_ADMIN_APP, 'window.wcNavigation = ' . wp_json_encode( $data ), 'before' );

		return $menu;
	}
}
