<?php

/**
 * A test case parent for testing HTTP requests.
 *
 * @package WP_HTTP_Testcase
 * @since 1.0.0
 */

use Automattic\Jetpack\Constants;

/**
 * Parent test case for tests involving HTTP requests.
 *
 * @since 1.0.0
 */
abstract class WP_HTTP_TestCase extends WP_UnitTestCase {

	/**
	 * The HTTP requests caught.
	 *
	 * Each of the requests has the following keys:
	 * {
	 *    @type string $url     The URL for the request.
	 *    @type array  $request The request arguments.
	 * }
	 *
	 * @since 1.0.0
	 *
	 * @var array $http_requests
	 */
	protected $http_requests;

	/**
	 * A function to simulate responses to requests.
	 *
	 * @since 1.0.0
	 *
	 * @type callable|false $http_responder
	 */
	protected $http_responder;

	/**
	 * Whether the class has been initialized.
	 *
	 * @since 1.3.0
	 *
	 * @var bool
	 */
	protected static $did_init = false;

	/**
	 * The local host to route requests to in 'local' mode.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected static $host;

	/**
	 * Whether to use caching.
	 *
	 * @since 1.1.0
	 *
	 * @var bool
	 */
	protected static $use_caching = true;

	/**
	 * The request fields to use when generating the cache key.
	 *
	 * Only the keys are used. The values are meaningless and are completely ignored.
	 *
	 * @since 1.3.0
	 *
	 * @var array
	 */
	protected static $cache_request_fields = array(
		'method'  => 1,
		'headers' => 1,
		'cookies' => 1,
		'body'    => 1,
	);

	/**
	 * The directory the cache files are in.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected static $cache_dir;

	/**
	 * The cache group to use.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected static $cache_group = 'default';

	/**
	 * The currently loaded cache.
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	protected static $cache;

	/**
	 * Whether the cache has changed.
	 *
	 * @since 1.1.0
	 *
	 * @var bool
	 */
	protected static $cache_changed;

	/**
	 * Whether to skip just the next cache hit and put the request through.
	 *
	 * When true, the cache won't be checked for the next request, but the response
	 * will still overwrite the existing cache.
	 *
	 * @since 1.2.0
	 *
	 * @var bool
	 */
	protected $skip_cache_next = false;

	/**
	 * @since 1.3.0
	 */
	public static function setUpBeforeClass() {

		if ( ! self::$did_init ) {
			self::init();
		}

		parent::setUpBeforeClass();
	}

	/**
	 * @since 1.3.1
	 */
	public static function tearDownAfterClass() {

		self::save_cache();

		parent::tearDownAfterClass();
	}

	/**
	 * Set up for each test.
	 *
	 * @since 1.0.0
	 */
	public function setUp() {

		parent::setUp();

		$this->http_requests = array();

		if ( ! empty( self::$host ) ) {
			$this->http_responder = array( $this, 'route_request' );
		}

		add_filter( 'pre_http_request', array( $this, 'http_request_listner' ), 10, 3 );
	}

	/**
	 * Clean up the filters after each test.
	 *
	 * @since 1.0.0
	 */
	public function tearDown() {

		parent::tearDown();

		remove_filter( 'pre_http_request', array( $this, 'http_request_listner' ) );

		$this->skip_cache_next = false;
	}

	//
	// Helpers.
	//

	/**
	 * Mock responses to HTTP requests coming from WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @WordPress\filter pre_http_request Added by self::setUp().
	 *
	 * @param mixed  $preempt Response to the request, or false to not preempt it.
	 * @param array  $request The request arguments.
	 * @param string $url     The URL the request is being made to.
	 *
	 * @return mixed A response, or false.
	 */
	public function http_request_listner( $preempt, $request, $url ) {

		$this->http_requests[] = array(
			'url'     => $url,
			'request' => $request,
		);

		if ( $this->http_responder ) {
			$preempt = call_user_func( $this->http_responder, $request, $url );
		}

		return $preempt;
	}

	/**
	 * Route a request through to a predefined host, with optional caching.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $request The request to route.
	 * @param string $url     The URL the request is for.
	 *
	 * @return array|bool|false|WP_Error The response.
	 */
	protected function route_request( $request, $url ) {

		// Check the cache.
		$cache_key = $this->get_cache_key( $request, $url );
		$cached    = $this->get_cached_response( $cache_key );

		if ( $cached ) {
			return $cached;
		}

		// Get the URL host.
		$host = parse_url( $url, PHP_URL_HOST );

		// If the host is already correct, return false so the request continues.
		if ( $host === self::$host ) {
			return false;
		}

		$url = str_replace( $host, self::$host, $url );

		$response = wp_remote_request( $url, $request );

		$this->cache_response( $cache_key, $response );

		return $response;
	}

