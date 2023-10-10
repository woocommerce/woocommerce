<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

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
	 * ListTable class.
	 */
	public function __construct( FileController $file_controller ) {
		$this->file_controller = $file_controller;

		parent::__construct(
			array(
				'singular' => 'log-file',
				'plural'   => 'log-files',
				'ajax'     => false,
			)
		);
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
		$offset   = ( $this->get_pagenum() - 1 ) * $per_page;
		$orderby  = filter_input(
			INPUT_GET,
			'orderby',
			FILTER_VALIDATE_REGEXP,
			array(
				'options' => array(
					'regexp'  => '/^(created|modified|source|size)$/',
					'default' => 'modified'
				),
			)
		);
		$order    = filter_input(
			INPUT_GET,
			'order',
			FILTER_VALIDATE_REGEXP,
			array(
				'options' => array(
					'regexp'  => '/^(asc|desc)$/i',
					'default' => 'desc'
				),
			)
		);

		$file_args = array(
			'per_page' => $per_page,
			'offset'   => $offset,
			'orderby'  => $orderby,
			'order'    => $order,
		);

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
		return $item->get_source();
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

		return date( 'Y-m-d H:i:s', $timestamp );
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
