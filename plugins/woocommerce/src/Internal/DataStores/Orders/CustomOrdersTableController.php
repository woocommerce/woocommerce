<?php
/**
 * CustomOrdersTableController class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Caches\OrderCacheController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Utilities\PluginUtil;
use WC_Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * This is the main class that controls the custom orders tables feature. Its responsibilities are:
 *
 * - Displaying UI components (entries in the tools page and in settings)
 * - Providing the proper data store for orders via 'woocommerce_order_data_store' hook
 *
 * ...and in general, any functionality that doesn't imply database access.
 */
class CustomOrdersTableController {

	use AccessiblePrivateMethods;

	private const SYNC_QUERY_ARG = 'wc_hpos_sync_now';

	/**
	 * The name of the option for enabling the usage of the custom orders tables
	 */
	public const CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION = 'woocommerce_custom_orders_table_enabled';

	/**
	 * The name of the option that tells whether database transactions are to be used or not for data synchronization.
	 */
	public const USE_DB_TRANSACTIONS_OPTION = 'woocommerce_use_db_transactions_for_custom_orders_table_data_sync';

	/**
	 * The name of the option to store the transaction isolation level to use when database transactions are enabled.
	 */
	public const DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION = 'woocommerce_db_transactions_isolation_level_for_custom_orders_table_data_sync';

	public const DEFAULT_DB_TRANSACTIONS_ISOLATION_LEVEL = 'READ UNCOMMITTED';

	public const HPOS_FTS_INDEX_OPTION = 'woocommerce_hpos_fts_index_enabled';

	public const HPOS_FTS_ADDRESS_INDEX_CREATED_OPTION = 'woocommerce_hpos_address_fts_index_created';

	public const HPOS_FTS_ORDER_ITEM_INDEX_CREATED_OPTION = 'woocommerce_hpos_order_item_fts_index_created';

	/**
	 * The data store object to use.
	 *
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	/**
	 * Refunds data store object to use.
	 *
	 * @var OrdersTableRefundDataStore
	 */
	private $refund_data_store;

	/**
	 * The data synchronizer object to use.
	 *
	 * @var DataSynchronizer
	 */
	private $data_synchronizer;

	/**
	 * The data cleanup instance to use.
	 *
	 * @var LegacyDataCleanup
	 */
	private $data_cleanup;

	/**
	 * The batch processing controller to use.
	 *
	 * @var BatchProcessingController
	 */
	private $batch_processing_controller;

	/**
	 * The features controller to use.
	 *
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * The orders cache object to use.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * The orders cache controller object to use.
	 *
	 * @var OrderCacheController
	 */
	private $order_cache_controller;

	/**
	 * The plugin util object to use.
	 *
	 * @var PluginUtil
	 */
	private $plugin_util;