	/**
	 * Get the cache key for a request.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $request The request.
	 * @param string $url The URL the request is for.
	 *
	 * @return string|false The cache key for the request. False if not caching.
	 */
	protected function get_cache_key( $request, $url ) {

		if ( ! self::$use_caching ) {
			return false;
		}

		$request = array_intersect_key( $request, self::$cache_request_fields );

		return md5( serialize( $request ) . $url );
	}

	/**
	 * Get the cached response to a request.
	 *
	 * @since 1.1.0
	 *
	 * @param string $cache_key The cache key for the request.
	 *
	 * @return array|false The cached response, or false if none.
	 */
	protected function get_cached_response( $cache_key ) {

		if ( ! self::$use_caching ) {
			return false;
		}

		// If we're to skip the cache this time, return false.
		if ( $this->skip_cache_next ) {
			$this->skip_cache_next = false;
			return false;
		}

		if ( ! isset( self::$cache[ $cache_key ] ) ) {
			return false;
		}

		return self::$cache[ $cache_key ];
	}

	/**
	 * Save a response to the cache.
	 *
	 * @since 1.1.0
	 *
	 * @param string $cache_key The cache key for the request.
	 * @param array  $response  The response.
	 */
	protected function cache_response( $cache_key, $response ) {

		if ( ! self::$use_caching ) {
			return;
		}

		self::$cache[ $cache_key ] = $response;
		self::$cache_changed       = true;
	}

	//
	// Static Functions.
	//

	/**
	 * Initialize the class.
	 *
	 * @since 1.1.0
	 */
	public static function init() {

		self::load_env( 'HOST' );
		self::load_env( 'USE_CACHING', true );

		self::load_cache();

		self::$did_init = true;
	}

	/**
	 * Get an environment setting.
	 *
	 * @since 1.1.0
	 *
	 * @param string $var     The name of the setting to get.
	 * @param mixed  $default The default value for this setting.
	 *
	 * @return mixed|null|string
	 */
	protected static function get_env( $var, $default = null ) {

		$value = getenv( 'WP_HTTP_TC_' . $var );

		if ( false !== $value ) {
			return $value;
		}

		if ( ! Constants::is_defined( 'WP_HTTP_TC_' . $var ) ) {
			return $default;
		}

		return Constants::get_constant( 'WP_HTTP_TC_' . $var );
	}

	/**
	 * Get an environment setting and assign it to the corresponding property.
	 *
	 * @since 1.2.0
	 *
	 * @param string $var     The var name.
	 * @param bool   $is_bool Whether this is a boolean property.
	 */
	protected static function load_env( $var, $is_bool = false ) {

		$property = strtolower( $var );

		self::$$property = self::get_env( $var, self::$$property );

		if ( $is_bool ) {
			self::$$property = (bool) self::$$property;
		}
	}

	/**
	 * Load the cache if caching is in use.
	 *
	 * @since 1.1.0
	 */
	protected static function load_cache() {

		if ( ! self::$use_caching ) {
			return;
		}

		$request_fields = self::get_env( 'CACHE_REQUEST_FIELDS' );

		if ( null !== $request_fields ) {
			self::$cache_request_fields = array_flip(
				array_map( 'trim', explode( ',', $request_fields ) )
			);
		}

		self::load_env( 'CACHE_GROUP' );

		self::$cache_dir = self::get_env( 'CACHE_DIR', dirname( __FILE__ ) );

		$cache_file = self::$cache_dir . '/' . self::$cache_group;

		if ( ! file_exists( $cache_file ) ) {
			return;
		}

		$cache = file_get_contents( $cache_file );

		self::$cache = unserialize( $cache );
	}

	/**
	 * Save the cache.
	 *
	 * @since 1.1.0
	 */
	public static function save_cache() {

		if ( ! self::$cache_changed ) {
			return;
		}

		// phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_file_put_contents
		file_put_contents(
			self::$cache_dir . '/' . self::$cache_group,
			serialize( self::$cache )
		);
	}
}

if ( ! Constants::is_defined( 'WP_HTTP_TC_NO_BACKPAT' ) ) {
	abstract class WP_HTTP_UnitTestCase extends WP_HTTP_TestCase {}
}

// EOF
