<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\WooCommerce\Internal\Admin\Logging\PageController;

use WP_List_Table;

/**
 * FileListTable class.
 */
class FileListTable extends WP_List_Table {
	/**
	 * The user option key for saving the preferred number of files displayed per page.
	 *
	 * @const string
	 */
	public const PER_PAGE_USER_OPTION_KEY = 'woocommerce_logging_file_list_per_page';

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
	 * FileListTable class.
	 *
	 * @param FileController $file_controller Instance of FileController.
	 * @param PageController $page_controller Instance of PageController.
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
	 * Render message when there are no items.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No log files found.', 'woocommerce' );
	}

	/**
	 * Retrieves the list of bulk actions available for this table.
	 *
	 * @return array
	 */
	protected function get_bulk_actions(): array {
		return array(
			'export' => esc_html__( 'Download', 'woocommerce' ),
			'delete' => esc_html__( 'Delete permanently', 'woocommerce' ),
		);
	}

	/**
	 * Get the existing log sources for the filter dropdown.
	 *
	 * @return array
	 */
	protected function get_sources_list(): array {
		$sources = $this->file_controller->get_file_sources();
		if ( is_wp_error( $sources ) ) {
			return array();
		}

		sort( $sources );

		return $sources;
	}

	/**
	 * Displays extra controls between bulk actions and pagination.
	 *
	 * @param string $which The location of the tablenav being rendered. 'top' or 'bottom'.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		$all_sources = $this->get_sources_list();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$current_source = File::sanitize_source( wp_unslash( $_GET['source'] ?? '' ) );

		?>
		<div class="alignleft actions">
			<?php if ( 'top' === $which ) : ?>
				<label for="filter-by-source" class="screen-reader-text"><?php esc_html_e( 'Filter by log source', 'woocommerce' ); ?></label>
				<select name="source" id="filter-by-source">
					<option<?php selected( $current_source, '' ); ?> value=""><?php esc_html_e( 'All sources', 'woocommerce' ); ?></option>
					<?php foreach ( $all_sources as $source ) : ?>
						<option<?php selected( $current_source, $source ); ?> value="<?php echo esc_attr( $source ); ?>">
							<?php echo esc_html( $source ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
				submit_button(
					__( 'Filter', 'woocommerce' ),
					'',
					'filter_action',
					false,
					array(
						'id' => 'logs-filter-submit',
					)
				);
				?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Set up the column header info.
	 *
	 * @return void
	 */
	public function prepare_column_headers(): void {
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
	public function prepare_items(): void {
		$per_page = $this->get_items_per_page(
			self::PER_PAGE_USER_OPTION_KEY,
			$this->get_per_page_default()
		);

		$defaults  = array(
			'per_page' => $per_page,
			'offset'   => ( $this->get_pagenum() - 1 ) * $per_page,
		);
		$file_args = wp_parse_args(
			$this->page_controller->get_query_params( array( 'order', 'orderby', 'source' ) ),
			$defaults
		);

		$total_items = $this->file_controller->get_files( $file_args, true );
		if ( is_wp_error( $total_items ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html( $total_items->get_error_message() )
			);

			return;
		}

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
	public function get_columns(): array {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'source'   => esc_html__( 'Source', 'woocommerce' ),
			'created'  => esc_html__( 'Date created', 'woocommerce' ),
			'modified' => esc_html__( 'Date modified', 'woocommerce' ),
			'size'     => esc_html__( 'File size', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Gets a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns(): array {
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
	 * @param File $item The current log file being rendered.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		ob_start();
		?>
		<input
			id="cb-select-<?php echo esc_attr( $item->get_file_id() ); ?>"
			type="checkbox"
			name="file_id[]"
			value="<?php echo esc_attr( $item->get_file_id() ); ?>"
		/>
		<label for="cb-select-<?php echo esc_attr( $item->get_file_id() ); ?>">
			<span class="screen-reader-text">
				<?php
				printf(
					// translators: 1. a date, 2. a slug-style name for a file.
					esc_html__( 'Select the %1$s log file for %2$s', 'woocommerce' ),
					esc_html( gmdate( get_option( 'date_format' ), $item->get_created_timestamp() ) ),
					esc_html( $item->get_source() )
				);
				?>
			</span>
		</label>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the source column.
	 *
	 * @param File $item The current log file being rendered.
	 *
	 * @return string
	 */
	public function column_source( $item ): string {
		$log_file        = $item->get_file_id();
		$single_file_url = add_query_arg(
			array(
				'view'    => 'single_file',
				'file_id' => $log_file,
			),
			$this->page_controller->get_logs_tab_url()
		);
		$rotation        = '';
		if ( ! is_null( $item->get_rotation() ) ) {
			$rotation = sprintf(
				' &ndash; <span class="post-state">%d</span>',
				$item->get_rotation()
			);
		}

		return sprintf(
			'<a class="row-title" href="%1$s">%2$s</a>%3$s',
			esc_url( $single_file_url ),
			esc_html( $item->get_source() ),
			$rotation
		);
	}

	/**
	 * Render the created column.
	 *
	 * @param File $item The current log file being rendered.
	 *
	 * @return string
	 */
	public function column_created( $item ): string {
		$timestamp = $item->get_created_timestamp();

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Render the modified column.
	 *
	 * @param File $item The current log file being rendered.
	 *
	 * @return string
	 */
	public function column_modified( $item ): string {
		$timestamp = $item->get_modified_timestamp();

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Render the size column.
	 *
	 * @param File $item The current log file being rendered.
	 *
	 * @return string
	 */
	public function column_size( $item ): string {
		$size = $item->get_file_size();

		return size_format( $size );
	}

	/**
	 * Helper to get the default value for the per_page arg.
	 *
	 * @return int
	 */
	public function get_per_page_default(): int {
		return $this->file_controller::DEFAULTS_GET_FILES['per_page'];
	}
}
