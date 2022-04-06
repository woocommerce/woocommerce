<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Event\SuiteEvent;

use \WP_CLI\Process;
use \WP_CLI\Utils;

// Inside a community package
if ( file_exists( __DIR__ . '/utils.php' ) ) {
	require_once __DIR__ . '/utils.php';
	require_once __DIR__ . '/Process.php';
	$project_composer = dirname( dirname( dirname( __FILE__ ) ) ) . '/composer.json';
	if ( file_exists( $project_composer ) ) {
		$composer = json_decode( file_get_contents( $project_composer ) );
		if ( ! empty( $composer->autoload->files ) ) {
			$contents = 'require:' . PHP_EOL;
			foreach( $composer->autoload->files as $file ) {
				$contents .= '  - ' . dirname( dirname( dirname( __FILE__ ) ) ) . '/' . $file;
			}
			@mkdir( sys_get_temp_dir() . '/wp-cli-package-test/' );
			$project_config = sys_get_temp_dir() . '/wp-cli-package-test/config.yml';
			file_put_contents( $project_config, $contents );
			putenv( 'WP_CLI_CONFIG_PATH=' . $project_config );
		}
	}
// Inside WP-CLI
} else {
	require_once __DIR__ . '/../../php/utils.php';
	require_once __DIR__ . '/../../php/WP_CLI/Process.php';
	require_once __DIR__ . '/../../vendor/autoload.php';
}

/**
 * Features context.
 */
class FeatureContext extends BehatContext implements ClosuredContextInterface {

	private static $cache_dir, $suite_cache_dir;

	private static $db_settings = array(
		'dbname' => 'wp_cli_test',
		'dbuser' => 'wp_cli_test',
		'dbpass' => 'password1',
		'dbhost' => '127.0.0.1',
	);

	private $running_procs = array();

	public $variables = array();

	/**
	 * Get the environment variables required for launched `wp` processes
	 * @beforeSuite
	 */
	private static function get_process_env_variables() {
		// Ensure we're using the expected `wp` binary
		$bin_dir = getenv( 'WP_CLI_BIN_DIR' ) ?: realpath( __DIR__ . "/../../bin" );
		$env = array(
			'PATH' =>  $bin_dir . ':' . getenv( 'PATH' ),
			'BEHAT_RUN' => 1,
			'HOME' => '/tmp/wp-cli-home',
		);
		if ( $config_path = getenv( 'WP_CLI_CONFIG_PATH' ) ) {
			$env['WP_CLI_CONFIG_PATH'] = $config_path;
		}
		return $env;
	}

	// We cache the results of `wp core download` to improve test performance
	// Ideally, we'd cache at the HTTP layer for more reliable tests
	private static function cache_wp_files() {
		self::$cache_dir = sys_get_temp_dir() . '/wp-cli-test core-download-cache';

		if ( is_readable( self::$cache_dir . '/wp-config-sample.php' ) )
			return;

		$cmd = Utils\esc_cmd( 'wp core download --force --path=%s', self::$cache_dir );
		Process::create( $cmd, null, self::get_process_env_variables() )->run_check();
	}

	/**
	 * @BeforeSuite
	 */
	public static function prepare( SuiteEvent $event ) {
		$result = Process::create( 'wp cli info', null, self::get_process_env_variables() )->run_check();
		echo PHP_EOL;
		echo $result->stdout;
		echo PHP_EOL;
		self::cache_wp_files();
	}

	/**
	 * @AfterSuite
	 */
	public static function afterSuite( SuiteEvent $event ) {
		if ( self::$suite_cache_dir ) {
			Process::create( Utils\esc_cmd( 'rm -r %s', self::$suite_cache_dir ), null, self::get_process_env_variables() )->run();
		}
	}

	/**
	 * @BeforeScenario
	 */
	public function beforeScenario( $event ) {
		$this->variables['SRC_DIR'] = realpath( __DIR__ . '/../..' );
	}

	/**
	 * @AfterScenario
	 */
	public function afterScenario( $event ) {
		if ( isset( $this->variables['RUN_DIR'] ) ) {
			// remove altered WP install, unless there's an error
			if ( $event->getResult() < 4 ) {
				$this->proc( Utils\esc_cmd( 'rm -r %s', $this->variables['RUN_DIR'] ) )->run();
			}
		}

		foreach ( $this->running_procs as $proc ) {
			self::terminate_proc( $proc );
		}
	}

	/**
	 * Terminate a process and any of its children.
	 */
	private static function terminate_proc( $proc ) {
		$status = proc_get_status( $proc );

		$pid = $status['pid'];

		$output = `ps -o ppid,pid,command | grep $pid`;

		foreach ( explode( PHP_EOL, $output ) as $line ) {
			if ( preg_match( '/^\s*(\d+)\s+(\d+)/', $line, $matches ) ) {
				$parent = $matches[1];
				$child = $matches[2];

				if ( $parent == $pid ) {
					if ( ! posix_kill( (int) $child, 9 ) ) {
						throw new RuntimeException( posix_strerror( posix_get_last_error() ) );
					}
				}
			}
		}

		if ( ! posix_kill( (int) $pid, 9 ) ) {
			throw new RuntimeException( posix_strerror( posix_get_last_error() ) );
		}
	}

	public static function create_cache_dir() {
		self::$suite_cache_dir = sys_get_temp_dir() . '/' . uniqid( "wp-cli-test-suite-cache-", TRUE );
		mkdir( self::$suite_cache_dir );
		return self::$suite_cache_dir;
	}

