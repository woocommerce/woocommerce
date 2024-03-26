<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\File;
use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WC_Admin_Settings;
use WC_Log_Handler_DB, WC_Log_Handler_File, WC_Log_Levels;

/**
 * Settings class.
 */
class Settings {

	use AccessiblePrivateMethods;

	/**
	 * Default values for logging settings.
	 *
	 * @const array
	 */
	private const DEFAULTS = array(
		'logging_enabled'       => true,
		'default_handler'       => LogHandlerFileV2::class,
		'retention_period_days' => 30,
		'level_threshold'       => 'none',
	);

	/**
	 * The prefix for settings keys used in the options table.
	 *
	 * @const string
	 */
	private const PREFIX = 'woocommerce_logs_';

	/**
	 * Class Settings.
	 */
	public function __construct() {
		self::add_action( 'wc_logs_load_tab', array( $this, 'save_settings' ) );
	}

	/**
	 * Get the directory for storing log files.
	 *
	 * The `wp_upload_dir` function takes into account the possibility of multisite, and handles changing
	 * the directory if the context is switched to a different site in the network mid-request.
	 *
	 * @return string The full directory path, with trailing slash.
	 */
	public static function get_log_directory(): string {
		if ( true === Constants::get_constant( 'WC_LOG_DIR_CUSTOM' ) ) {
			$dir = Constants::get_constant( 'WC_LOG_DIR' );
		} else {
			$upload_dir = wc_get_container()->get( LegacyProxy::class )->call_function( 'wp_upload_dir' );

			/**
			 * Filter to change the directory for storing WooCommerce's log files.
			 *
			 * @param string $dir The full directory path, with trailing slash.
			 *
			 * @since 8.8.0
			 */
			$dir = apply_filters( 'woocommerce_log_directory', $upload_dir['basedir'] . '/wc-logs/' );
		}

		$dir = trailingslashit( $dir );

		$realpath = realpath( $dir );
		if ( false === $realpath ) {
			$result = wp_mkdir_p( $dir );

			if ( true === $result ) {
				// Create infrastructure to prevent listing contents of the logs directory.
				try {
					$filesystem = FilesystemUtil::get_wp_filesystem();
					$filesystem->put_contents( $dir . '.htaccess', 'deny from all' );
					$filesystem->put_contents( $dir . 'index.html', '' );
				} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Creation failed.
				}
			}
		}

