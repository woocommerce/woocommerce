<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\WooCommerce\Internal\Admin\Logging\PageController;

use WP_List_Table;

/**
 * ListTable class.
 */
class ListTable extends WP_List_Table {
	/**
	 * @const string
	 */
	public const PER_PAGE_USER_OPTION_KEY = 'woocommerce_logging_file_list_per_page';

	/**
	 * @var FileController
	 */
	private $file_controller;

	/**
	 * @var PageController
	 */
	private $page_controller;

	/**
	 * @var array
	 */
	private $file_args = array();

	/**
	 * ListTable class.
	 */
	public function __construct( FileController $file_controller, PageController $page_controller ) {
		$this->file_controller = $file_controller;
		$this->page_controller = $page_controller;

		parent::__construct(
			array(
				'singular' => 'log-file',
				'plural'   => 'log-files',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Set file args for later use, since `prepare_items` can't take any parameters.
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function set_file_args( $args ) {
		if ( is_array( $args ) ) {
			$this->file_args = $args;
		}
	}

	/**
	 * Render message when there are no items.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No log files found.', 'woocommerce' );
	}

	/**
	 * Get the existing log sources for the filter dropdown.
	 *
	 * @return array
	 */
	protected function get_sources_list() {
		$sources = $this->file_controller->get_file_sources();
		sort( $sources );

		return $sources;
	}

	/**
	 * Displays extra controls between bulk actions and pagination.
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		$all_sources    = $this->get_sources_list();
		$current_source = filter_input( INPUT_GET, 'source', FILTER_SANITIZE_STRING ) ?? '';

		?>
		<div class="alignleft actions">
			<?php if ( 'top' === $which ) : ?>
				<label for="filter-by-source" class="screen-reader-text"><?php esc_html_e( 'Filter by log source', 'woocommerce' ); ?></label>
				<select name="source" id="filter-by-source">
					<option<?php selected( $current_source, '' ); ?> value=""><?php esc_html_e( 'All sources', 'woocommerce' ); ?></option>
					<?php foreach ( $all_sources as $source ) : ?>
						<option<?php selected( $current_source, $source ); ?> value="<?php echo esc_attr( $source ) ?>">
							<?php echo esc_html( $source ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Filter', 'woocommerce' ), '', 'filter_action', false, array( 'id' => 'logs-filter-submit' ) ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Set up the column header info.
	 *
	 * @return void
	 */
	public function prepare_column_headers() {
		$this->_column_headers = array(
			$this->get_columns(),
			get_hidden_columns( $this->screen ),
			$this->get_sortable_columns(),
			$this->get_primary_column(),
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( self::PER_PAGE_USER_OPTION_KEY );

		$defaults = array(
			'per_page' => $per_page,
			'offset'   => ( $this->get_pagenum() - 1 ) * $per_page,
		);
		$file_args = wp_parse_args( $this->file_args, $defaults );

		$total_items = $this->file_controller->get_files( $file_args, true );
		$total_pages = ceil( $total_items / $per_page );
		$items       = $this->file_controller->get_files( $file_args );

		$this->items = $items;

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
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'source'   => __( 'Source', 'woocommerce' ),
			'created'  => __( 'Date created', 'woocommerce' ),
			'modified' => __( 'Date modified', 'woocommerce' ),
			'size'     => __( 'File size', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Gets a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable = array(
			'source'   => array( 'source' ),
			'created'  => array( 'created' ),
			'modified' => array( 'modified', true ),
			'size'     => array( 'size' ),
		);

		return $sortable;
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param File $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		ob_start();
		?>
		<input
			id="cb-select-<?php echo esc_attr( $item->get_key() ); ?>"
			type="checkbox"
			name="id[]"
			value="<?php echo esc_attr( $item->get_key() ); ?>"
		/>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the source column.
	 *
	 * @param File $item
	 *
	 * @return string
	 */
	public function column_source( $item ) {
		$log_file        = sanitize_title( $item->get_basename() );
		$single_file_url = add_query_arg(
			array(
				'view'     => 'single_file',
				'log_file' => $log_file,
			),
			$this->page_controller->get_logs_tab_url()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			$single_file_url,
			$item->get_source()
		);
	}

	/**
	 * Render the created column.
	 *
	 * @param File $item
	 *
	 * @return string
	 */
	public function column_created( $item ) {
		$timestamp = $item->get_created_timestamp();

		return date( 'Y-m-d', $timestamp );
	}

	/**
	 * Render the modified column.
	 *
	 * @param File $item
	 *
	 * @return string
	 */
	public function column_modified( $item ) {
		$timestamp = $item->get_modified_timestamp();

		return date( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Render the size column.
	 *
	 * @param File $item
	 *
	 * @return string
	 */
	public function column_size( $item ) {
		$size = $item->get_file_size();

		return size_format( $size );
	}
}
