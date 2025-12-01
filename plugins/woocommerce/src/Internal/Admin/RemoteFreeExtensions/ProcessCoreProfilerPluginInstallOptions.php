<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use WC_Logger_Interface;

/**
 * Process install options for plugins.
 */
class ProcessCoreProfilerPluginInstallOptions {
	/**
	 * List of plugins.
	 *
	 * @var array List of plugins
	 */
	private array $plugins;

	/**
	 * Plugin slug.
	 *
	 * @var string Plugin slug
	 */
	private string $slug;

	/**
	 * Logger instance.
	 *
	 * @var WC_Logger_Interface Logger instance
	 */
	private WC_Logger_Interface $logger;

	private const DISALLOWED_OPTIONS = array(
		'siteurl',              // The URL to your WordPress installation.
		'home',                 // The home URL of the site.
		'admin_email',          // Administrator email address.
		'wp_user_roles',        // Serialized roles and capabilities.
		'active_plugins',       // List of active plugins.
		'template',             // The current theme template.
		'stylesheet',           // The current theme stylesheet.
		'default_role',         // Default role for new users.
		'ftp_hostname',         // FTP server hostname.
		'ftp_username',         // FTP server username.
		'ftp_password',         // FTP server password.
		'ftp_port',             // FTP server port.
		'ftp_ssl',              // Whether to use FTP over SSL.
		'ftp_pasv',             // Whether to use passive FTP.
		'rewrite_rules',        // URL rewrite rules.
		'permalink_structure',  // Structure of permalinks.
		'cron',                 // Scheduled tasks (WP-Cron jobs).
		'upload_path',          // Filesystem path for uploads.
		'upload_url_path',      // URL path for uploads.
		'mailserver_url',       // Mail server hostname.
		'mailserver_login',     // Mail server login.
		'mailserver_pass',      // Mail server password.
		'mailserver_port',       // Mail server port.
	);

	/**
	 * Constructor.
	 *
	 * @param array                    $plugins List of plugins.
	 * @param string                   $slug Plugin slug.
	 * @param WC_Logger_Interface|null $logger Logger instance.
	 */
	public function __construct( array $plugins, string $slug, ?WC_Logger_Interface $logger = null ) {
		$this->plugins = $plugins;
		$this->slug    = $slug;
		$this->logger  = $logger ?? wc_get_logger();
	}

	/**
	 * Retrieve install options for a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array|null Install options or null if not found.
	 */
	public function get_install_options( string $plugin_slug ): ?array {
		foreach ( $this->plugins as $plugin ) {
			if ( $this->matches_plugin_slug( $plugin, $plugin_slug ) ) {
				return $plugin->install_options ?? null;
			}
		}
		return null;
	}

	/**
	 * Process install options based on a filtering function.
	 */
	public function process_install_options() {
		$install_options = $this->get_install_options( $this->slug );
		if ( ! $install_options ) {
			return;
		}

		foreach ( $install_options as $install_option ) {
			$this->add_install_option( $install_option );
		}
	}

	/**
	 * Updates an install option in the WordPress database.
	 *
	 * @param object $install_option Install option object.
	 */
	protected function add_install_option( object $install_option ) {
		$default_options = array(
			'force_array' => false,
			'autoload'    => false,
		);

		$options = isset( $install_option->options )
			? (object) $install_option->options
			: new \stdClass();

		foreach ( $default_options as $key => $value ) {
			if ( ! isset( $options->$key ) ) {
				$options->$key = $value;
			}
		}

		if ( $options->force_array ) {
			$install_option->value = json_decode( wp_json_encode( $install_option->value ), true );
			// In case of JSON error, return early.
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$this->logger && $this->logger->error( 'Failed to decode JSON for install option value for ' . $install_option->name . ': ' . json_last_error_msg() );
				return;
			}
		}

		$autoload = null;

		if ( isset( $options->autoload ) ) {
			if ( 'yes' === $options->autoload ) {
				$autoload = true;
			} elseif ( 'no' === $options->autoload ) {
				$autoload = false;
			} elseif ( true === $options->autoload || false === $options->autoload ) {
				$autoload = $options->autoload;
			}
		}

		$this->add_option( $install_option->name, $install_option->value, $autoload );
	}

	/**
	 * Updates an option in the WordPress database.
	 *
	 * @param string $name Option name.
	 * @param mixed  $value Option value.
	 * @param string $autoload Autoload option.
	 *
	 * @return void
	 */
	protected function add_option( string $name, $value, $autoload = null ) {
		if ( in_array( $name, self::DISALLOWED_OPTIONS, true ) ) {
			$this->logger && $this->logger->error( 'Disallowed option: ' . $name );
			return;
		}

		add_option( $name, $value, '', $autoload );
	}

	/**
	 * Checks if the given plugin matches the provided slug.
	 *
	 * @param object $plugin Plugin object.
	 * @param string $plugin_slug Plugin slug.
	 * @return bool True if it matches, false otherwise.
	 */
	private function matches_plugin_slug( object $plugin, string $plugin_slug ): bool {
		return explode( ':', $plugin->key )[0] === $plugin_slug;
	}
}