		return $dir;
	}

	/**
	 * The definitions used by WC_Admin_Settings to render and save settings controls.
	 *
	 * @return array
	 */
	private function get_settings_definitions(): array {
		$settings = array(
			'start'                 => array(
				'title' => __( 'Logs settings', 'woocommerce' ),
				'id'    => self::PREFIX . 'settings',
				'type'  => 'title',
			),
			'logging_enabled'       => array(
				'title'    => __( 'Logger', 'woocommerce' ),
				'desc'     => __( 'Enable logging', 'woocommerce' ),
				'id'       => self::PREFIX . 'logging_enabled',
				'type'     => 'checkbox',
				'value'    => $this->logging_is_enabled() ? 'yes' : 'no',
				'default'  => self::DEFAULTS['logging_enabled'] ? 'yes' : 'no',
				'autoload' => false,
			),
			'default_handler'       => array(),
			'retention_period_days' => array(),
			'level_threshold'       => array(),
			'end'                   => array(
				'id'   => self::PREFIX . 'settings',
				'type' => 'sectionend',
			),
		);

		if ( true === $this->logging_is_enabled() ) {
			$settings['default_handler']       = $this->get_default_handler_setting_definition();
			$settings['retention_period_days'] = $this->get_retention_period_days_setting_definition();
			$settings['level_threshold']       = $this->get_level_threshold_setting_definition();

			$default_handler = $this->get_default_handler();
			if ( in_array( $default_handler, array( LogHandlerFileV2::class, WC_Log_Handler_File::class ), true ) ) {
				$settings += $this->get_filesystem_settings_definitions();
			} elseif ( WC_Log_Handler_DB::class === $default_handler ) {
				$settings += $this->get_database_settings_definitions();
			}
		}

		return $settings;
	}

	/**
	 * The definition for the default_handler setting.
	 *
	 * @return array
	 */
	private function get_default_handler_setting_definition(): array {
		$handler_options = array(
			LogHandlerFileV2::class  => __( 'File system (default)', 'woocommerce' ),
			WC_Log_Handler_DB::class => __( 'Database (not recommended on live sites)', 'woocommerce' ),
		);

		/**
		 * Filter the list of logging handlers that can be set as the default handler.
		 *
		 * @param array $handler_options An associative array of class_name => description.
		 *
		 * @since 8.6.0
		 */
		$handler_options = apply_filters( 'woocommerce_logger_handler_options', $handler_options );

		$current_value = $this->get_default_handler();
		if ( ! array_key_exists( $current_value, $handler_options ) ) {
			$handler_options[ $current_value ] = $current_value;
		}

		$desc = array();

		$desc[] = __( 'Note that if this setting is changed, any log entries that have already been recorded will remain stored in their current location, but will not migrate.', 'woocommerce' );

		$hardcoded = ! is_null( Constants::get_constant( 'WC_LOG_HANDLER' ) );
		if ( $hardcoded ) {
			$desc[] = sprintf(
				// translators: %s is the name of a code variable.
				__( 'This setting cannot be changed here because it is defined in the %s constant.', 'woocommerce' ),
				'<code>WC_LOG_HANDLER</code>'
			);
		}

		return array(
			'title'       => __( 'Log storage', 'woocommerce' ),
			'desc_tip'    => __( 'This determines where log entries are saved.', 'woocommerce' ),
			'id'          => self::PREFIX . 'default_handler',
			'type'        => 'radio',
			'value'       => $current_value,
			'default'     => self::DEFAULTS['default_handler'],
			'autoload'    => false,
			'options'     => $handler_options,
			'disabled'    => $hardcoded ? array_keys( $handler_options ) : array(),
			'desc'        => implode( '<br><br>', $desc ),
			'desc_at_end' => true,
		);
	}

	/**
	 * The definition for the retention_period_days setting.
	 *
	 * @return array
	 */
	private function get_retention_period_days_setting_definition(): array {
		$custom_attributes = array(
			'min'  => 1,
			'step' => 1,
		);

		$desc = array();

		$hardcoded = has_filter( 'woocommerce_logger_days_to_retain_logs' );
		if ( $hardcoded ) {
			$custom_attributes['disabled'] = 'true';

			$desc[] = sprintf(
				// translators: %s is the name of a filter hook.
				__( 'This setting cannot be changed here because it is being set by a filter on the %s hook.', 'woocommerce' ),
				'<code>woocommerce_logger_days_to_retain_logs</code>'
			);
		}

		$file_delete_has_filter = LogHandlerFileV2::class === $this->get_default_handler() && has_filter( 'woocommerce_logger_delete_expired_file' );
		if ( $file_delete_has_filter ) {
			$desc[] = sprintf(
				// translators: %s is the name of a filter hook.
				__( 'The %s hook has a filter set, so some log files may have different retention settings.', 'woocommerce' ),
				'<code>woocommerce_logger_delete_expired_file</code>'
			);
		}

		return array(
			'title'             => __( 'Retention period', 'woocommerce' ),
			'desc_tip'          => __( 'This sets how many days log entries will be kept before being auto-deleted.', 'woocommerce' ),
			'id'                => self::PREFIX . 'retention_period_days',
			'type'              => 'number',
			'value'             => $this->get_retention_period(),
			'default'           => self::DEFAULTS['retention_period_days'],
			'autoload'          => false,
			'custom_attributes' => $custom_attributes,
			'css'               => 'width:70px;',
			'row_class'         => 'logs-retention-period-days',
			'suffix'            => sprintf(
				' %s',
				__( 'days', 'woocommerce' ),
			),
			'desc'              => implode( '<br><br>', $desc ),
		);
	}

	/**
	 * The definition for the level_threshold setting.
	 *
	 * @return array
	 */
	private function get_level_threshold_setting_definition(): array {
		$hardcoded = ! is_null( Constants::get_constant( 'WC_LOG_THRESHOLD' ) );
		$desc      = '';
		if ( $hardcoded ) {
			$desc = sprintf(
				// translators: %1$s is the name of a code variable. %2$s is the name of a file.
				__( 'This setting cannot be changed here because it is defined in the %1$s constant, probably in your %2$s file.', 'woocommerce' ),
				'<code>WC_LOG_THRESHOLD</code>',
				'<b>wp-config.php</b>'
			);
		}

		$labels         = WC_Log_Levels::get_all_level_labels();
		$labels['none'] = __( 'None', 'woocommerce' );

		$custom_attributes = array();
		if ( $hardcoded ) {
			$custom_attributes['disabled'] = 'true';
		}

		return array(
			'title'             => __( 'Level threshold', 'woocommerce' ),
			'desc_tip'          => __( 'This sets the minimum severity level of logs that will be stored. Lower severity levels will be ignored. "None" means all logs will be stored.', 'woocommerce' ),
			'id'                => self::PREFIX . 'level_threshold',
			'type'              => 'select',
			'value'             => $this->get_level_threshold(),
			'default'           => self::DEFAULTS['level_threshold'],
			'autoload'          => false,
			'options'           => $labels,
			'custom_attributes' => $custom_attributes,
			'css'               => 'width:auto;',
			'desc'              => $desc,
		);
	}

	/**
	 * The definitions used by WC_Admin_Settings to render settings related to filesystem log handlers.
	 *
	 * @return array
	 */
	private function get_filesystem_settings_definitions(): array {
		$location_info = array();
		$directory     = self::get_log_directory();

		try {
			FilesystemUtil::get_wp_filesystem();
		} catch ( Exception $exception ) {
			$location_info[] = __( '⚠️ The file system connection could not be initialized. You may want to switch to the database for log storage.', 'woocommerce' );
		}

		$location_info[] = sprintf(
			// translators: %s is a location in the filesystem.
			__( 'Log files are stored in this directory: %s', 'woocommerce' ),
			sprintf(
				'<code>%s</code>',
				esc_html( $directory )
			)
		);

		if ( ! wp_is_writable( $directory ) ) {
			$location_info[] = __( '⚠️ This directory does not appear to be writable.', 'woocommerce' );
		}

		$location_info[] = sprintf(
			// translators: %s is an amount of computer disk space, e.g. 5 KB.
			__( 'Directory size: %s', 'woocommerce' ),
			size_format( wc_get_container()->get( FileController::class )->get_log_directory_size() )
		);

		return array(
			'file_start'    => array(
				'title' => __( 'File system settings', 'woocommerce' ),
				'id'    => self::PREFIX . 'settings',
				'type'  => 'title',
			),
			'log_directory' => array(
				'title' => __( 'Location', 'woocommerce' ),
				'type'  => 'info',
				'text'  => implode( "\n\n", $location_info ),
			),
			'entry_format'  => array(),
			'file_end'      => array(
				'id'   => self::PREFIX . 'settings',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * The definitions used by WC_Admin_Settings to render settings related to database log handlers.
	 *
	 * @return array
	 */
	private function get_database_settings_definitions(): array {
		global $wpdb;
		$table = "{$wpdb->prefix}woocommerce_log";

		$location_info = sprintf(
			// translators: %s is the name of a table in the database.
			__( 'Log entries are stored in this database table: %s', 'woocommerce' ),
			"<code>$table</code>"
		);

		return array(
			'file_start'     => array(
				'title' => __( 'Database settings', 'woocommerce' ),
				'id'    => self::PREFIX . 'settings',
				'type'  => 'title',
			),
			'database_table' => array(
				'title' => __( 'Location', 'woocommerce' ),
				'type'  => 'info',
				'text'  => $location_info,
			),
			'file_end'       => array(
				'id'   => self::PREFIX . 'settings',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Handle the submission of the settings form and update the settings values.
	 *
	 * @param string $view The current view within the Logs tab.
	 *
	 * @return void
	 */
	private function save_settings( string $view ): void {
		$is_saving = 'settings' === $view && isset( $_POST['save_settings'] );

		if ( $is_saving ) {
			check_admin_referer( self::PREFIX . 'settings' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage logging settings.', 'woocommerce' ) );
			}

			$settings = $this->get_settings_definitions();

			WC_Admin_Settings::save_fields( $settings );
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_form(): void {
		$settings = $this->get_settings_definitions();

		?>
		<form id="mainform" class="wc-logs-settings" method="post">
			<?php WC_Admin_Settings::output_fields( $settings ); ?>
			<?php
			/**
			 * Action fires after the built-in logging settings controls have been rendered.
			 *
			 * This is intended as a way to allow other logging settings controls to be added by extensions.
			 *
			 * @param bool $enabled True if logging is currently enabled.
			 *
			 * @since 8.6.0
			 */
			do_action( 'wc_logs_settings_form_fields', $this->logging_is_enabled() );
			?>
			<?php wp_nonce_field( self::PREFIX . 'settings' ); ?>
			<?php submit_button( __( 'Save changes', 'woocommerce' ), 'primary', 'save_settings' ); ?>
		</form>
		<?php
	}

	/**
	 * Determine the current value of the logging_enabled setting.
	 *
	 * @return bool
	 */
	public function logging_is_enabled(): bool {
		$key = self::PREFIX . 'logging_enabled';

		$enabled = WC_Admin_Settings::get_option( $key, self::DEFAULTS['logging_enabled'] );
		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

		if ( is_null( $enabled ) ) {
			$enabled = self::DEFAULTS['logging_enabled'];
		}

		return $enabled;
	}

	/**
	 * Determine the current value of the default_handler setting.
	 *
	 * @return string
	 */
	public function get_default_handler(): string {
		$key = self::PREFIX . 'default_handler';

		$handler = Constants::get_constant( 'WC_LOG_HANDLER' );

		if ( is_null( $handler ) ) {
			$handler = WC_Admin_Settings::get_option( $key );
		}

		if ( ! class_exists( $handler ) || ! is_a( $handler, 'WC_Log_Handler_Interface', true ) ) {
			$handler = self::DEFAULTS['default_handler'];
		}

		return $handler;
	}

	/**
	 * Determine the current value of the retention_period_days setting.
	 *
	 * @return int
	 */
	public function get_retention_period(): int {
		$key = self::PREFIX . 'retention_period_days';

		$retention_period = self::DEFAULTS['retention_period_days'];

		if ( has_filter( 'woocommerce_logger_days_to_retain_logs' ) ) {
			/**
			 * Filter the retention period of log entries.
			 *
			 * @param int $days The number of days to retain log entries.
			 *
			 * @since 3.4.0
			 */
			$retention_period = apply_filters( 'woocommerce_logger_days_to_retain_logs', $retention_period );
		} else {
			$retention_period = WC_Admin_Settings::get_option( $key );
		}

		$retention_period = absint( $retention_period );

		if ( $retention_period < 1 ) {
			$retention_period = self::DEFAULTS['retention_period_days'];
		}

		return $retention_period;
	}

	/**
	 * Determine the current value of the level_threshold setting.
	 *
	 * @return string
	 */
	public function get_level_threshold(): string {
		$key = self::PREFIX . 'level_threshold';

		$threshold = Constants::get_constant( 'WC_LOG_THRESHOLD' );

		if ( is_null( $threshold ) ) {
			$threshold = WC_Admin_Settings::get_option( $key );
		}

		if ( ! WC_Log_Levels::is_valid_level( $threshold ) ) {
			$threshold = self::DEFAULTS['level_threshold'];
		}

		return $threshold;
	}
}
