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
	 * Shared sample image URL used by WooCommerce tests.
	 *
	 * @var string
	 */
	private const SAMPLE_IMAGE_URL = 'http://cldup.com/Dr1Bczxq4q.png';

	/**
	 * Shared sample image fixture used by WooCommerce tests.
	 *
	 * @var string
	 */
	private const SAMPLE_IMAGE_FILE = 'Dr1Bczxq4q.png';

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
	 * Whether a mocked sample image is waiting to be moved into uploads.
	 *
	 * @var bool
	 */
	private $sample_image_download_pending = false;

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
	public static function setUpBeforeClass(): void {

		if ( ! self::$did_init ) {
			self::init();
		}

		parent::setUpBeforeClass();
	}

	/**
	 * @since 1.3.1
	 */
	public static function tearDownAfterClass(): void {

		self::save_cache();

		parent::tearDownAfterClass();
	}

	/**
	 * Set up for each test.
	 *
	 * @since 1.0.0
	 */
	public function setUp(): void {

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
	public function tearDown(): void {

		parent::tearDown();

		remove_filter( 'pre_http_request', array( $this, 'http_request_listner' ) );
		remove_filter( 'wp_handle_sideload_overrides', array( $this, 'set_sample_image_unique_filename_callback' ) );

		$this->skip_cache_next               = false;
		$this->sample_image_download_pending = false;
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

		if ( false === $preempt && self::SAMPLE_IMAGE_URL === $url ) {
			$preempt = $this->mock_sample_image_response( $request );
		}

		return $preempt;
	}

	/**
	 * Mock the shared sample image download from a local fixture.
	 *
	 * @param array $request The request arguments.
	 * @return array
	 */
	protected function mock_sample_image_response( $request ) {
		$fixture_path = WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/' . self::SAMPLE_IMAGE_FILE;
		$response     = array(
			'headers'  => array(
				'content-type' => 'image/png',
			),
			'body'     => '',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);

		if ( ! empty( $request['filename'] ) ) {
			WC_Unit_Test_Case::file_copy( $fixture_path, $request['filename'] );
			$this->sample_image_download_pending = true;
			add_filter( 'wp_handle_sideload_overrides', array( $this, 'set_sample_image_unique_filename_callback' ), 10, 2 );
			$response['filename'] = $request['filename'];
			return $response;
		}

		$response['body'] = file_get_contents( $fixture_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local test fixture.
		return $response;
	}

	/**
	 * Use a unique filename for the pending sample image download.
	 *
	 * @param array $overrides Sideload overrides.
	 * @param array $file      Sideloaded file data.
	 * @return array
	 */
	public function set_sample_image_unique_filename_callback( $overrides, $file ) {
		if ( ! $this->sample_image_download_pending || self::SAMPLE_IMAGE_FILE !== $file['name'] ) {
			return $overrides;
		}

		$this->sample_image_download_pending = false;
		remove_filter( 'wp_handle_sideload_overrides', array( $this, 'set_sample_image_unique_filename_callback' ) );
		$overrides['unique_filename_callback'] = static function ( $_dir, $name, $ext ) {
			return $name . '-' . wp_generate_uuid4() . $ext;
		};

		return $overrides;
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
