<?php
/**
 * API\Reports\Taxes\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Enums\OrderItemType;

/**
 * API\Reports\Taxes\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @override ReportsDataStore::$table_name
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_order_tax_lookup';

	/**
	 * Cache identifier.
	 *
	 * @override ReportsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'taxes';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override ReportsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'tax_rate_id'    => 'intval',
		'name'           => 'strval',
		'tax_rate'       => 'floatval',
		'country'        => 'strval',
		'state'          => 'strval',
		'priority'       => 'intval',
		'total_tax'      => 'floatval',
		'order_tax'      => 'floatval',
		'shipping_tax'   => 'floatval',
		'taxable_amount' => 'floatval',
		'orders_count'   => 'intval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override ReportsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'taxes';

	/**
	 * Option caching a positive check for the taxable_amount column on the lookup table.
	 *
	 * @since 11.2.0
	 *
	 * @var string
	 */
	const OPTION_TAX_LOOKUP_TABLE_HAS_TAXABLE_AMOUNT_COLUMN = 'woocommerce_tax_lookup_has_taxable_amount_column';

	/**
	 * Constructor.
	 *
	 * Report on the date type configured in Analytics settings (default `date_paid`),
	 * matching the Orders and Revenue reports. The reporting period is filtered against
	 * the chosen column on `wc_order_stats` so the Taxes report reconciles with them.
	 *
	 * @override ReportsDataStore::__construct()
	 */
	public function __construct() {
		$this->date_column_name = $this->sanitize_date_column_name( get_option( 'woocommerce_date_type' ), 'date_paid' );
		parent::__construct();
	}

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override ReportsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		global $wpdb;
		$table_name = self::get_db_table_name();

		// Using wp_woocommerce_tax_rates table limits the result to only the existing tax rates and
		// omits the historical records which differs from the purpose of wp_wc_order_tax_lookup table.
		// So in order to get the same data present in wp_woocommerce_tax_rates without breaking the
		// API contract the values are now retrieved from wp_woocommerce_order_items and wp_woocommerce_order_itemmeta.
		// And given that country, state and priority are not separate columns within the woocommerce_order_items,
		// a split to order_item_name column value is required to separate those values. This is not ideal,
		// but given this query is paginated and cached, then it is not a big deal. There is always room for
		// improvements here.
		$this->report_columns = array(
			'tax_rate_id'    => "{$table_name}.tax_rate_id",
			'name'           => "SUBSTRING_INDEX(SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',-2), '-', 1) as name",
			'tax_rate'       => 'CAST(itemmeta_rate_percent.meta_value AS DECIMAL(7,4)) as tax_rate',
			'country'        => "SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',1) as country",
			'state'          => "SUBSTRING_INDEX(SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',-3), '-', 1) as state",
			'priority'       => "SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',-1) as priority",
			'total_tax'      => 'SUM(total_tax) as total_tax',
			'order_tax'      => 'SUM(order_tax) as order_tax',
			'shipping_tax'   => 'SUM(shipping_tax) as shipping_tax',
			'taxable_amount' => 'SUM(taxable_amount) as taxable_amount',
			// parent_id stays unqualified: wc_order_stats is the only joined table carrying it, and
			// this string is carried by the public woocommerce_admin_report_columns filter, so it must
			// match the released form for extension callbacks that inspect or rewrite it.
			'orders_count'   => "COUNT( DISTINCT ( CASE WHEN parent_id = 0 THEN {$table_name}.order_id END ) ) as orders_count",
		);

		// Guard against the column not existing yet: the report otherwise breaks entirely on a
		// site where the upgrade routine has not run (or could not run) the schema update.
		if ( ! static::has_taxable_amount_column() ) {
			unset( $this->report_columns['taxable_amount'] );
		}
	}

	/**
	 * Check if the wc_order_tax_lookup table has the taxable_amount column.
	 *
	 * Only a positive result is cached: while the column is missing the check re-runs,
	 * so the feature turns on by itself once the schema update lands.
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	public static function has_taxable_amount_column() {
		// Memoize the negative result per request only: a site whose schema update never
		// lands would otherwise pay two SHOW queries per call, once per order on imports.
		// Keyed by blog id since the schema is per site, though get_db_table_name() itself
		// memoizes the first blog's table name across switch_to_blog() (pre-existing
		// limitation shared by the whole Taxes report).
		static $missing_this_request = array();

		if ( ! empty( $missing_this_request[ get_current_blog_id() ] ) ) {
			return false;
		}

		if ( 'yes' === get_option( self::OPTION_TAX_LOOKUP_TABLE_HAS_TAXABLE_AMOUNT_COLUMN ) ) {
			return true;
		}

		global $wpdb;
		$table_name = self::get_db_table_name();

		// If the table itself does not exist yet, checking its columns would be a DB error.
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		if ( ! $table_exists ) {
			$missing_this_request[ get_current_blog_id() ] = true;
			return false;
		}

		$column_exists = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be prepared.
				"SHOW COLUMNS FROM `{$table_name}` LIKE %s",
				'taxable_amount'
			)
		);

		if ( empty( $column_exists ) ) {
			$missing_this_request[ get_current_blog_id() ] = true;
			return false;
		}

		// The column changes the report's SELECT, so reports cached before it existed would
		// keep serving rows without it for up to a week. Invalidate only when the option
		// write persisted, or a blocked write would re-invalidate on every request.
		if ( update_option( self::OPTION_TAX_LOOKUP_TABLE_HAS_TAXABLE_AMOUNT_COLUMN, 'yes', false ) ) {
			ReportsCache::invalidate();
		}

		return true;
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_analytics_delete_order_stats', array( __CLASS__, 'sync_on_order_delete' ), 15 );
	}

	/**
	 * Fills FROM clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args          Query arguments supplied by the user.
	 * @param string $order_status_filter Order status subquery. Retained for signature compatibility; the wc_order_stats join is now always added.
	 */
	protected function add_from_sql_params( $query_args, $order_status_filter ) {
		global $wpdb;
		$table_name = self::get_db_table_name();

		// Always join wc_order_stats: the reporting period is filtered against its
		// configured date column (date_paid by default) and the status subquery relies on it.
		$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id" );

		$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_order_items ON {$table_name}.order_id = {$wpdb->prefix}woocommerce_order_items.order_id AND {$wpdb->prefix}woocommerce_order_items.order_item_type = 'tax'" );
		$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_order_itemmeta itemmeta_rate_id ON itemmeta_rate_id.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id AND itemmeta_rate_id.meta_key = 'rate_id'" );
		$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_order_itemmeta itemmeta_rate_percent ON itemmeta_rate_percent.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id AND itemmeta_rate_percent.meta_key = 'rate_percent'" );
	}

	/**
	 * Updates the database query with parameters used for Taxes report: categories and order status.
	 *
	 * @see Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore::update_sql_query_params()
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function add_sql_query_params( $query_args ) {
		global $wpdb;

		$order_tax_lookup_table = self::get_db_table_name();
		$order_stats_table      = $wpdb->prefix . 'wc_order_stats';

		// Filter the reporting period against the configured date type on wc_order_stats
		// (date_paid by default) rather than the lookup's date_created, so the Taxes report
		// reconciles with the Orders and Revenue reports.
		$this->add_time_period_sql_params( $query_args, $order_stats_table );
		$this->get_limit_sql_params( $query_args );
		$this->add_order_by_sql_params( $query_args );
		$order_status_filter = $this->get_status_subquery( $query_args );
		$this->add_from_sql_params( $query_args, $order_status_filter );

		$this->subquery->add_sql_clause( 'where', "AND itemmeta_rate_id.meta_value = {$order_tax_lookup_table}.tax_rate_id" );

		/*
		 * Narrow the rate match to the single tax line the row was written for. The rate id on its
		 * own fans one lookup row out across every line of the order that shares it, which is how
		 * an order carrying several lines on one rate id came to report the wrong tax.
		 *
		 * Rows recorded before the lookup held one row per tax order item sit at the column's zero
		 * default and have no line to narrow to, so they go on matching the rate id alone, which is
		 * how the report read them all along. OrderTaxLookupMigrator rebuilds them in the
		 * background.
		 */
		$this->subquery->add_sql_clause( 'where', "AND ( {$order_tax_lookup_table}.order_item_id = 0 OR {$order_tax_lookup_table}.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id )" );

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes = self::get_filtered_ids( $query_args, 'taxes' );
			$this->subquery->add_sql_clause( 'where', "AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})" );
		}

		if ( $order_status_filter ) {
			$this->subquery->add_sql_clause( 'where', "AND ( {$order_status_filter} )" );
		}
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override ReportsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults            = parent::get_default_query_vars();
		$defaults['orderby'] = 'tax_rate_id';
		$defaults['taxes']   = array();

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override ReportsDataStore::get_noncached_data()
	 *
	 * @see get_data
	 * @param array $query_args Query parameters.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_data( $query_args ) {
		global $wpdb;

		$this->initialize_queries();

		$data = (object) array(
			'data'    => array(),
			'total'   => 0,
			'pages'   => 0,
			'page_no' => 0,
		);

		// While the taxable_amount column is missing its report column is unset, so ordering
		// by it would be a SQL error. Fall back to the default order.
		if ( 'taxable_amount' === ( $query_args['orderby'] ?? '' ) && ! isset( $this->report_columns['taxable_amount'] ) ) {
			$query_args['orderby'] = 'tax_rate_id';
		}

		$this->add_sql_query_params( $query_args );
		$params = $this->get_limit_params( $query_args );

		if ( isset( $query_args['taxes'] ) && is_array( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$total_results = count( $query_args['taxes'] );
			$total_pages   = (int) ceil( $total_results / $params['per_page'] );
		} else {
			$db_records_count = (int) $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- cache ok, DB call ok, unprepared SQL ok.
				"SELECT COUNT(*) FROM ( {$this->subquery->get_query_statement()} ) AS tt"
			);

			$total_results = $db_records_count;
			$total_pages   = (int) ceil( $db_records_count / $params['per_page'] );

			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}
		}

		$this->subquery->clear_sql_clause( 'select' );
		$this->subquery->add_sql_clause( 'select', $this->selected_columns( $query_args ) );
		if ( in_array( $query_args['orderby'], array( 'total_tax', 'order_tax', 'shipping_tax', 'taxable_amount', 'orders_count' ), true ) ) {
			$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) . ', tax_rate_id' );
		} else {
			$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		}
		$this->subquery->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );

		$taxes_query = $this->subquery->get_query_statement();

		$tax_data = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$taxes_query,
			ARRAY_A
		);

		if ( null === $tax_data ) {
			return $data;
		}

		$tax_data = array_map( array( $this, 'cast_numbers' ), $tax_data );
		$data     = (object) array(
			'data'    => $tax_data,
			'total'   => $total_results,
			'pages'   => $total_pages,
			'page_no' => (int) $query_args['page'],
		);

		return $data;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @override ReportsDataStore::normalize_order_by()
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		global $wpdb;

		if ( 'tax_code' === $order_by ) {
			return "{$wpdb->prefix}woocommerce_order_items.order_item_name";
		} elseif ( 'rate' === $order_by ) {
			return 'tax_rate';
		}

		return $order_by;
	}

	/**
	 * Cache of lookup_is_keyed_by_order_item(). Only `true` sticks: the re-key can land while a
	 * request runs (the "Verify base database tables" tool re-keys right before the tools list
	 * re-renders), and a cached `false` would outlive it.
	 *
	 * @var bool|null
	 */
	private static $lookup_keyed_by_order_item = null;

	/**
	 * Whether the lookup's primary key includes the tax order item.
	 *
	 * The re-key in `WC_Install::create_tables()` can fail on a large store, and dbDelta adds the
	 * `order_item_id` column either way. Writing a real tax order item id into a table still keyed
	 * on (order_id, tax_rate_id) collapses the lines sharing a rate into one row that the report
	 * then matches to a single line, which reads worse than it did before the column existed.
	 *
	 * `OrderTaxLookupMigrator` reads this too: a rebuild over such a table would write every row
	 * back at zero while stepping the cursor past it, so it waits instead.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	public static function lookup_is_keyed_by_order_item(): bool {
		global $wpdb;

		if ( true !== self::$lookup_keyed_by_order_item ) {
			$table_name = self::get_db_table_name();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
			self::$lookup_keyed_by_order_item = (bool) $wpdb->get_var( "SHOW KEYS FROM `{$table_name}` WHERE Key_name = 'PRIMARY' AND Column_name = 'order_item_id'" );
		}

		return self::$lookup_keyed_by_order_item;
	}

	/**
	 * Create or update an entry in the wc_order_tax_lookup table for an order.
	 *
	 * Writes one row per tax order item, in a single statement so that a write that does not land
	 * rebuilds none of the order's tax lines rather than some of them, then drops the rows the
	 * order held before and no longer carries.
	 *
	 * @param int $order_id Order ID.
	 * @return int|bool Returns -1 if order won't be processed, or a boolean indicating processing success.
	 */
	public static function sync_order_taxes( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );

		// An order with no creation date has nothing to date its lookup rows by, and
		// `WC_Data::set_date_prop()` leaves the date null for a zero datetime, not only for a
		// missing one. `OrdersScheduler::import()` leaves such an order out of the reports for the
		// same reason.
		if ( ! $order || ! $order->get_date_created( 'edit' ) ) {
			return -1;
		}

		$table_name   = self::get_db_table_name();
		$date_created = $order->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format );
		/**
		 * Tax line items of the order.
		 *
		 * @var \WC_Order_Item_Tax[] $tax_items
		 */
		$tax_items     = $order->get_items( OrderItemType::TAX );
		$keyed_by_item = self::lookup_is_keyed_by_order_item();

		// Read the rows the order already holds, so that the prune below names the rows this sync
		// found, the way the Products and Coupons stores do. Deleting everything outside the
		// snapshot instead would let two syncs that read different tax lines delete each other's
		// rows and leave the order with none.
		$existing_rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT tax_rate_id, order_item_id FROM {$table_name} WHERE order_id = %d",
				$order->get_id()
			),
			ARRAY_A
		);

		// Nothing has been written yet, so the order keeps the rows it came in with. Carrying on
		// would prune against an empty snapshot, which leaves every row the order no longer
		// carries in place for the reports to go on counting.
		if ( $wpdb->last_error ) {
			wc_get_logger()->error(
				"Could not read the analytics tax lookup rows of order {$order->get_id()}. The order keeps the rows it had and reports the way it did before.",
				array( 'source' => 'wc-order-tax-lookup' )
			);

			return false;
		}

		$stale  = array();
		$rows   = array();
		$values = array();

		foreach ( $existing_rows as $existing_row ) {
			$stale[ $existing_row['tax_rate_id'] . '-' . $existing_row['order_item_id'] ] = array(
				(int) $existing_row['tax_rate_id'],
				(int) $existing_row['order_item_id'],
			);
		}

		// Guard against the column not existing yet: wpdb::replace() with an unknown column
		// fails whole, which would silently drop the order from the Taxes report.
		$has_taxable_amount_column = static::has_taxable_amount_column();
		$taxable_amounts           = array();

		// Also skip orders without tax lines: computing bases would hydrate every
		// line item, fee and shipping row for a write that never happens.
		if ( $has_taxable_amount_column && ! empty( $tax_items ) ) {
			// A refund's tax items re-derive the compound flag from the live rate, which can
			// be gone by refund time; the parent's tax items carry the flags as charged, and
			// the refund base must mirror them or a refunded order stops netting to zero.
			$flag_items = $tax_items;
			if ( $order instanceof \WC_Order_Refund ) {
				$parent = wc_get_order( $order->get_parent_id() );
				if ( $parent ) {
					/**
					 * Tax line items of the parent order.
					 *
					 * @var \WC_Order_Item_Tax[] $parent_tax_items
					 */
					$parent_tax_items = $parent->get_items( OrderItemType::TAX );
					if ( ! empty( $parent_tax_items ) ) {
						$flag_items = $parent_tax_items;
					}
				}
			}

			$compound_rate_ids = array();
			foreach ( $flag_items as $tax_item ) {
				if ( $tax_item->get_compound() ) {
					$compound_rate_ids[] = $tax_item->get_rate_id();
				}
			}
			$taxable_amounts = static::get_taxable_amounts_by_rate( $order, $compound_rate_ids );
		}

		foreach ( $tax_items as $tax_item ) {
			// Leaving the column at zero on a table the re-key never reached keeps the row in the
			// shape the released report reads, rather than collapsing the order's tax lines into
			// one row the report matches to a single line.
			$order_item_id = $keyed_by_item ? $tax_item->get_id() : 0;
			$tax_rate_id   = (int) $tax_item->get_rate_id();

			// A row this sync is about to write is not stale. The key is the rate and the item
			// together, so a line whose rate id has changed leaves behind the row it held before.
			unset( $stale[ $tax_rate_id . '-' . $order_item_id ] );

			array_push(
				$values,
				$order->get_id(),
				$date_created,
				$tax_rate_id,
				$order_item_id,
				$tax_item->get_shipping_tax_total(),
				$tax_item->get_tax_total(),
				(float) $tax_item->get_tax_total() + (float) $tax_item->get_shipping_tax_total()
			);

			if ( $has_taxable_amount_column ) {
				// The bases are computed per rate and the report sums the column per rate, so
				// when tax lines share a rate only the first row carries the rate's base. On an
				// unkeyed table the lines collapse into one row and the last write wins, so
				// there every write carries the base.
				$values[] = $taxable_amounts[ $tax_rate_id ] ?? 0;
				if ( $keyed_by_item ) {
					unset( $taxable_amounts[ $tax_rate_id ] );
				}
				$rows[] = '(%d, %s, %d, %d, %f, %f, %f, %f)';
			} else {
				$rows[] = '(%d, %s, %d, %d, %f, %f, %f)';
			}
		}

		// One statement for the whole order. Rebuilding only some of its lines would leave a row
		// still on the order item column's zero default beside the rows written next to it, and
		// that row stands in for every line of the order sharing its rate, so the reports would
		// count those lines twice.
		if ( $rows ) {
			$columns = 'order_id, date_created, tax_rate_id, order_item_id, shipping_tax, order_tax, total_tax';
			if ( $has_taxable_amount_column ) {
				$columns .= ', taxable_amount';
			}

			$written = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name is not user input, and the value placeholders are built above, one set per tax line.
					"REPLACE INTO {$table_name} ({$columns}) VALUES " . implode( ', ', $rows ),
					$values
				)
			);

			if ( false === $written ) {
				wc_get_logger()->error(
					"Could not write the analytics tax lookup rows of order {$order->get_id()}. The order keeps the rows it had and reports the way it did before.",
					array( 'source' => 'wc-order-tax-lookup' )
				);

				return false;
			}
		}

		// Drop the rows the order came in with that it no longer carries, which includes the rows
		// written before the order item column existed, since those sit at zero. Prune only once
		// the writes have landed, so a write that did not land leaves the order with the rows it
		// came in with.
		if ( $stale ) {
			$keys       = array();
			$key_values = array( $order->get_id() );

			foreach ( $stale as $stale_key ) {
				$keys[] = '(%d, %d)';
				array_push( $key_values, $stale_key[0], $stale_key[1] );
			}

			$deleted = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name is not user input, and the key placeholders are built above, one pair per stale row.
					"DELETE FROM {$table_name} WHERE order_id = %d AND (tax_rate_id, order_item_id) IN (" . implode( ', ', $keys ) . ')',
					$key_values
				)
			);

			// A row the order no longer carries goes on being counted by the reports, so a prune
			// that failed is not a sync that succeeded.
			if ( false === $deleted ) {
				wc_get_logger()->error(
					"Could not drop the analytics tax lookup rows order {$order->get_id()} no longer carries. Its old rows stand beside the rows just written, so the reports count those tax lines twice until the order is synced again.",
					array( 'source' => 'wc-order-tax-lookup' )
				);

				return false;
			}
		}

		foreach ( $tax_items as $tax_item ) {
			/**
			 * Fires when tax's reports are updated.
			 *
			 * @param int $tax_rate_id Tax Rate ID.
			 * @param int $order_id    Order ID.
			 *
			 * @since 4.0.0
			 */
			do_action( 'woocommerce_analytics_update_tax', $tax_item->get_rate_id(), $order->get_id() );
		}

		return true;
	}

	/**
	 * Sum the net totals of the order parts (line items, fees, shipping) each tax rate applied to.
	 *
	 * Computed from the items rather than derived from the tax amounts, so zero-rated
	 * sales still record the base amount they were taxed on. A compound rate is applied
	 * on top of the other taxes of the same item, so its base includes them.
	 *
	 * @since 11.2.0
	 *
	 * @param \WC_Order|\WC_Order_Refund $order             Order object.
	 * @param int[]                      $compound_rate_ids Ids of the order's compound tax rates.
	 * @return array Map of tax rate id => taxable amount.
	 */
	protected static function get_taxable_amounts_by_rate( $order, $compound_rate_ids = array() ) {
		$amounts = array();
		/**
		 * Taxable line items of the order.
		 *
		 * @var \WC_Order_Item_Product[]|\WC_Order_Item_Fee[]|\WC_Order_Item_Shipping[] $items
		 */
		$items = $order->get_items( array( OrderItemType::LINE_ITEM, OrderItemType::FEE, OrderItemType::SHIPPING ) );

		foreach ( $items as $item ) {
			$taxes = $item->get_taxes();
			if ( empty( $taxes['total'] ) || ! is_array( $taxes['total'] ) ) {
				continue;
			}

			$non_compound_tax = 0.0;
			foreach ( $taxes['total'] as $rate_id => $tax ) {
				if ( ! in_array( (int) $rate_id, $compound_rate_ids, true ) ) {
					$non_compound_tax += (float) $tax;
				}
			}

			// Mirror WC_Tax::calc_exclusive_tax(): a compound rate is applied over the item
			// total plus all non-compound taxes plus the compound taxes applied before it.
			// Assumes the item tax data keeps core's rate order (compound rates after the
			// rates they compound over); a third-party engine writing another order would
			// only mis-split the base between multiple compound rates, not the total.
			$compound_running_tax = 0.0;
			foreach ( $taxes['total'] as $rate_id => $tax ) {
				$base = (float) $item->get_total();
				if ( in_array( (int) $rate_id, $compound_rate_ids, true ) ) {
					$base                 += $non_compound_tax + $compound_running_tax;
					$compound_running_tax += (float) $tax;
				}
				$amounts[ $rate_id ] = ( $amounts[ $rate_id ] ?? 0 ) + $base;
			}
		}

		return $amounts;
	}

	/**
	 * Clean taxes data when an order is deleted.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function sync_on_order_delete( $order_id ) {
		global $wpdb;

		$wpdb->delete( self::get_db_table_name(), array( 'order_id' => $order_id ) );

		/**
		 * Fires when tax's reports are removed from database.
		 *
		 * @param int $tax_rate_id Tax Rate ID.
		 * @param int $order_id    Order ID.
		 */
		do_action( 'woocommerce_analytics_delete_tax', 0, $order_id );

		ReportsCache::invalidate();
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		global $wpdb;

		$this->clear_all_clauses();
		$this->subquery = new SqlQuery( $this->context . '_subquery' );
		$this->subquery->add_sql_clause( 'select', self::get_db_table_name() . '.tax_rate_id' );
		$this->subquery->add_sql_clause( 'from', self::get_db_table_name() );
		$this->subquery->add_sql_clause( 'group_by', self::get_db_table_name() . '.tax_rate_id' );
		$this->subquery->add_sql_clause( 'group_by', ", {$wpdb->prefix}woocommerce_order_items.order_item_name, itemmeta_rate_percent.meta_value" );
	}
}
