<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ FileController, ListTable };
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use WC_Admin_Status;

/**
 * PageController class.
 */
class PageController {

	use AccessiblePrivateMethods;

	/**
	 * Instance of FileController.
	 *
	 * @var FileController
	 */
	private $file_controller;

	/**
	 * Instance of ListTable.
	 *
	 * @var ListTable
	 */
	private $list_table;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FileController $file_controller Instance of FileController.
	 *
	 * @return void
	 */
	final public function init(
		FileController $file_controller
	): void {
		$this->file_controller = $file_controller;

		$this->init_hooks();
	}

	/**
	 * Add callbacks to hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		self::add_action( 'load-woocommerce_page_wc-status', array( $this, 'setup_screen_options' ) );
		self::add_action( 'load-woocommerce_page_wc-status', array( $this, 'handle_list_table_bulk_actions' ) );
	}

	/**
	 * Get the canonical URL for the Logs tab of the Status admin page.
	 *
	 * @return string
	 */
	public function get_logs_tab_url(): string {
		return add_query_arg(
			array(
				'page' => 'wc-status',
				'tab'  => 'logs',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Determine the default log handler.
	 *
	 * @return string
	 */
	public function get_default_handler(): string {
		$handler = Constants::get_constant( 'WC_LOG_HANDLER' );

		if ( is_null( $handler ) || ! class_exists( $handler ) ) {
			$handler = \WC_Log_Handler_File::class;
		}

		return $handler;
	}

	/**
	 * Render the "Logs" tab, depending on the current default log handler.
	 *
	 * @return void
	 */
	public function render(): void {
		$handler = $this->get_default_handler();

		switch ( $handler ) {
			case LogHandlerFileV2::class:
				$params = $this->get_query_params();
				$this->render_filev2( $params );
				break;
			case 'WC_Log_Handler_DB':
				WC_Admin_Status::status_logs_db();
				break;
			default:
				WC_Admin_Status::status_logs_file();
				break;
		}
	}

	/**
	 * Render the views for the FileV2 log handler.
	 *
	 * @param array $params Args for rendering the views.
	 *
	 * @return void
	 */
	private function render_filev2( array $params = array() ): void {
		$view = $params['view'] ?? '';

		switch ( $view ) {
			case 'list_files':
			default:
				$this->render_file_list_page( $params );
				break;
			case 'single_file':
				WC_Admin_Status::status_logs_file();
				break;
		}
	}

	/**
	 * Render the file list view.
	 *
	 * @param array $params Args for rendering the views.
	 *
	 * @return void
	 */
	private function render_file_list_page( array $params = array() ): void {
		$defaults = $this->get_query_param_defaults();

		?>
		<h2>
			<?php esc_html_e( 'Log files', 'woocommerce' ); ?>
		</h2>
		<form id="logs-list-table-form" method="get">
			<input type="hidden" name="page" value="wc-status" />
			<input type="hidden" name="tab" value="logs" />
			<?php foreach ( $params as $key => $value ) : ?>
				<?php if ( $value !== $defaults[ $key ] ) : ?>
					<input
						type="hidden"
						name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
					/>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php $this->get_list_table()->prepare_items(); ?>
			<?php $this->get_list_table()->display(); ?>
		</form>
		<?php
	}

	/**
	 * Get the default values for URL query params for FileV2 views.
	 *
	 * @return string[]
	 */
	public function get_query_param_defaults(): array {
		return array(
			'order'   => $this->file_controller::DEFAULTS_GET_FILES['order'],
			'orderby' => $this->file_controller::DEFAULTS_GET_FILES['orderby'],
			'source'  => $this->file_controller::DEFAULTS_GET_FILES['source'],
			'view'    => 'list_files',
		);
	}

	/**
	 * Get and validate URL query params for FileV2 views.
	 *
	 * @return array
	 */
	public function get_query_params(): array {
		$defaults = $this->get_query_param_defaults();
		$params   = filter_input_array(
			INPUT_GET,
			array(
				'view'    => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(list_files|single_file)$/',
						'default' => $defaults['view'],
					),
				),
				'orderby' => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(created|modified|source|size)$/',
						'default' => $defaults['orderby'],
					),
				),
				'order'   => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(asc|desc)$/i',
						'default' => $defaults['order'],
					),
				),
				'source'  => array(
					'filter'  => FILTER_CALLBACK,
					'options' => function( $source ) {
						return $this->file_controller->sanitize_source( wp_unslash( $source ) );
					},
				),
			),
			false
		);
		$params   = wp_parse_args( $params, $defaults );

		return $params;
	}

	/**
	 * Get and cache an instance of the list table.
	 *
	 * @return ListTable
	 */
	private function get_list_table(): ListTable {
		if ( $this->list_table instanceof ListTable ) {
			return $this->list_table;
		}

		$this->list_table = new ListTable( $this->file_controller, $this );

		return $this->list_table;
	}

	/**
	 * Register screen options for the logging views.
	 *
	 * @return void
	 */
	private function setup_screen_options(): void {
		$params = $this->get_query_params();

		if ( 'list_files' === $params['view'] ) {
			// Ensure list table columns are initialized early enough to enable column hiding.
			$this->get_list_table()->prepare_column_headers();

			add_screen_option(
				'per_page',
				array(
					'default' => 20,
					'option'  => ListTable::PER_PAGE_USER_OPTION_KEY,
				)
			);
		}
	}

	/**
	 * Process bulk actions initiated from the log file list table.
	 *
	 * @return void
	 */
	private function handle_list_table_bulk_actions(): void {
		$action = $this->get_list_table()->current_action();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : $this->get_logs_tab_url();

		if ( $action ) {
			check_admin_referer( 'bulk-log-files' );

			$sendback = remove_query_arg( array( 'deleted' ), wp_get_referer() );

			// Multiple file[] params will be filtered separately, but assigned to $files as an array.
			$files = filter_input(
				INPUT_GET,
				'file',
				FILTER_CALLBACK,
				array(
					'options' => function( $file ) {
						return sanitize_file_name( wp_unslash( $file ) );
					},
				)
			);

			if ( ! is_array( $files ) || count( $files ) < 1 ) {
				wp_safe_redirect( $sendback );
				exit;
			}

			switch ( $action ) {
				case 'delete':
					$deleted  = $this->file_controller->delete_files( $files );
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;
			}

			$sendback = remove_query_arg( array( 'action', 'action2' ), $sendback );

			wp_safe_redirect( $sendback );
			exit;
		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			$removable_args = array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'filter_action' );
			wp_safe_redirect( remove_query_arg( $removable_args, $request_uri ) );
			exit;
		}

		$deleted = filter_input( INPUT_GET, 'deleted', FILTER_VALIDATE_INT );

		if ( is_numeric( $deleted ) ) {
			add_action(
				'admin_notices',
				function() use ( $deleted ) {
					?>
					<div class="notice notice-info is-dismissible">
						<p>
							<?php
							printf(
								// translators: %s is a number of files.
								esc_html( _n( '%s log file deleted.', '%s log files deleted.', $deleted, 'woocommerce' ) ),
								esc_html( number_format_i18n( $deleted ) )
							);
							?>
						</p>
					</div>
					<?php
				}
			);
		}
	}
}
