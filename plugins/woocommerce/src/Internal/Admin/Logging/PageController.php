<?php

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
	 * @var FileController
	 */
	private $file_controller;

	/**
	 * @var ListTable
	 */
	private $list_table;

	/**
	 * Initialize dependencies.
	 *
	 * @param FileController $file_controller
	 *
	 * @return void
	 */
	final public function init(
		FileController $file_controller
	) {
		$this->file_controller = $file_controller;

		$this->init_hooks();
	}

	/**
	 * Add callbacks to hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		self::add_action( 'load-woocommerce_page_wc-status', array( $this, 'setup_screen_options' ) );
	}

	/**
	 * Get the canonical URL for the Logs tab of the Status admin page.
	 *
	 * @return string
	 */
	public function get_logs_tab_url() {
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
	public function get_default_handler() {
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
	public function render() {
		$handler = $this->get_default_handler();

		switch ( $handler ) {
			case LogHandlerFileV2::class:
				$args = $this->get_query_params();
				$this->render_filev2( $args );
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
	 * @param array $args Args for rendering the views.
	 *
	 * @return void
	 */
	private function render_filev2( array $args = array() ) {
		$view = $args['view'] ?? '';

		switch ( $view ) {
			case 'list_files':
			default:
				$this->render_file_list_page();
				break;
			case 'single_file':
				WC_Admin_Status::status_logs_file();
				break;
		}
	}

	/**
	 * Render the file list view.
	 *
	 * @return void
	 */
	private function render_file_list_page() {
		$defaults = $this->get_query_param_defaults();
		$params   = $this->get_query_params();

		$this->get_list_table()->set_file_args( $params );
		$this->get_list_table()->prepare_items();
		?>
		<form id="logs-filter" method="get">
			<input type="hidden" name="page" value="wc-status" />
			<input type="hidden" name="tab" value="logs" />
			<?php foreach ( $params as $key => $value ) : ?>
				<?php if ( $value !== $defaults[ $key ] ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<?php endif; ?>
			<?php endforeach; ?>
			<?php $this->get_list_table()->display(); ?>
		</form>
		<?php
	}

	/**
	 * Get the default values for URL query params for FileV2 views.
	 *
	 * @return string[]
	 */
	private function get_query_param_defaults() {
		return array(
			'view'    => 'list_files',
			'orderby' => 'modified',
			'order'   => 'desc',
			'source'  => '',
		);
	}

	/**
	 * Get and validate URL query params for FileV2 views.
	 *
	 * @return array
	 */
	private function get_query_params() {
		$defaults = $this->get_query_param_defaults();
		$params   = filter_input_array(
			INPUT_GET,
			array(
				'view' => array(
					'filter'  => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(list_files|single_file)$/',
						'default' => $defaults['view'],
					),
				),
				'orderby' => array(
					'filter' => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(created|modified|source|size)$/',
						'default' => $defaults['orderby']
					),
				),
				'order' => array(
					'filter' => FILTER_VALIDATE_REGEXP,
					'options' => array(
						'regexp'  => '/^(asc|desc)$/i',
						'default' => $defaults['order']
					),
				),
				'source' => FILTER_SANITIZE_STRING,
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
	private function get_list_table() {
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
	private function setup_screen_options() {
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
}
