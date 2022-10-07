<?php

namespace Automattic\WooCommerce\Caching;

/**
 * Base class for caching objects (or associative arrays) that have a unique identifier.
 * At the very least, derived classes need to implement the 'get_object_type' method,
 * but usually it will be convenient to override some of the other protected members.
 *
 * The actual caching is delegated to an instance of CacheEngine. By default WpCacheEngine is used,
 * but a different engine can be used by either overriding the get_cache_engine_instance method
 * or capturing the wc_object_cache_get_engine filter.
 *
 * Objects are identified by ids that are either integers or strings. The actual cache keys passed
 * to the cache engine will be prefixed with the object type and a random string. The 'flush' operation
 * just forces the generation a new prefix and lets the old cached objects expire.
 */
abstract class ObjectCache {

	private const CACHE_PREFIX_OPTION_NAME = 'wp_object_cache_key_prefix_';

	/**
	 * Expiration value to be passed to 'set' to use the value of $default_expiration.
	 */
	public const DEFAULT_EXPIRATION = -1;

	/**
	 * Maximum expiration time value, in seconds, that can be passed to 'set'.
	 */
	public const MAX_EXPIRATION = MONTH_IN_SECONDS;

	/**
	 * This needs to be set in each derived class.
	 *
	 * @var string
	 */
	private $object_type;

	/**
	 * Default value for the duration of the objects in the cache, in seconds
	 * (may not be used depending on the cache engine used WordPress cache implementation).
	 *
	 * @var int
	 */
	protected $default_expiration = HOUR_IN_SECONDS;

	/**
	 * Temporarily used when retrieving data in 'get'.
	 *
	 * @var array
	 */
	private $last_cached_data;

	/**
	 * The cache engine to use.
	 *
	 * @var CacheEngine
	 */
	private $cache_engine = null;

	/**
	 * The prefix to use for cache keys to pass to the cache engine.
	 *
	 * @var string
	 */
	private $cache_key_prefix = null;

	/**
	 * The name of the option used to store the cache prefix.
	 *
	 * @var string
	 */
	private $cache_key_prefix_option_name;

	/**
	 * Gets an identifier for the types of objects cached by this class.
	 * This identifier will be used to compose the keys passed to the cache engine,
	 * to the name of the option that stores the cache prefix, and the names of the hooks used.
	 * It must be unique for each class inheriting from ObjectCache.
	 *
	 * @return string
	 */
	abstract public function get_object_type(): string;

	/**
	 * Creates a new instance of the class.
	 *
	 * @throws CacheException If get_object_type returns null or an empty string.
	 */
	public function __construct() {
		$this->object_type = $this->get_object_type();
		if ( empty( $this->object_type ) ) {
			throw new CacheException( 'Class ' . get_class( $this ) . ' returns an empty value for get_object_type', $this );
		}

		$this->cache_key_prefix_option_name = self::CACHE_PREFIX_OPTION_NAME . $this->object_type;
	}

	/**
	 * Get the default expiration time for cached objects, in seconds.
	 *
	 * @return int
	 */
	public function get_default_expiration_value(): int {
		return $this->default_expiration;
	}

	/**
	 * Get the cache engine to use and cache it internally.
	 *
	 * @return CacheEngine
	 */
	private function get_cache_engine(): CacheEngine {
		if ( null === $this->cache_engine ) {
			$engine = $this->get_cache_engine_instance();

			/**
			 * Filters the underlying cache engine to be used by an instance of ObjectCache.
			 *
			 * @since 6.8.0
			 *
			 * @param CacheEngine $engine The cache engine to be used by default.
			 * @param ObjectCache $cache_instance The instance of ObjectCache that will use the cache engine.
			 * @returns CacheEngine The actual cache engine that will be used.
			 */
			$this->cache_engine = apply_filters( 'wc_object_cache_get_engine', $engine, $this );
		}
		return $this->cache_engine;
	}

	/**
	 * Get the current cache prefix to use, generating one if none is in use yet.
	 *
	 * @return string
	 */
	private function get_cache_key_prefix(): string {
		$value = $this->cache_key_prefix;
		if ( ! $value ) {
			$value = get_option( $this->cache_key_prefix_option_name );
			if ( ! $value ) {
				$value = $this->create_cache_key_prefix();
			}
			$this->cache_key_prefix = $value;
		}
		return $value;
	}

	/**
	 * Generate a prefix for the cache keys to use, containing the object type and a random string,
	 * and store it persistently using an option.
	 *
	 * @return string The generated prefix.
	 * @throws CacheException Can't store the generated prefix.
	 */
	private function create_cache_key_prefix(): string {
		$prefix_variable_part = $this->get_random_string();
		$prefix               = "woocommerce_object_cache|{$this->object_type}|{$prefix_variable_part}|";
		if ( ! update_option( $this->cache_key_prefix_option_name, $prefix ) ) {
			throw new CacheException( "Can't store the key prefix option", $this );
		}
		return $prefix;
	}

