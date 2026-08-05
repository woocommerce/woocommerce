<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Traits;

/**
 * DON'T USE THIS TRAIT. It's DEPRECATED and will be REMOVED in a future version of WooCommerce.
 *
 * If you have class methods that are public solely because they are the target of WordPress hooks,
 * make the methods public and mark them with an @internal annotation.
 *
 * @deprecated 9.6.0 Make the hook target methods public and mark them with an @internal annotation. This trait will be REMOVED in a future version of WooCommerce.
 */
trait AccessiblePrivateMethods {
    // phpcs:disable

	private $_accessible_private_methods = array();

	private static $_accessible_static_private_methods = array();

	protected static function add_action( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		self::process_callback_before_hooking( $callback );
		add_action( $hook_name, $callback, $priority, $accepted_args );
	}

	protected static function add_filter( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		self::process_callback_before_hooking( $callback );
		add_filter( $hook_name, $callback, $priority, $accepted_args );
	}

	protected static function process_callback_before_hooking( $callback ): void {
		if ( ! is_array( $callback ) || count( $callback ) < 2 ) {
			return;
		}

		$first_item = $callback[0];
		if ( __CLASS__ === $first_item ) {
			static::mark_static_method_as_accessible( $callback[1] );
		} elseif ( is_object( $first_item ) && get_class( $first_item ) === __CLASS__ ) {
			$first_item->mark_method_as_accessible( $callback[1] );
		}
	}

	protected function mark_method_as_accessible( string $method_name ): bool {
		if ( method_exists( $this, $method_name ) ) {
			$this->_accessible_private_methods[ $method_name ] = $method_name;
			return true;
		}

		return false;
	}

	protected static function mark_static_method_as_accessible( string $method_name ): bool {
		if ( method_exists( __CLASS__, $method_name ) ) {
			static::$_accessible_static_private_methods[ $method_name ] = $method_name;
			return true;
		}

		return false;
	}

	public function __call( $name, $arguments ) {
		if ( isset( $this->_accessible_private_methods[ $name ] ) ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		} elseif ( is_callable( array( 'parent', '__call' ) ) ) {
			return parent::__call( $name, $arguments );
		} elseif ( method_exists( $this, $name ) ) {
			throw new \Error( 'Call to private method ' . get_class( $this ) . '::' . $name );
		} else {
			throw new \Error( 'Call to undefined method ' . get_class( $this ) . '::' . $name );
		}
	}

	public static function __callStatic( $name, $arguments ) {
		if ( isset( static::$_accessible_static_private_methods[ $name ] ) ) {
			return call_user_func_array( array( __CLASS__, $name ), $arguments );
		} elseif ( is_callable( array( 'parent', '__callStatic' ) ) ) {
			return parent::__callStatic( $name, $arguments );
		} elseif ( 'add_action' === $name || 'add_filter' === $name ) {
			$proper_method_name = 'add_static_' . substr( $name, 4 );
			throw new \Error( __CLASS__ . '::' . $name . " can't be called statically, did you mean '$proper_method_name'?" );
		} elseif ( method_exists( __CLASS__, $name ) ) {
			throw new \Error( 'Call to private method ' . __CLASS__ . '::' . $name );
		} else {
			throw new \Error( 'Call to undefined method ' . __CLASS__ . '::' . $name );
		}
	}

    // phpcs:enable
}