	/**
	 * The db util object to use.
	 *
	 * @var DatabaseUtil;
	 */
	private $db_util;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize the hooks used by the class.
	 */
	private function init_hooks() {
		self::add_filter( 'woocommerce_order_data_store', array( $this, 'get_orders_data_store' ), 999, 1 );
		self::add_filter( 'woocommerce_order-refund_data_store', array( $this, 'get_refunds_data_store' ), 999, 1 );
		self::add_filter( 'woocommerce_debug_tools', array( $this, 'add_hpos_tools' ), 999 );
		self::add_filter( 'updated_option', array( $this, 'process_updated_option' ), 999, 3 );
		self::add_filter( 'updated_option', array( $this, 'process_updated_option_fts_index' ), 999, 3 );
		self::add_filter( 'pre_update_option', array( $this, 'process_pre_update_option' ), 999, 3 );
		self::add_action( 'woocommerce_after_register_post_type', array( $this, 'register_post_type_for_order_placeholders' ), 10, 0 );
		self::add_action( 'woocommerce_sections_advanced', array( $this, 'sync_now' ) );
		self::add_filter( 'removable_query_args', array( $this, 'register_removable_query_arg' ) );
		self::add_action( 'woocommerce_register_feature_definitions', array( $this, 'add_feature_definition' ) );
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrdersTableDataStore       $data_store The data store to use.
	 * @param DataSynchronizer           $data_synchronizer The data synchronizer to use.
	 * @param LegacyDataCleanup          $data_cleanup The legacy data cleanup instance to use.
	 * @param OrdersTableRefundDataStore $refund_data_store The refund data store to use.
	 * @param BatchProcessingController  $batch_processing_controller The batch processing controller to use.
	 * @param FeaturesController         $features_controller The features controller instance to use.
	 * @param OrderCache                 $order_cache The order cache engine to use.
	 * @param OrderCacheController       $order_cache_controller The order cache controller to use.
	 * @param PluginUtil                 $plugin_util The plugin util to use.
	 * @param DatabaseUtil               $db_util The database util to use.
	 */
	final public function init(
		OrdersTableDataStore $data_store,
		DataSynchronizer $data_synchronizer,
		LegacyDataCleanup $data_cleanup,
		OrdersTableRefundDataStore $refund_data_store,
		BatchProcessingController $batch_processing_controller,
		FeaturesController $features_controller,
		OrderCache $order_cache,
		OrderCacheController $order_cache_controller,
		PluginUtil $plugin_util,
		DatabaseUtil $db_util
	) {
		$this->data_store                  = $data_store;
		$this->data_synchronizer           = $data_synchronizer;
		$this->data_cleanup                = $data_cleanup;
		$this->batch_processing_controller = $batch_processing_controller;
		$this->refund_data_store           = $refund_data_store;
		$this->features_controller         = $features_controller;
		$this->order_cache                 = $order_cache;
		$this->order_cache_controller      = $order_cache_controller;
		$this->plugin_util                 = $plugin_util;
		$this->db_util                     = $db_util;
	}

	/**
	 * Is the custom orders table usage enabled via settings?
	 * This can be true only if the feature is enabled and a table regeneration has been completed.
	 *
	 * @return bool True if the custom orders table usage is enabled
	 */
	public function custom_orders_table_usage_is_enabled(): bool {
		return get_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ) === 'yes';
	}

	/**
	 * Gets the instance of the orders data store to use.
	 *
	 * @param \WC_Object_Data_Store_Interface|string $default_data_store The default data store (as received via the woocommerce_order_data_store hook).
	 *
	 * @return \WC_Object_Data_Store_Interface|string The actual data store to use.
	 */
	private function get_orders_data_store( $default_data_store ) {
		return $this->get_data_store_instance( $default_data_store, 'order' );
	}

	/**
	 * Gets the instance of the refunds data store to use.
	 *
	 * @param \WC_Object_Data_Store_Interface|string $default_data_store The default data store (as received via the woocommerce_order-refund_data_store hook).
	 *
	 * @return \WC_Object_Data_Store_Interface|string The actual data store to use.
	 */
	private function get_refunds_data_store( $default_data_store ) {
		return $this->get_data_store_instance( $default_data_store, 'order_refund' );
	}

	/**
	 * Gets the instance of a given data store.
	 *
	 * @param \WC_Object_Data_Store_Interface|string $default_data_store The default data store (as received via the appropriate hooks).
	 * @param string                                 $type               The type of the data store to get.
	 *
	 * @return \WC_Object_Data_Store_Interface|string The actual data store to use.
	 */
	private function get_data_store_instance( $default_data_store, string $type ) {
		if ( $this->custom_orders_table_usage_is_enabled() ) {
			switch ( $type ) {
				case 'order_refund':
					return $this->refund_data_store;
				default:
					return $this->data_store;
			}
		} else {
			return $default_data_store;
		}
	}