	/**
	 * Add an object to the cache, or update an already cached object.
	 *
	 * @param int|string|null $id Id of the object to be cached, if null, get_object_id will be used to get it.
	 * @param object|array    $object The object to be cached.
	 * @param int             $expiration Expiration of the cached data in seconds from the current time, or DEFAULT_EXPIRATION to use the default value.
	 * @return bool True on success, false on error.
	 * @throws CacheException Invalid parameter, or null id was passed and get_object_id returns null too.
	 */
	public function set( $id = null, $object, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		if ( null === $object ) {
			throw new CacheException( "Can't cache a null value", $this, $id );
		}

		if ( ! is_array( $object ) && ! is_object( $object ) ) {
			throw new CacheException( "Can't cache a non-object, non-array value", $this, $id );
		}

		if ( ! is_string( $id ) && ! is_int( $id ) && ! is_null( $id ) ) {
			throw new CacheException( "Object id must be an int, a string, or null for 'set'", $this, $id );
		}

		$this->verify_expiration_value( $expiration );

		if ( null === $id ) {
			$id = $this->get_object_id( $object );
			if ( null === $id ) {
				throw new CacheException( "Null id supplied and the cache class doesn't implement get_object_id", $this );
			}
		}

		$errors = $this->validate( $object );
		if ( null !== $errors && 1 === count( $errors ) ) {
			throw new CacheException( 'Object validation/serialization failed: ' . $errors[0], $this, $id, $errors );
		} elseif ( ! empty( $errors ) ) {
			throw new CacheException( 'Object validation/serialization failed', $this, $id, $errors );
		}

		$data = $this->serialize( $object );

		/**
		 * Filters the serialized object that will be passed by an instance of ObjectCache to its cache engine to be cached.
		 *
		 * @since 6.8.0
		 *
		 * @param array $data The already serialized object data.
		 * @param array|object $object The object before serialization.
		 * @returns array The actual serialized object data that will be passed to the cache engine.
		 */
		$data = apply_filters( "woocommerce_after_serializing_{$this->object_type}_for_caching", $data, $object, $id );

		$this->last_cached_data = $data;
		return $this->get_cache_engine()->cache_object( $this->get_cache_key_prefix() . $id, $data, self::DEFAULT_EXPIRATION === $expiration ? $this->default_expiration : $expiration );
	}

	/**
	 * Check if the given expiration time value is valid, throw an exception if not.
	 *
	 * @param int $expiration Expiration time to check.
	 * @return void
	 * @throws CacheException Expiration time is negative or higher than MAX_EXPIRATION.
	 */
	private function verify_expiration_value( int $expiration ): void {
		if ( self::DEFAULT_EXPIRATION !== $expiration && ( ( $expiration < 1 ) || ( $expiration > self::MAX_EXPIRATION ) ) ) {
			throw new CacheException( 'Invalid expiration value, must be ObjectCache::DEFAULT_EXPIRATION or a value between 1 and ObjectCache::MAX_EXPIRATION', $this );
		}
	}

	/**
	 * Retrieve a cached object, and if no object is cached with the given id,
	 * try to get one via get_from_datastore method or by supplying a callback and then cache it.
	 *
	 * If you want to provide a callable but still use the default expiration value,
	 * pass "ObjectCache::DEFAULT_EXPIRATION" as the second parameter.
	 *
	 * @param int|string    $id The id of the object to retrieve.
	 * @param int           $expiration Expiration of the cached data in seconds from the current time, used if an object is retrieved from datastore and cached.
	 * @param callable|null $get_from_datastore_callback Optional callback to get the object if it's not cached, it must return an object/array or null.
	 * @return object|array|null Cached object, or null if it's not cached and can't be retrieved from datastore or via callback.
	 * @throws CacheException Invalid id parameter.
	 */
	public function get( $id, int $expiration = self::DEFAULT_EXPIRATION, callable $get_from_datastore_callback = null ) {
		if ( ! is_string( $id ) && ! is_int( $id ) ) {
			throw new CacheException( "Object id must be an int or a string for 'get'", $this );
		}

		$this->verify_expiration_value( $expiration );

		$data = $this->get_cache_engine()->get_cached_object( $this->get_cache_key_prefix() . $id );
		if ( null === $data ) {
			if ( $get_from_datastore_callback ) {
				$object = $get_from_datastore_callback( $id );
			} else {
				$object = $this->get_from_datastore( $id );
			}

			if ( null === $object ) {
				return null;
			}

			$this->set( $id, $object, $expiration );
			$data = $this->last_cached_data;
		}

		$object = $this->deserialize( $data );

		/**
		 * Filters the deserialized object that is retrieved from the cache engine of an instance of ObjectCache and will be returned by 'get'.
		 *
		 * @since 6.8.0
		 *
		 * @param array|object $object The object after being deserialized.
		 * @param array $data The serialized object data that was retrieved from the cache engine.
		 * @returns array|object The actual deserialized object data that will be returned by 'get'.
		 */
		return apply_filters( "woocommerce_after_deserializing_{$this->object_type}_from_cache", $object, $data, $id );
	}

