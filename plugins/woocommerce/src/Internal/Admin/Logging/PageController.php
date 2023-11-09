<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ FileController, ListTable };
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use WC_Admin_Status;
use WC_Log_Levels;

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
				$this->render_single_file_page( $params );
				break;
		}
	}

	/**
	 * Render the file list view.
	 *
	 * @param array $params Args for rendering the view.
	 *
	 * @return void
	 */
	private function render_file_list_page( array $params = array() ): void {
		$defaults = $this->get_query_param_defaults();

		?>
		<header id="logs-header" class="wc-logs-header">
			<h2>
				<?php esc_html_e( 'Browse log files', 'woocommerce' ); ?>
			</h2>
		</header>
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
	 * Render the single file view.
	 *
	 * @param array $params Args for rendering the view.
	 *
	 * @return void
	 */
	private function render_single_file_page( array $params ): void {
		$file = $this->file_controller->get_file_by_id( $params['file_id'] );

		if ( is_wp_error( $file ) ) {
			?>
			<div class="notice notice-error notice-inline">
				<?php echo wp_kses_post( wpautop( $file->get_error_message() ) ); ?>
				<?php
				printf(
					'<p><a href="%1$s">%2$s</a></p>',
					esc_url( $this->get_logs_tab_url() ),
					esc_html__( 'Return to the file list.', 'woocommerce' )
				);
				?>
			</div>
			<?php

			return;
		}

		$rotations         = $this->file_controller->get_file_rotations( $file->get_file_id() );
		$rotation_url_base = add_query_arg( 'view', 'single_file', $this->get_logs_tab_url() );

		$delete_url = add_query_arg(
			array(
				'action'  => 'delete',
				'file_id' => array( $file->get_file_id() ),
			),
			wp_nonce_url( $this->get_logs_tab_url(), 'bulk-log-files' )
		);

		$stream      = $file->get_stream();
		$line_number = 1;

		$delete_confirmation_js = sprintf(
			"return window.confirm( '%s' )",
			esc_js( __( 'Delete this log file permanently?', 'woocommerce' ) )
		);

		?>
		<header id="logs-header" class="wc-logs-header">
			<h2>
				<?php
				printf(
					// translators: %s is the name of a log file.
					esc_html__( 'Viewing log file %s', 'woocommerce' ),
					sprintf(
						'<span class="file-id">%s</span>',
						esc_html( $file->get_file_id() )
					)
				);
				?>
			</h2>
			<?php if ( count( $rotations ) > 1 ) : ?>
				<nav class="wc-logs-single-file-rotations">
					<h3><?php esc_html_e( 'File rotations:', 'woocommerce' ); ?></h3>
					<ul class="wc-logs-rotation-links">
						<?php if ( isset( $rotations['current'] ) ) : ?>
							<?php
							printf(
								'<li><a href="%1$s" class="button button-small button-%2$s">%3$s</a></li>',
								esc_url( add_query_arg( 'file_id', $rotations['current']->get_file_id(), $rotation_url_base ) ),
								$file->get_file_id() === $rotations['current']->get_file_id() ? 'primary' : 'secondary',
								esc_html__( 'Current', 'woocommerce' )
							);
							unset( $rotations['current'] );
							?>
						<?php endif; ?>
						<?php foreach ( $rotations as $rotation ) : ?>
							<?php
							printf(
								'<li><a href="%1$s" class="button button-small button-%2$s">%3$s</a></li>',
								esc_url( add_query_arg( 'file_id', $rotation->get_file_id(), $rotation_url_base ) ),
								$file->get_file_id() === $rotation->get_file_id() ? 'primary' : 'secondary',
								absint( $rotation->get_rotation() )
							);
							?>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
			<div class="wc-logs-single-file-actions">
				<?php
				// Delete button.
				printf(
					'<a href="%1$s" class="button button-secondary" onclick="%2$s">%3$s</a>',
					esc_url( $delete_url ),
					esc_attr( $delete_confirmation_js ),
					esc_html__( 'Delete permanently', 'woocommerce' )
				);
				?>
			</div>
		</header>
		<section id="logs-entries" class="wc-logs-entries">
			<?php while ( ! feof( $stream ) ) : ?>
				<?php
				$line = fgets( $stream );
				if ( is_string( $line ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_line does the escaping.
					echo $this->format_line( $line, $line_number );
					$line_number ++;
				}
				?>
			<?php endwhile; ?>
		</section>
		<?php
	}

	/**
	 * Get the default values for URL query params for FileV2 views.
	 *
	 * @return string[]
	 */
	public function get_query_param_defaults(): array {
		return array(
			'file_id' => '',
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
				'file_id' => array(
					'filter'  => FILTER_CALLBACK,
					'options' => function( $file_id ) {
						return sanitize_file_name( $file_id );
					},
				),
				'order'   => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(asc|desc)$/i',
						'default' => $defaults['order'],
					),
				),
				'orderby' => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(created|modified|source|size)$/',
						'default' => $defaults['orderby'],
					),
				),
				'source'  => array(
					'filter'  => FILTER_CALLBACK,
					'options' => function( $source ) {
						return $this->file_controller->sanitize_source( wp_unslash( $source ) );
					},
				),
				'view'    => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(list_files|single_file)$/',
						'default' => $defaults['view'],
					),
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
		// Bail if this is not the list table view.
		$params = $this->get_query_params();
		if ( 'list_files' !== $params['view'] ) {
			return;
		}

		$action = $this->get_list_table()->current_action();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : $this->get_logs_tab_url();

		if ( $action ) {
			check_admin_referer( 'bulk-log-files' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage log files.', 'woocommerce' ) );
			}

			$sendback = remove_query_arg( array( 'deleted' ), wp_get_referer() );

			// Multiple file_id[] params will be filtered separately, but assigned to $files as an array.
			$file_ids = filter_input(
				INPUT_GET,
				'file_id',
				FILTER_CALLBACK,
				array(
					'options' => function( $file ) {
						return sanitize_file_name( wp_unslash( $file ) );
					},
				)
			);

			if ( ! is_array( $file_ids ) || count( $file_ids ) < 1 ) {
				wp_safe_redirect( $sendback );
				exit;
			}

			switch ( $action ) {
				case 'delete':
					$deleted  = $this->file_controller->delete_files( $file_ids );
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );

					/**
					 * If the delete action was triggered on the single file view, don't redirect back there
					 * since the file doesn't exist anymore.
					 */
					$sendback = remove_query_arg( array( 'view', 'file_id' ), $sendback );
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

	/**
	 * Format a log file line.
	 *
	 * @param string $text        The unformatted log file line.
	 * @param int    $line_number The line number.
	 *
	 * @return string
	 */
	private function format_line( string $text, int $line_number ): string {
		$severity_levels = WC_Log_Levels::get_all_severity_levels();
		$classes         = array( 'line' );

		$text = esc_html( trim( $text ) );
		if ( empty( $text ) ) {
			$text = '&nbsp;';
		}

		$segments = explode( ' ', $text, 3 );

		if ( isset( $segments[0] ) && false !== strtotime( $segments[0] ) ) {
			$classes[]   = 'log-entry';
			$segments[0] = sprintf(
				'<span class="log-timestamp">%s</span>',
				$segments[0]
			);
		}

		if ( isset( $segments[1] ) && in_array( strtolower( $segments[1] ), $severity_levels, true ) ) {
			$segments[1] = sprintf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( 'log-level log-level--' . strtolower( $segments[1] ) ),
				esc_html( $segments[1] )
			);
		}

		if ( count( $segments ) > 1 ) {
			$text = implode( ' ', $segments );
		}

		$classes = implode( ' ', $classes );

		return sprintf(
			'<span id="L%1$d" class="%2$s">%3$s%4$s</span>',
			absint( $line_number ),
			esc_attr( $classes ),
			sprintf(
				'<a href="#L%1$d" class="line-anchor"></a>',
				absint( $line_number )
			),
			sprintf(
				'<span class="line-content">%s</span>',
				wp_kses_post( $text )
			)
		);
	}
}
