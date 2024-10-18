<?php

namespace Automattic\WooCommerce;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * This class manages the hooks (actions and filters) of all the WooCommerce classes.
 *
 * When hooking to a class method the standard 'add_action' and 'add_filter' functions require an actual instance
 * of the target class to be already existing, on the contrary this class takes a class name or a class
 * instantiation callback when declaring a hook, so the class doesn't get instantiated until the time when
 * the corresponding hook is fired for the first time.
 *
 * For each combination of hook name, priority and accepted args registered a standard action or filter hooking
 * to a static method named "{$filter_name}__{$priority}__{$accepted_args}" within this class will be
 * performed, the call to this method when the hook is fired will be captured by __callStatic and then the
 * necessary class instantiations will happen and the target methods on these objects will be called.
 *
 * The built-in hookings (those that target WooCommerce classes and are set up at WooCommerce startup time)
 * are declared in $built_in_hookings and hooked by the 'init' method, plugins can declare additional hookings
 * using the 'register_action' and 'register_filter' methods.
 */
class Hooks {

	/**
	 * The Container instance to use.
	 *
	 * @var Container
	 */
	private static $container;

	/**
	 * The LegacyProxy instance to use.
	 *
	 * @var LegacyProxy
	 */
	private static $legacy_proxy;

	/**
	 * Built-in WooCommerce hooking declarations.
	 * Keys are class names, values are arrays of [hook name, method name, priority, arguments count]
	 * Priority and arguments count are optional, defaulting to 10 and 1 respectively.
	 *
	 * @var array[]
	 */
	private static $built_in_hookings = array(
		DataRegenerator::class => array(
			array( 'woocommerce_debug_tools', 'add_initiate_regeneration_entry_to_tools_array', 999 ),
			array( 'woocommerce_run_product_attribute_lookup_regeneration_callback', 'run_regeneration_step_callback' ),
			array( 'woocommerce_installed', 'run_woocommerce_installed_callback' ),
		),

		/*
		TODO: Add here all the hookings performed in the constructors or the 'register' methods of the classes
		 * that get instantiated at the end of WooCommerce::init_hooks, and replace their 'add_action' and 'add_filter'
		 * calls with calls to 'mark_method_as_accessible' (from the AccessiblePrivateMethods trait).
		 */
	);

	/**
	 * Currently active hookings.
	 *
	 * Format: {hook_name}__{priority}__{accepted_args} => [target, method_name, priority, accepted_args]
	 *
	 * where target can be:
	 *
	 * - An object
	 * - A callable that returns an object
	 *   (runs once and then gets replaced with the class name of the returned object)
	 * - A class name
	 *
	 * When hooking to a static method, the format for method_name is "Class::method", and then
	 * the target can either be null or a callback that initializes the class.
	 *
	 * @var array
	 */
	private static $active_hookings = array();

	/**
	 * Cache of classes instantiated when the target passed to register_filter/action is a callable or a class name.
	 * Classes are instantiated the first time one of the hooks for which they are the target is executed,
	 * and then cached and reused for any further hook execution.
	 *
	 * Format: class_name => class_instance
	 *
	 * @var array
	 */
	private static $class_instances = array();

	/**
	 * Count of hookings for each of the classes that get instantiated inside a hook handler,
	 * the count increases in 'register_action/filter' and decreases in 'remove_action/filter' and
	 * 'remove_action/filter_by_id' as appropriate; when the count reaches zero, the class is removed
	 * from the array.
	 *
	 * Format: class_name => count
	 *
	 * @var array
	 */
	private static $hookings_count_by_class = array();

	/**
	 * Initializes the class and performs the built-in hookings.
	 *
     * phpcs:disable WooCommerce.Functions.InternalInjectionMethod
	 */
	public static function init(): void {
		self::$container    = wc_get_container();
		self::$legacy_proxy = self::$container->get( LegacyProxy::class );

		foreach ( self::$built_in_hookings as $class_name => $hookings ) {
			foreach ( $hookings as $hooking ) {
				self::register_hook_core( $hooking[0], $class_name, $hooking[1], $hooking[2] ?? 10, $hooking[3] ?? 1 );
			}
		}
	}

