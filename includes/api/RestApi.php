<?php
/**
 * Load REST API Namespaces.
 *
 * @package WooCommerce/RestAPI/Classes
 */

namespace WC\RestAPI;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading REST API Namespaces.
 */
class RestApi {

	/**
	 * REST API namespaces.
	 *
	 * @var array
	 */
	protected $namespaces = array();

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	private function __wakeup() {}

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		spl_autoload_register( array( $this, 'autoload' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Take a class name and turn it into a file name.
	 *
	 * @param  string $class  Class name.
	 * @param  string $prefix Class prefix.
	 * @return string
	 */
	protected function get_file_name_from_class( $class, $prefix = 'class-' ) {
		return $prefix . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Class Autoloader.
	 *
	 * @param string $class Classname.
	 */
	public function autoload( $class ) {
		$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '\\' . __NAMESPACE__ . '\\', '', $class ) );

		// Non-namespaced files.
		if ( stristr( $class, 'WC_REST_' ) ) {
			if ( stristr( $class, '_V1_' ) ) {
				$dir = dirname( __FILE__ ) . '/includes/v1/';
			} elseif ( stristr( $class, '_V2_' ) ) {
				$dir = dirname( __FILE__ ) . '/includes/v2/';
			} elseif ( stristr( $class, 'WC_REST_Blocks' ) ) {
				$dir = dirname( __FILE__ ) . '/includes/wc-blocks/';
			} else {
				$dir = dirname( __FILE__ ) . '/includes/v3/';
			}

			$file = $this->get_file_name_from_class( $class );

			if ( file_exists( "{$dir}{$file}" ) ) {
				include "{$dir}{$file}";
				return;
			}

			$file = $this->get_file_name_from_class( $class, 'abstract-' );

			if ( file_exists( dirname( __FILE__ ) . "/includes/abstracts/{$file}" ) ) {
				include dirname( __FILE__ ) . "/includes/abstracts/{$file}";
				return;
			}
		}

		if ( file_exists( dirname( __FILE__ ) . "/includes/{$class}.php" ) ) {
			include dirname( __FILE__ ) . "/includes/{$class}.php";
			return;
		}
	}

	/**
	 * Get API namespaces.
	 */
	protected function get_namespaces() {
		return array(
			'wc/v1'        => 'WC_Rest_API_V1',
			'wc/v2'        => 'WC_Rest_API_V2',
			'wc/v3'        => 'WC_Rest_API_V3',
			'wc-blocks/v1' => 'WC_Rest_API_Blocks_V1',
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_namespaces() as $namespace => $classname ) {
			$api = new $classname();
			$api->includes();

			foreach ( $api->get_controllers() as $controller ) {
				$this->$controller = new $controller();
				$this->$controller->register_routes();
			}
		}
	}
}
