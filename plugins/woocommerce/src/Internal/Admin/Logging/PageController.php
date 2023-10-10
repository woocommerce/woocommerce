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
				$args = $this->get_filev2_query_params();
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
				$this->get_list_table()->prepare_items();
				$this->get_list_table()->display();
				break;
			case 'single_file':
				WC_Admin_Status::status_logs_file();
				break;
		}
	}

	/**
	 * Get and validate URL query params for FileV2 views.
	 *
	 * @return array
	 */
	private function get_filev2_query_params() {
		$defaults = array(
			'view' => 'list_files',
		);
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

		$this->list_table = new ListTable( $this->file_controller );

		return $this->list_table;
	}

	/**
	 * Register screen options for the logging views.
	 *
	 * @return void
	 */
	private function setup_screen_options() {
		$params = $this->get_filev2_query_params();

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