	/**
	 * Register a new action hooking.
	 *
	 * When hooking to an instance method, $target can be:
	 *
	 * - An object.
	 * - A callback that returns an object.
	 * - A class name.
	 *
	 * In the last two cases, the object is instantiated from the callback or class name the first time that
	 * the action is fired. The class is instantiated using the dependency injection container if possible,
	 * or using LegacyProxy::get_instance_of if not (this usually leads to just "new ClassName()").
	 *
	 * When hooking to a static method, $target can be:
	 *
	 * - Null.
	 * - A callback that performs any required initialization (its return value is ignored).
	 *
	 * @param string                      $action_name The name of the action to hook to.
	 * @param object|string|callable|null $target An object, a callback, a class name, or (only when hooking to a static method) null.
	 * @param string                      $method The class method to hook to. When hooking to a static method it must have the format "Class::method".
	 * @param int                         $priority The action priority.
	 * @param int                         $accepted_args The number of arguments the action accepts.
	 * @return string A unique identifier for the hooking.
	 */
	public static function register_action( string $action_name, $target, string $method, int $priority = 10, int $accepted_args = 1 ) {
		return self::register_filter( $action_name, $target, $method, $priority, $accepted_args );
	}

	/**
	 * Register a new filter hooking.
	 *
	 * When hooking to an instance method, $target can be:
	 *
	 * - An object.
	 * - A callback that returns an object.
	 * - A class name.
	 *
	 * In the last two cases, the object is instantiated from the callback or class name the first time that
	 * the filter is fired. The class is instantiated using the dependency injection container if possible,
	 * or using LegacyProxy::get_instance_of if not (this usually leads to just "new ClassName()").
	 *
	 * When hooking to a static method, $target can be:
	 *
	 * - Null.
	 * - A callback that performs any required initialization (its return value is ignored).
	 *
	 * @param string                      $filter_name The name of the filter to hook to.
	 * @param object|string|callable|null $target An object, a callback, a class name, or (only when hooking to a static method) null.
	 * @param string                      $method The class method to hook to. When hooking to a static method it must have the format "Class::method".
	 * @param int                         $priority The filter priority.
	 * @param int                         $accepted_args The number of arguments the filter accepts.
	 * @return string A unique identifier for the hooking.
	 * @throws \InvalidArgumentException Invalid value for $target.
	 */
	public static function register_filter( string $filter_name, $target, string $method, int $priority = 10, int $accepted_args = 1 ) {
		if ( StringUtil::contains( $method, '::' ) ) {
			if ( ! is_callable( $target ) && ! is_null( $target ) ) {
				throw new \InvalidArgumentException( '$target must be either an initialization callable or null when hooking to a static method.' );
			}
		} elseif ( ! is_callable( $target ) && ! is_object( $target ) && ! is_string( $target ) ) {
			throw new \InvalidArgumentException( '$target must be either an object, a callable that returns an instance of an object, or a class name.' );
		}

		return self::register_hook_core( $filter_name, $target, $method, $priority, $accepted_args );
	}

	/**
	 * Core method to register an action or filter hooking.
	 *
	 * @param string                      $hook_name The name of the hook.
	 * @param object|string|callable|null $target An object, a callback, a class name, or (only when hooking to a static method) null.
	 * @param string                      $method The class method to hook to. When hooking to a static method it must have the format "Class::method".
	 * @param int                         $priority The hook priority.
	 * @param int                         $accepted_args The number of arguments the hook accepts.
	 * @return string A unique identifier for the hooking.
	 */
	private static function register_hook_core( string $hook_name, $target, string $method, int $priority, int $accepted_args ) {
		$hooks_array_key = "{$hook_name}__{$priority}__{$accepted_args}";
		if ( ! isset( self::$active_hookings[ $hooks_array_key ] ) ) {
			add_filter( $hook_name, __CLASS__ . '::' . $hooks_array_key, $priority, $accepted_args );
		}

		if ( is_string( $target ) ) {
			self::increase_hookings_count_for_class( $target );
		}

		$hooking_id                                  = bin2hex( random_bytes( 16 ) );
		self::$active_hookings[ $hooks_array_key ][] = array( $target, $method, $priority, $accepted_args, $hooking_id );
		return $hooking_id;
	}