	/**
	 * Remove an object from the cache.
	 *
	 * @param int|string $id The id of the object to remove.
	 * @return bool True if the object is removed from the cache successfully, false otherwise (because the object wasn't cached or for other reason).
	 */
	public function remove( $id ): bool {
		$result = $this->get_cache_engine()->delete_cached_object( $this->get_cache_key_prefix() . $id );

		/**
		 * Action triggered by an instance of ObjectCache after an object is (attempted to be) removed from the cache.
		 *
		 * @since 6.8.0
		 *
		 * @param int|string $id The id of the object being removed.
		 * @param bool $result True if the object removal succeeded, false otherwise.
		 */
		do_action( "woocommerce_after_removing_{$this->object_type}_from_cache", $id, $result );

		return $result;
	}

	/**
	 * Remove all the objects from the cache.
	 * This is done by forcing the generation of a new cache key prefix
	 * and leaving the old cached objects to expire.
	 *
	 * @return void
	 */
	public function flush(): void {
		delete_option( $this->cache_key_prefix_option_name );
		$this->cache_key_prefix = null;

		/**
		 * Action triggered by an instance of ObjectCache after it flushes all the cached objects.
		 *
		 * @since 6.8.0
		 *
		 * @param ObjectCache $cache_instance The instance of ObjectCache whose 'flush` method has been called.
		 * @param CacheEngine $engine The cache engine in use.
		 */
		do_action( "woocommerce_after_flushing_{$this->object_type}_cache", $this, $this->get_cache_engine() );
	}

	/**
	 * Is a given object cached?
	 *
	 * @param int|string $id The id of the object to check.
	 * @return bool True if there's a cached object with the specified id.
	 */
	public function is_cached( $id ): bool {
		return $this->get_cache_engine()->is_cached( $this->get_cache_key_prefix() . $id );
	}

	/**
	 * Get the id of an object. This is used by 'set' when a null id is passed.
	 * If the object id can't be determined the method must return null.
	 *
	 * @param array|object $object The object to get the id for.
	 * @return int|string|null
	 */
	protected function get_object_id( $object ) {
		return null;
	}

	/**
	 * Validate an object before it's cached.
	 *
	 * @param array|object $object Object to validate.
	 * @return array|null An array with validation error messages, null or an empty array if there are no errors.
	 */
	protected function validate( $object ): ?array {
		return null;
	}

	/**
	 * Convert an object to a serialized form suitable for caching.
	 * If a class overrides this method it should override 'deserialize' as well.
	 *
	 * @param array|object $object The object to serialize.
	 * @return array The serialized object.
	 */
	protected function serialize( $object ): array {
		return array( 'data' => $object );
	}

	/**
	 * Deserializes a set of object data after having been retrieved from the cache.
	 * If a class overrides this method it should override 'serialize' as well.
	 *
	 * @param array $serialized Serialized object data as it was returned by 'validate_and_serialize'.
	 * @return object|array Deserialized object, ready to be returned by 'get'.
	 */
	protected function deserialize( array $serialized ) {
		return $serialized['data'];
	}

	/**
	 * Get an object from an authoritative data store.
	 * This is used by 'get' if the object isn't cached and no custom object retrieval callback is suupplied.
	 *
	 * @param int|string $id The id of the object to get.
	 * @return array|object|null The retrieved object, or null if it's not possible to retrieve an object by the given id.
	 */
	protected function get_from_datastore( $id ) {
		return null;
	}

	/**
	 * Get the instance of the cache engine to use.
	 *
	 * @return CacheEngine
	 */
	protected function get_cache_engine_instance(): CacheEngine {
		return wc_get_container()->get( WpCacheEngine::class );
	}

	/**
	 * Get a random string to be used to compose the cache key prefix.
	 * It should return a different string each time.
	 *
	 * @return string
	 */
	protected function get_random_string(): string {
		return dechex( microtime( true ) * 1000 ) . bin2hex( random_bytes( 8 ) );
	}
}