	/**
	 * Add an entry to Status - Tools to create or regenerate the custom orders table,
	 * and also an entry to delete the table as appropriate.
	 *
	 * @param array $tools_array The array of tools to add the tool to.
	 * @return array The updated array of tools-
	 */
	private function add_hpos_tools( array $tools_array ): array {
		if ( ! $this->data_synchronizer->check_orders_table_exists() ) {
			return $tools_array;
		}

		// Cleanup tool.
		$tools_array = array_merge( $tools_array, $this->data_cleanup->get_tools_entries() );

		// Delete HPOS tables tool.
		if ( $this->custom_orders_table_usage_is_enabled() || $this->data_synchronizer->data_sync_is_enabled() ) {
			$disabled = true;
			$message  = __( 'This will delete the custom orders tables. The tables can be deleted only if the "High-Performance order storage" is not authoritative and sync is disabled (via Settings > Advanced > Features).', 'woocommerce' );
		} else {
			$disabled = false;
			$message  = __( 'This will delete the custom orders tables. To create them again enable the "High-Performance order storage" feature (via Settings > Advanced > Features).', 'woocommerce' );
		}

		$tools_array['delete_custom_orders_table'] = array(
			'name'             => __( 'Delete the custom orders tables', 'woocommerce' ),
			'desc'             => sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				__( 'Note:', 'woocommerce' ),
				$message
			),
			'requires_refresh' => true,
			'callback'         => function () {
				$this->features_controller->change_feature_enable( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, false );
				$this->delete_custom_orders_tables();
				return __( 'Custom orders tables have been deleted.', 'woocommerce' );
			},
			'button'           => __( 'Delete', 'woocommerce' ),
			'disabled'         => $disabled,
		);