	/**
	 * Initializes context.
	 * Every scenario gets it's own context object.
	 *
	 * @param array $parameters context parameters (set them up through behat.yml)
	 */
	public function __construct( array $parameters ) {
		$this->drop_db();
		$this->set_cache_dir();
		$this->variables['CORE_CONFIG_SETTINGS'] = Utils\assoc_args_to_str( self::$db_settings );
	}

	public function getStepDefinitionResources() {
		return glob( __DIR__ . '/../steps/*.php' );
	}

	public function getHookDefinitionResources() {
		return array();
	}

	public function replace_variables( $str ) {
		return preg_replace_callback( '/\{([A-Z_]+)\}/', array( $this, '_replace_var' ), $str );
	}

	private function _replace_var( $matches ) {
		$cmd = $matches[0];

		foreach ( array_slice( $matches, 1 ) as $key ) {
			$cmd = str_replace( '{' . $key . '}', $this->variables[ $key ], $cmd );
		}

		return $cmd;
	}

	public function create_run_dir() {
		if ( !isset( $this->variables['RUN_DIR'] ) ) {
			$this->variables['RUN_DIR'] = sys_get_temp_dir() . '/' . uniqid( "wp-cli-test-run-", TRUE );
			mkdir( $this->variables['RUN_DIR'] );
		}
	}

	public function build_phar( $version = 'same' ) {
		$this->variables['PHAR_PATH'] = $this->variables['RUN_DIR'] . '/' . uniqid( "wp-cli-build-", TRUE ) . '.phar';

		$this->proc( Utils\esc_cmd(
			'php -dphar.readonly=0 %1$s %2$s --version=%3$s && chmod +x %2$s',
			__DIR__ . '/../../utils/make-phar.php',
			$this->variables['PHAR_PATH'],
			$version
		) )->run_check();
	}

	private function set_cache_dir() {
		$path = sys_get_temp_dir() . '/wp-cli-test-cache';
		$this->proc( Utils\esc_cmd( 'mkdir -p %s', $path ) )->run_check();
		$this->variables['CACHE_DIR'] = $path;
	}

	private static function run_sql( $sql ) {
		Utils\run_mysql_command( 'mysql --no-defaults', array(
			'execute' => $sql,
			'host' => self::$db_settings['dbhost'],
			'user' => self::$db_settings['dbuser'],
			'pass' => self::$db_settings['dbpass'],
		) );
	}

	public function create_db() {
		$dbname = self::$db_settings['dbname'];
		self::run_sql( "CREATE DATABASE IF NOT EXISTS $dbname" );
	}

	public function drop_db() {
		$dbname = self::$db_settings['dbname'];
		self::run_sql( "DROP DATABASE IF EXISTS $dbname" );
	}

	public function proc( $command, $assoc_args = array(), $path = '' ) {
		if ( !empty( $assoc_args ) )
			$command .= Utils\assoc_args_to_str( $assoc_args );

		$env = self::get_process_env_variables();
		if ( isset( $this->variables['SUITE_CACHE_DIR'] ) ) {
			$env['WP_CLI_CACHE_DIR'] = $this->variables['SUITE_CACHE_DIR'];
		}

		if ( isset( $this->variables['RUN_DIR'] ) ) {
			$cwd = "{$this->variables['RUN_DIR']}/{$path}";
		} else {
			$cwd = null;
		}

		return Process::create( $command, $cwd, $env );
	}

	/**
	 * Start a background process. Will automatically be closed when the tests finish.
	 */
	public function background_proc( $cmd ) {
		$descriptors = array(
			0 => STDIN,
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		$proc = proc_open( $cmd, $descriptors, $pipes, $this->variables['RUN_DIR'], self::get_process_env_variables() );

		sleep(1);

		$status = proc_get_status( $proc );

		if ( !$status['running'] ) {
			throw new RuntimeException( stream_get_contents( $pipes[2] ) );
		} else {
			$this->running_procs[] = $proc;
		}
	}

	public function move_files( $src, $dest ) {
		rename( $this->variables['RUN_DIR'] . "/$src", $this->variables['RUN_DIR'] . "/$dest" );
	}

	public function add_line_to_wp_config( &$wp_config_code, $line ) {
		$token = "/* That's all, stop editing!";

		$wp_config_code = str_replace( $token, "$line\n\n$token", $wp_config_code );
	}

	public function download_wp( $subdir = '' ) {
		$dest_dir = $this->variables['RUN_DIR'] . "/$subdir";

		if ( $subdir ) {
			mkdir( $dest_dir );
		}

		$this->proc( Utils\esc_cmd( "cp -r %s/* %s", self::$cache_dir, $dest_dir ) )->run_check();

		// disable emailing
		mkdir( $dest_dir . '/wp-content/mu-plugins' );
		copy( __DIR__ . '/../extra/no-mail.php', $dest_dir . '/wp-content/mu-plugins/no-mail.php' );
		symlink( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ),  $dest_dir . '/wp-content/plugins/woocommerce' );
	}

	public function create_config( $subdir = '' ) {
		$params = self::$db_settings;
		$params['dbprefix'] = $subdir ?: 'wp_';

		$params['skip-salts'] = true;
		$this->proc( 'wp core config', $params, $subdir )->run_check();
	}

	public function install_wp( $subdir = '' ) {
		$this->create_db();
		$this->create_run_dir();
		$this->download_wp( $subdir );

		$this->create_config( $subdir );

		$install_args = array(
			'url' => 'http://example.com',
			'title' => 'WP CLI Site',
			'admin_user' => 'admin',
			'admin_email' => 'admin@example.com',
			'admin_password' => 'password1'
		);

		$this->proc( 'wp core install', $install_args, $subdir )->run_check();
		$this->proc( 'wp plugin activate woocommerce', array(), $subdir )->run_check();
	}
}
