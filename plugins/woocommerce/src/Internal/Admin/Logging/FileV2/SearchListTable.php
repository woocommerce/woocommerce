<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\WooCommerce\Internal\Admin\Logging\PageController;

use WP_List_Table;

/**
 * SearchListTable class.
 */
class SearchListTable extends WP_List_Table {
	/**
	 * The user option key for saving the preferred number of search results displayed per page.
	 *
	 * @const string
	 */
	public const PER_PAGE_USER_OPTION_KEY = 'woocommerce_logging_search_results_per_page';

	/**
	 * Instance of FileController.
	 *
	 * @var FileController
	 */
	private $file_controller;

	/**
	 * Instance of PageController.
	 *
	 * @var PageController
	 */
	private $page_controller;

	/**
	 * SearchListTable class.
	 *
	 * @param FileController $file_controller Instance of FileController.
	 * @param PageController $page_controller Instance of PageController.
	 */
	public function __construct( FileController $file_controller, PageController $page_controller ) {
		$this->file_controller = $file_controller;
		$this->page_controller = $page_controller;

		parent::__construct(
			array(
				'singular' => 'wc-logs-search-result',
				'plural'   => 'wc-logs-search-results',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Render message when there are no items.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No search results.', 'woocommerce' );
	}

	/**
	 * Set up the column header info.
	 *
	 * @return void
	 */
	public function prepare_column_headers(): void {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array(),
			$this->get_primary_column(),
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page = $this->get_items_per_page(
			self::PER_PAGE_USER_OPTION_KEY,
			$this->get_per_page_default()
		);

		$args = array(
			'per_page' => $per_page,
			'offset'   => ( $this->get_pagenum() - 1 ) * $per_page,
		);

		$file_args = $this->page_controller->get_query_params(
			array( 'date_end', 'date_filter', 'date_start', 'order', 'orderby', 'search', 'source' )
		);
		$search    = $file_args['search'];
		unset( $file_args['search'] );

		$total_items = $this->file_controller->search_within_files( $search, $args, $file_args, true );
		if ( is_wp_error( $total_items ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html( $total_items->get_error_message() )
			);

			return;
		}

		if ( $total_items >= $this->file_controller::SEARCH_MAX_RESULTS ) {
			printf(
				'<div class="notice notice-info"><p>%s</p></div>',
				sprintf(
					// translators: %s is a number.
					esc_html__( 'The number of search results has reached the limit of %s. Try refining your search.', 'woocommerce' ),
					esc_html( number_format_i18n( $this->file_controller::SEARCH_MAX_RESULTS ) )
				)
			);
		}

		$total_pages = ceil( $total_items / $per_page );
		$results     = $this->file_controller->search_within_files( $search, $args, $file_args );
		$this->items = $results;

		$this->set_pagination_args(
			array(
				'per_page'    => $per_page,
				'total_items' => $total_items,
				'total_pages' => $total_pages,
			)
		);
	}

	/**
	 * Gets a list of columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		$columns = array(
			'file_id'     => esc_html__( 'File', 'woocommerce' ),
			'line_number' => esc_html__( 'Line #', 'woocommerce' ),
			'line'        => esc_html__( 'Matched Line', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Render the file_id column.
	 *
	 * @param array $item The current search result being rendered.
	 *
	 * @return string
	 */
	public function column_file_id( array $item ): string {
		// Add a word break after the rotation number, if it exists.
		$file_id = preg_replace( '/\.([0-9])+\-/', '.\1<wbr>-', $item['file_id'] );

		return wp_kses( $file_id, array( 'wbr' => array() ) );
	}

	/**
	 * Render the line_number column.
	 *
	 * @param array $item The current search result being rendered.
	 *
	 * @return string
	 */
	public function column_line_number( array $item ): string {
		$match_url = add_query_arg(
			array(
				'view'    => 'single_file',
				'file_id' => $item['file_id'],
			),
			$this->page_controller->get_logs_tab_url() . '#L' . absint( $item['line_number'] )
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $match_url ),
			sprintf(
				// translators: %s is a line number in a file.
				esc_html__( 'Line %s', 'woocommerce' ),
				number_format_i18n( absint( $item['line_number'] ) )
			)
		);
	}

	/**
	 * Render the line column.
	 *
	 * @param array $item The current search result being rendered.
	 *
	 * @return string
	 */
	public function column_line( array $item ): string {
		$params = $this->page_controller->get_query_params( array( 'search' ) );
		$line   = $item['line'];

		// Highlight matches within the line.
		$pattern = preg_quote( $params['search'], '/' );
		preg_match_all( "/$pattern/i", $line, $matches, PREG_OFFSET_CAPTURE );
		if ( is_array( $matches[0] ) && count( $matches[0] ) >= 1 ) {
			$length_change = 0;

			foreach ( $matches[0] as $match ) {
				$replace        = '<span class="search-match">' . $match[0] . '</span>';
				$offset         = $match[1] + $length_change;
				$orig_length    = strlen( $match[0] );
				$replace_length = strlen( $replace );

				$line = substr_replace( $line, $replace, $offset, $orig_length );

				$length_change += $replace_length - $orig_length;
			}
		}

		return wp_kses_post( $line );
	}

	/**
	 * Helper to get the default value for the per_page arg.
	 *
	 * @return int
	 */
	public function get_per_page_default(): int {
		return $this->file_controller::DEFAULTS_SEARCH_WITHIN_FILES['per_page'];
	}
}