		return $tools_array;
	}

	/**
	 * Delete the custom orders tables and any related options and data in response to the user pressing the tool button.
	 *
	 * @throws \Exception Can't delete the tables.
	 */
	private function delete_custom_orders_tables() {
		if ( $this->custom_orders_table_usage_is_enabled() ) {
			throw new \Exception( "Can't delete the custom orders tables: they are currently in use (via Settings > Advanced > Features)." );
		}

		delete_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION );
		$this->data_synchronizer->delete_database_tables();
	}

	/**
	 * Handler for the individual setting updated hook.
	 *
	 * @param string $option Setting name.
	 * @param mixed  $old_value Old value of the setting.
	 * @param mixed  $value New value of the setting.
	 */
	private function process_updated_option( $option, $old_value, $value ) {
		if ( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION === $option && 'no' === $value ) {
			$this->data_synchronizer->cleanup_synchronization_state();
		}
	}

	/**
	 * Process option that enables FTS index on orders table. Tries to create an FTS index when option is enabled.
	 *
	 * @param string $option Option name.
	 * @param string $old_value Old value of the option.
	 * @param string $value New value of the option.
	 *
	 * @return void
	 */
	private function process_updated_option_fts_index( $option, $old_value, $value ) {
		if ( self::HPOS_FTS_INDEX_OPTION !== $option ) {
			return;
		}

		if ( 'yes' !== $value ) {
			return;
		}

		if ( ! $this->custom_orders_table_usage_is_enabled() ) {
			update_option( self::HPOS_FTS_INDEX_OPTION, 'no', true );
			if ( class_exists( 'WC_Admin_Settings' ) ) {
				WC_Admin_Settings::add_error( __( 'Failed to create FTS index on orders table. This feature is only available when High-performance order storage is enabled.', 'woocommerce' ) );
			}
			return;
		}

		if ( ! $this->db_util->fts_index_on_order_address_table_exists() ) {
			$this->db_util->create_fts_index_order_address_table();
		}

		// Check again to see if index was actually created.
		if ( $this->db_util->fts_index_on_order_address_table_exists() ) {
			update_option( self::HPOS_FTS_ADDRESS_INDEX_CREATED_OPTION, 'yes', true );
		} else {
			update_option( self::HPOS_FTS_ADDRESS_INDEX_CREATED_OPTION, 'no', true );
			if ( class_exists( 'WC_Admin_Settings ' ) ) {
				WC_Admin_Settings::add_error( __( 'Failed to create FTS index on address table', 'woocommerce' ) );
			}
		}

		if ( ! $this->db_util->fts_index_on_order_item_table_exists() ) {
			$this->db_util->create_fts_index_order_item_table();
		}

		// Check again to see if index was actually created.
		if ( $this->db_util->fts_index_on_order_item_table_exists() ) {
			update_option( self::HPOS_FTS_ORDER_ITEM_INDEX_CREATED_OPTION, 'yes', true );
		} else {
			update_option( self::HPOS_FTS_ORDER_ITEM_INDEX_CREATED_OPTION, 'no', true );
			if ( class_exists( 'WC_Admin_Settings ' ) ) {
				WC_Admin_Settings::add_error( __( 'Failed to create FTS index on order item table', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Handler for the setting pre-update hook.
	 * We use it to verify that authoritative orders table switch doesn't happen while sync is pending.
	 *
	 * @param mixed  $value New value of the setting.
	 * @param string $option Setting name.
	 * @param mixed  $old_value Old value of the setting.
	 *
	 * @throws \Exception Attempt to change the authoritative orders table while orders sync is pending.
	 */
	private function process_pre_update_option( $value, $option, $old_value ) {
		if ( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION === $option && $value !== $old_value ) {
			$this->order_cache->flush();
			return $value;
		}

		if ( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION !== $option ) {
			return $value;
		}

		if ( $old_value === $value ) {
			return $value;
		}

		$this->order_cache->flush();
		if ( ! $this->data_synchronizer->check_orders_table_exists() ) {
			$this->data_synchronizer->create_database_tables();
		}

		$tables_created = get_option( DataSynchronizer::ORDERS_TABLE_CREATED ) === 'yes';
		if ( ! $tables_created ) {
			return 'no';
		}

		$sync_is_pending = 0 !== $this->data_synchronizer->get_current_orders_pending_sync_count();
		if ( $sync_is_pending && ! $this->changing_data_source_with_sync_pending_is_allowed() ) {
			throw new \Exception( "The authoritative table for orders storage can't be changed while there are orders out of sync" );
		}

		return $value;
	}

	/**
	 * Callback to trigger a sync immediately by clicking a button on the Features screen.
	 *
	 * @return void
	 */
	private function sync_now() {
		$section = filter_input( INPUT_GET, 'section' );
		if ( 'features' !== $section ) {
			return;
		}

		if ( ! filter_input( INPUT_GET, self::SYNC_QUERY_ARG, FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'hpos-sync-now' ) ) {
			WC_Admin_Settings::add_error( esc_html__( 'Unable to start synchronization. The link you followed may have expired.', 'woocommerce' ) );
			return;
		}

		$this->data_cleanup->toggle_flag( false );
		$this->batch_processing_controller->enqueue_processor( DataSynchronizer::class );
	}

	/**
	 * Tell WP Admin to remove the sync query arg from the URL.
	 *
	 * @param array $query_args The query args that are removable.
	 *
	 * @return array
	 */
	private function register_removable_query_arg( $query_args ) {
		$query_args[] = self::SYNC_QUERY_ARG;

		return $query_args;
	}

	/**
	 * Handler for the woocommerce_after_register_post_type post,
	 * registers the post type for placeholder orders.
	 *
	 * @return void
	 */
	private function register_post_type_for_order_placeholders(): void {
		wc_register_order_type(
			DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE,
			array(
				'public'                           => false,
				'exclude_from_search'              => true,
				'publicly_queryable'               => false,
				'show_ui'                          => false,
				'show_in_menu'                     => false,
				'show_in_nav_menus'                => false,
				'show_in_admin_bar'                => false,
				'show_in_rest'                     => false,
				'rewrite'                          => false,
				'query_var'                        => false,
				'can_export'                       => false,
				'supports'                         => array(),
				'capabilities'                     => array(),
				'exclude_from_order_count'         => true,
				'exclude_from_order_views'         => true,
				'exclude_from_order_reports'       => true,
				'exclude_from_order_sales_reports' => true,
			)
		);
	}

	/**
	 * Add the definition for the HPOS feature.
	 *
	 * @param FeaturesController $features_controller The instance of FeaturesController.
	 *
	 * @return void
	 */
	private function add_feature_definition( $features_controller ) {
		$definition = array(
			'option_key'          => self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
			'is_experimental'     => false,
			'enabled_by_default'  => false,
			'order'               => 50,
			'setting'             => $this->get_hpos_setting_for_feature(),
			'additional_settings' => array(
				$this->get_hpos_setting_for_sync(),
			),
		);

		$features_controller->add_feature_definition(
			'custom_order_tables',
			__( 'High-Performance order storage', 'woocommerce' ),
			$definition
		);
	}

	/**
	 * Returns the HPOS setting for rendering HPOS vs Post setting block in Features section of the settings page.
	 *
	 * @return array Feature setting object.
	 */
	private function get_hpos_setting_for_feature() {
		if ( 'yes' === get_transient( 'wc_installing' ) ) {
			return array();
		}

		$get_value = function () {
			return $this->custom_orders_table_usage_is_enabled() ? 'yes' : 'no';
		};

		/**
		 * ⚠️The FeaturesController instance must only be accessed from within the callback functions. Otherwise it
		 * gets called while it's still being instantiated and creates and endless loop.
		 */

		$get_desc = function () {
			$plugin_compatibility = $this->features_controller->get_compatible_plugins_for_feature( 'custom_order_tables', true );

			return $this->plugin_util->generate_incompatible_plugin_feature_warning( 'custom_order_tables', $plugin_compatibility );
		};

		$get_disabled = function () {
			$plugin_compatibility = $this->features_controller->get_compatible_plugins_for_feature( 'custom_order_tables', true );
			$sync_complete        = 0 === $this->get_orders_pending_sync_count();
			$disabled             = array();
			// Changing something here? might also want to look at `enable|disable` functions in CLIRunner.
			if ( count( array_merge( $plugin_compatibility['uncertain'], $plugin_compatibility['incompatible'] ) ) > 0 ) {
				$disabled = array( 'yes' );
			}
			if ( ! $sync_complete && ! $this->changing_data_source_with_sync_pending_is_allowed() ) {
				$disabled = array( 'yes', 'no' );
			}

			return $disabled;
		};

		return array(
			'id'          => self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
			'title'       => __( 'Order data storage', 'woocommerce' ),
			'type'        => 'radio',
			'options'     => array(
				'no'  => __( 'WordPress posts storage (legacy)', 'woocommerce' ),
				'yes' => __( 'High-performance order storage (recommended)', 'woocommerce' ),
			),
			'value'       => $get_value,
			'disabled'    => $get_disabled,
			'desc'        => $get_desc,
			'desc_at_end' => true,
			'row_class'   => self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
		);
	}

	/**
	 * Returns the setting for rendering sync enabling setting block in Features section of the settings page.
	 *
	 * @return array Feature setting object.
	 */
	private function get_hpos_setting_for_sync() {
		if ( 'yes' === get_transient( 'wc_installing' ) ) {
			return array();
		}

		$get_value = function () {
			return get_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
		};

		$get_sync_message = function () {
			$orders_pending_sync_count = $this->get_orders_pending_sync_count();
			$sync_in_progress          = $this->batch_processing_controller->is_enqueued( get_class( $this->data_synchronizer ) );
			$sync_enabled              = $this->data_synchronizer->data_sync_is_enabled();
			$sync_is_pending           = $orders_pending_sync_count > 0;
			$sync_message              = array();

			$is_dangerous = $sync_is_pending && $this->changing_data_source_with_sync_pending_is_allowed();

			if ( $is_dangerous ) {
				$sync_message[] = wp_kses_data(
					sprintf(
					// translators: %d: number of pending orders.
						_n(
							"There's %d order pending sync. <b>Switching data storage while sync is incomplete is dangerous and can lead to order data corruption or loss!</b>",
							'There are %d orders pending sync. <b>Switching data storage while sync is incomplete is dangerous and can lead to order data corruption or loss!</b>',
							$orders_pending_sync_count,
							'woocommerce'
						),
						$orders_pending_sync_count,
					)
				);
			}

			if ( ! $sync_enabled && $this->data_synchronizer->background_sync_is_enabled() ) {
				$sync_message[] = __( 'Background sync is enabled.', 'woocommerce' );
			}

			if ( $sync_in_progress && $sync_is_pending ) {
				$sync_message[] = sprintf(
					// translators: %d: number of pending orders.
					__( 'Currently syncing orders... %d pending', 'woocommerce' ),
					$orders_pending_sync_count
				);
			} elseif ( $sync_is_pending ) {
				$sync_now_url = wp_nonce_url(
					add_query_arg(
						array(
							self::SYNC_QUERY_ARG => true,
						),
						wc_get_container()->get( FeaturesController::class )->get_features_page_url()
					),
					'hpos-sync-now'
				);

				if ( ! $is_dangerous ) {
					$sync_message[] = wp_kses_data(
						sprintf(
						// translators: %d: number of pending orders.
							_n(
								"There's %d order pending sync. You can switch order data storage <strong>only when the posts and orders tables are in sync</strong>.",
								'There are %d orders pending sync. You can switch order data storage <strong>only when the posts and orders tables are in sync</strong>.',
								$orders_pending_sync_count,
								'woocommerce'
							),
							$orders_pending_sync_count
						)
					);
				}

				$sync_message[] = sprintf(
					'<a href="%1$s" class="button button-link">%2$s</a>',
					esc_url( $sync_now_url ),
					sprintf(
						// translators: %d: number of pending orders.
						_n(
							'Sync %s pending order',
							'Sync %s pending orders',
							$orders_pending_sync_count,
							'woocommerce'
						),
						number_format_i18n( $orders_pending_sync_count )
					)
				);
			}

			return implode( '<br />', $sync_message );
		};

		$get_description_is_error = function () {
			$sync_is_pending = $this->get_orders_pending_sync_count() > 0;

			return $sync_is_pending && $this->changing_data_source_with_sync_pending_is_allowed();
		};

		return array(
			'id'                   => DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
			'title'                => '',
			'type'                 => 'checkbox',
			'desc'                 => __( 'Enable compatibility mode (synchronizes orders to the posts table).', 'woocommerce' ),
			'value'                => $get_value,
			'desc_tip'             => $get_sync_message,
			'description_is_error' => $get_description_is_error,
			'row_class'            => DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
		);
	}

	/**
	 * Returns a value indicating if changing the authoritative data source for orders while there are orders pending synchronization is allowed.
	 *
	 * @return bool
	 */
	private function changing_data_source_with_sync_pending_is_allowed(): bool {
		/**
		 * Filter to allow changing where order data is stored, even when there are orders pending synchronization.
		 *
		 * DANGER! This filter is intended for usage when doing manual and automated testing in development environments only,
		 * it should NEVER be used in production environments. Order data corruption or loss can happen!
		 *
		 * @param bool $allow True to allow changing order storage when there are orders pending synchronization, false to disallow.
		 * @returns bool
		 *
		 * @since 8.3.0
		 */
		return apply_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending', false );
	}

	/**
	 * Returns the count of orders pending synchronization.
	 *
	 * @return int
	 */
	private function get_orders_pending_sync_count(): int {
		return $this->data_synchronizer->get_sync_status()['current_pending_count'];
	}
}