	/**
	 * Remove an action hooking by providing the unique id that 'register_action' returned.
	 *
	 * @param string $hook_id Hooking id as returned by 'register_action'.
	 * @return bool True if the hooking was removed, false if no hooking exists with the provided id.
	 */
	public static function remove_action_by_id( string $hook_id ): bool {
		return self::remove_filter_by_id( $hook_id );
	}

	/**
	 * Remove a filter hooking by providing the unique id that 'register_filter' returned.
	 *
	 * @param string $hook_id Hooking id as returned by 'register_filter'.
	 * @return bool True if the hooking was removed, false if no hooking exists with the provided id.
	 */
	public static function remove_filter_by_id( string $hook_id ): bool {
		foreach ( self::$active_hookings as $hooks_array_key => &$hookings_info ) {
			foreach ( $hookings_info as $info_key => $hooking_info ) {
				if ( $hooking_info[4] !== $hook_id ) {
					continue;
				}

				self::remove_hook_core( $hooks_array_key, $info_key, $hooking_info[0] );
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove an action hooking by providing the hooking details as they were provided to 'register_action'.
	 *
	 * When the action was registered by providing a callback as the target it will be possible to remove the
	 * hooking with this method only if the corresponding action has been fired at least once, and then the
	 * class name of the object that was returned by the callback (instead of the callback itself) must be passed
	 * in $target.
	 *
	 * @param string             $action_name The name of the action to remove the hooking for.
	 * @param object|string|null $target The target that was passed to 'register_action', class name if it was a callback.
	 * @param string             $method The class method that was passed to 'register_action'. When unhooking a static method it must have the format "Class::method".
	 * @param int                $priority The action priority that was passed to 'register_action'.
	 * @param int                $accepted_args The number of arguments the action accepts as it was passed to 'register_action'.
	 * @return bool True if the hooking was removed, false if no hooking exists with the provided details.
	 */
	public static function remove_action( string $action_name, $target, string $method, int $priority = 10, int $accepted_args = 1 ) {
		return self::remove_filter( $action_name, $target, $method, $priority, $accepted_args );
	}

	/**
	 * Remove a filter hooking by providing the hooking details as they were provided to 'register_filter'.
	 *
	 * When the filter was registered by providing a callback as the target it will be possible to remove the
	 * hooking with this method only if the corresponding filter has been fired at least once, and then the
	 * class name of the object that was returned by the callback (instead of the callback itself) must be passed
	 * in $target.
	 *
	 * @param string             $filter_name The name of the filter to remove the hooking for.
	 * @param object|string|null $target The target that was passed to 'register_filter', class name if it was a callback.
	 * @param string             $method The class method that was passed to 'register_filter'. When unhooking a static method it must have the format "Class::method".
	 * @param int                $priority The action priority that was passed to 'register_filter'.
	 * @param int                $accepted_args The number of arguments the filter accepts as it was passed to 'register_filter'.
	 * @return bool True if the hooking was removed, false if no hooking exists with the provided details.
	 */
	public static function remove_filter( string $filter_name, $target, string $method, int $priority = 10, int $accepted_args = 1 ) {
		$hooks_array_key = "{$filter_name}__{$priority}__{$accepted_args}";
		$hookings_info   = self::$active_hookings[ $hooks_array_key ] ?? null;
		if ( is_null( $hookings_info ) ) {
			return false;
		}

		$is_static = StringUtil::contains( $method, '::' );
		foreach ( $hookings_info as $info_key => $hooking_info ) {
			if ( ( ! $is_static && $hooking_info[0] !== $target ) || $hooking_info[1] !== $method || $hooking_info[2] !== $priority || $hooking_info[3] !== $accepted_args ) {
				continue;
			}

			self::remove_hook_core( $hooks_array_key, $info_key, $hooking_info[0] );
			return true;
		}

		return false;
	}

	/**
	 * Core method to remove a hooking.
	 *
	 * @param string             $hooks_array_key Hooking key within $active_hookings, with the format "{$filter_name}__{$priority}__{$accepted_args}".
	 * @param int                $info_key Index of the hooking info inside the array of hookings defined by $hooks_array_key in $active_hookings.
	 * @param object|string|null $target Hooking target as defined in the hooking info array.
	 */
	private static function remove_hook_core( string $hooks_array_key, int $info_key, $target ) {
		unset( self::$active_hookings[ $hooks_array_key ][ $info_key ] );
		if ( empty( self::$active_hookings[ $hooks_array_key ] ) ) {
			unset( self::$active_hookings[ $hooks_array_key ] );
			$hook_parts = explode( '__', $hooks_array_key );
			remove_filter( $hook_parts[0], __CLASS__ . '::' . $hooks_array_key, $hook_parts[1] );
		}

		if ( ! is_string( $target ) ) {
			return;
		}

		$hookings_count = self::$hookings_count_by_class[ $target ] ?? 0;
		if ( $hookings_count > 1 ) {
			--self::$hookings_count_by_class[ $target ];
		} else {
			unset( self::$class_instances[ $target ] );
			unset( self::$hookings_count_by_class[ $target ] );
		}
	}

	/**
	 * Remove all the hookings related to a given action that were registered using `register_action`.
	 *
	 * @param string   $action_name The name of the action whose hookings will be removed.
	 * @param int|null $priority The priority of the hooking to remove, null to remove all hookings regardless of priority.
	 * @return int Number of hookings that have been removed.
	 */
	public static function remove_all_actions( string $action_name, ?int $priority = null ) {
		return self::remove_all_filters( $action_name, $priority );
	}

	/**
	 * Remove all the hookings related to a given filter that were registered using `register_filter`.
	 *
	 * @param string   $filter_name The name of the filter whose hookings will be removed.
	 * @param int|null $priority The priority of the hooking to remove, null to remove all hookings regardless of priority.
	 * @return int Number of hookings that have been removed.
	 */
	public static function remove_all_filters( string $filter_name, ?int $priority = null ) {
		$removed            = 0;
		$key_prefix         = false === $priority ? "{$filter_name}__" : "{$filter_name}__{$priority}__";
		$matching_hook_keys = array_filter( array_keys( self::$active_hookings ), fn( $key ) => StringUtil::starts_with( $key, $key_prefix ) );
		foreach ( $matching_hook_keys as $hook_key ) {
			foreach ( self::$active_hookings[ $hook_key ] as $hooking_info ) {
				self::remove_filter_by_id( $hooking_info[4] );
				++$removed;
			}
		}
		return $removed;
	}

	/**
	 * Check if there's at least one hooking for the specified action that was registered using `register_action`.
	 *
	 * If $method is null, any hooking for the action will match the search. Otherwise, only hookings
	 * for the specified combination of $target and $method will match (for static methods, $target is ignored
	 * and $method must have the format "Class::method").
	 *
	 * For hookings registered with an object passed as the target, $target must be the class name of the object.
	 *
	 * For hookings registered with a class instantiation callback as the target, a match will happen only after
	 * the corresponding action has been fired at least once, and $target must be the class name of the object
	 * returned by the callback.
	 *
	 * @param string      $action_name The name of the action to match.
	 * @param string|null $target The target class name to match (ignored for static methods).
	 * @param string|null $method The method name to match, "Class::method" for static method.
	 * @return bool True if at least one matching hooking is found, false otherwise.
	 */
	public static function has_action( string $action_name, ?string $target = null, ?string $method = null ): bool {
		return self::has_filter( $action_name, $target, $method );
	}

	/**
	 * Check if there's at least one hooking for the specified filter that was registered using `register_filter`.
	 *
	 * If $method is null, any hooking for the filter will match the search. Otherwise, only hookings
	 * for the specified combination of $target and $method will match (for static methods, $target is ignored
	 * and $method must have the format "Class::method").
	 *
	 * For hookings registered with an object passed as the target, $target must be the class name of the object.
	 *
	 * For hookings registered with a class instantiation callback as the target, a match will happen only after
	 * the corresponding filter has been fired at least once, and $target must be the class name of the object
	 * returned by the callback.
	 *
	 * @param string      $filter_name The name of the filter to match.
	 * @param string|null $target The target class name to match (ignored for static methods).
	 * @param string|null $method The method name to match, "Class::method" for static method.
	 * @return bool True if at least one matching hooking is found, false otherwise.
	 */
	public static function has_filter( string $filter_name, ?string $target = null, ?string $method = null ): bool {
		foreach ( self::$active_hookings as $hooking_key => $hookings_info ) {
			if ( ! StringUtil::starts_with( $hooking_key, $filter_name . '__' ) ) {
				continue;
			}

			if ( is_null( $method ) ) {
				return true;
			}

			$is_static = StringUtil::contains( $method, '::' );
			foreach ( $hookings_info as $hooking_info ) {
				if ( is_callable( $hooking_info[0] ) ) {
					continue;
				}

				$target_class_name = is_object( $hooking_info[0] ) ? get_class( $hooking_info[0] ) : $hooking_info[0];
				if ( ( $is_static || $target === $target_class_name ) && $hooking_info[1] === $method ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Handle attempts to invoke a private or non-existing static method in the class.
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed|null Return value from handle_hook.
	 * @throws \Error Attempt to invoke a private or non-existing static method that is not a hook target.
	 */
	public static function __callStatic( $name, $arguments ) {
		$hook_info = &self::$active_hookings[ $name ];
		if ( ! is_null( $hook_info ) ) {
			return self::handle_hook( $hook_info, $arguments );
		} elseif ( method_exists( __CLASS__, $name ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Error( 'Call to private method ' . __CLASS__ . '::' . $name );
		} else {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Error( 'Call to undefined method ' . __CLASS__ . '::' . $name );
		}
	}

	/**
	 * Handle the execution of a registered hook.
	 *
	 * @param array $hook_info Hooking definition as stored in $active_hookings.
	 * @param array $args Arguments passed to the hook.
	 * @return mixed Return value for the hook.
	 */
	private static function handle_hook( array &$hook_info, array $args ) {
		$value = $args[0] ?? null;

		foreach ( $hook_info as &$hook_instance ) {
			$target    = $hook_instance[0];
			$method    = $hook_instance[1];
			$is_static = StringUtil::contains( $method, '::' );

			// "if"s ordering matters: a callable is also an object!
			if ( $is_static ) {
				if ( is_callable( $target ) ) {
					$target();
					$hook_instance[0] = null;
				}
			} elseif ( is_callable( $target ) ) {
				$instance                             = $target();
				$class_name                           = get_class( $instance );
				self::$class_instances[ $class_name ] = $instance;
				$hook_instance[0]                     = $class_name;
				self::increase_hookings_count_for_class( $class_name );
			} elseif ( is_object( $target ) ) {
				$instance = $target;
			} elseif ( isset( self::$class_instances[ $target ] ) ) {
				$instance = self::$class_instances[ $target ];
			} else {
				$instance                         = self::$container->has( $target ) ? self::$container->get( $target ) : self::$legacy_proxy->get_instance_of( $target );
				self::$class_instances[ $target ] = $instance;
			}

			$value = $is_static ? $method( ...$args ) : $instance->$method( ...$args );
			if ( ! empty( $args ) ) {
				$args[0] = $value;
			}
		}

		return $value;
	}

	/**
	 * Increase the count of active hookings for a given class name.
	 *
	 * @param string $class_name The class name.
	 */
	private static function increase_hookings_count_for_class( string $class_name ): void {
		$count                                        = self::$hookings_count_by_class[ $class_name ] ?? 0;
		self::$hookings_count_by_class[ $class_name ] = $count + 1;
	}
}
