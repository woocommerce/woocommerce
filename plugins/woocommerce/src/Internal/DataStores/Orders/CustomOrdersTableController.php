<?php
/**
 * CustomOrdersTableController class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

defined( 'ABSPATH' ) || exit;

/**
 * This is the main class that controls the custom orders tables feature. Its responsibilities are:
 *
 * - Allowing to enable and disable the feature while it's in development (show_feature method)
 * - Displaying UI components (entries in the tools page and in settings)
 * - Providing the proper data store for orders via 'woocommerce_order_data_store' hook
 *
 * ...and in general, any functionality that doesn't imply database access.
 */
class CustomOrdersTableController {

	use AccessiblePrivateMethods;

	/**
	 * The name of the option for enabling the usage of the custom orders tables
	 */
	public const CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION = 'woocommerce_custom_orders_table_enabled';

	/**
	 * The name of the option that tells that the authoritative table must be flipped once sync finishes.
	 */
	private const AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION = 'woocommerce_auto_flip_authoritative_table_roles';

	/**
	 * The name of the option that tells whether database transactions are to be used or not for data synchronization.
	 */
	public const USE_DB_TRANSACTIONS_OPTION = 'woocommerce_use_db_transactions_for_custom_orders_table_data_sync';

	/**
	 * The name of the option to store the transaction isolation level to use when database transactions are enabled.
	 */
	public const DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION = 'woocommerce_db_transactions_isolation_level_for_custom_orders_table_data_sync';

	public const DEFAULT_DB_TRANSACTIONS_ISOLATION_LEVEL = 'REPEATABLE READ';

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
		self::add_filter( 'woocommerce_debug_tools', array( $this, 'add_initiate_regeneration_entry_to_tools_array' ), 999, 1 );
		self::add_filter( 'woocommerce_get_sections_advanced', array( $this, 'get_settings_sections' ), 999, 1 );
		self::add_filter( 'woocommerce_get_settings_advanced', array( $this, 'get_settings' ), 999, 2 );
		self::add_filter( 'updated_option', array( $this, 'process_updated_option' ), 999, 3 );
		self::add_filter( 'pre_update_option', array( $this, 'process_pre_update_option' ), 999, 3 );
		self::add_filter( DataSynchronizer::PENDING_SYNCHRONIZATION_FINISHED_ACTION, array( $this, 'process_sync_finished' ), 10, 0 );
		self::add_action( 'woocommerce_update_options_advanced_custom_data_stores', array( $this, 'process_options_updated' ), 10, 0 );
		self::add_action( 'woocommerce_after_register_post_type', array( $this, 'register_post_type_for_order_placeholders' ), 10, 0 );
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrdersTableDataStore       $data_store The data store to use.
	 * @param DataSynchronizer           $data_synchronizer The data synchronizer to use.
	 * @param OrdersTableRefundDataStore $refund_data_store The refund data store to use.
	 * @param BatchProcessingController  $batch_processing_controller The batch processing controller to use.
	 * @param FeaturesController         $features_controller The features controller instance to use.
	 */
	final public function init(
		OrdersTableDataStore $data_store,
		DataSynchronizer $data_synchronizer,
		OrdersTableRefundDataStore $refund_data_store,
		BatchProcessingController $batch_processing_controller,
		FeaturesController $features_controller ) {
		$this->data_store                  = $data_store;
		$this->data_synchronizer           = $data_synchronizer;
		$this->batch_processing_controller = $batch_processing_controller;
		$this->refund_data_store           = $refund_data_store;
		$this->features_controller         = $features_controller;
	}

	/**
	 * Checks if the feature is visible (so that dedicated entries will be added to the debug tools page).
	 *
	 * @return bool True if the feature is visible.
	 */
	public function is_feature_visible(): bool {
		return $this->features_controller->feature_is_enabled( 'custom_order_tables' );
	}

	/**
	 * Makes the feature visible, so that dedicated entries will be added to the debug tools page.
	 *
	 * This method shouldn't be used anymore, see the FeaturesController class.
	 */
	public function show_feature() {
		$class_and_method = ( new \ReflectionClass( $this ) )->getShortName() . '::' . __FUNCTION__;
		wc_doing_it_wrong(
			$class_and_method,
			__( "${class_and_method}: The visibility of the custom orders table feature is now handled by the WooCommerce features engine. See the FeaturesController class, or go to WooCommerce - Settings - Advanced - Features.", 'woocommerce' ),
			'7.0'
		);
	}

	/**
	 * Hides the feature, so that no entries will be added to the debug tools page.
	 *
	 * This method shouldn't be used anymore, see the FeaturesController class.
	 */
	public function hide_feature() {
		$class_and_method = ( new \ReflectionClass( $this ) )->getShortName() . '::' . __FUNCTION__;
		wc_doing_it_wrong(
			$class_and_method,
			__( "${class_and_method}: The visibility of the custom orders table feature is now handled by the WooCommerce features engine. See the FeaturesController class, or go to WooCommerce - Settings - Advanced - Features.", 'woocommerce' ),
			'7.0'
		);
	}

	/**
	 * Is the custom orders table usage enabled via settings?
	 * This can be true only if the feature is enabled and a table regeneration has been completed.
	 *
	 * @return bool True if the custom orders table usage is enabled
	 */
	public function custom_orders_table_usage_is_enabled(): bool {
		return $this->is_feature_visible() && get_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ) === 'yes';
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
	private function add_initiate_regeneration_entry_to_tools_array( array $tools_array ): array {
		if ( ! $this->is_feature_visible() ) {
			return $tools_array;
		}

		if ( $this->data_synchronizer->check_orders_table_exists() ) {
			$tools_array['delete_custom_orders_table'] = array(
				'name'             => __( 'Delete the custom orders tables', 'woocommerce' ),
				'desc'             => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This will delete the custom orders tables. The tables can be deleted only if they are not not in use (via Settings > Advanced > Custom data stores). You can create them again at any time with the "Create the custom orders tables" tool.', 'woocommerce' )
				),
				'requires_refresh' => true,
				'callback'         => function () {
					$this->delete_custom_orders_tables();
					return __( 'Custom orders tables have been deleted.', 'woocommerce' );
				},
				'button'           => __( 'Delete', 'woocommerce' ),
				'disabled'         => $this->custom_orders_table_usage_is_enabled(),
			);
		} else {
			$tools_array['create_custom_orders_table'] = array(
				'name'             => __( 'Create the custom orders tables', 'woocommerce' ),
				'desc'             => __( 'This tool will create the custom orders tables. Once created you can go to WooCommerce > Settings > Advanced > Custom data stores and configure the usage of the tables.', 'woocommerce' ),
				'requires_refresh' => true,
				'callback'         => function() {
					$this->create_custom_orders_tables();
					return __( 'Custom orders tables have been created. You can now go to WooCommerce > Settings > Advanced > Custom data stores.', 'woocommerce' );
				},
				'button'           => __( 'Create', 'woocommerce' ),
			);
		}

		return $tools_array;
	}

	/**
	 * Create the custom orders tables in response to the user pressing the tool button.
	 *
	 * @throws \Exception Can't create the tables.
	 */
	private function create_custom_orders_tables() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! isset( $_REQUEST['_wpnonce'] ) || wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) === false ) {
			throw new \Exception( 'Invalid nonce' );
		}

		if ( ! $this->is_feature_visible() ) {
			throw new \Exception( "Can't create the custom orders tables: the feature isn't enabled" );
		}

		$this->data_synchronizer->create_database_tables();
		update_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
	}

	/**
	 * Delete the custom orders tables and any related options and data in response to the user pressing the tool button.
	 *
	 * @throws \Exception Can't delete the tables.
	 */
	private function delete_custom_orders_tables() {
		if ( $this->custom_orders_table_usage_is_enabled() ) {
			throw new \Exception( "Can't delete the custom orders tables: they are currently in use (via Settings > Advanced > Custom data stores)." );
		}

		delete_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION );
		$this->data_synchronizer->delete_database_tables();
	}

	/**
	 * Get the settings sections for the "Advanced" tab, with a "Custom data stores" section added if appropriate.
	 *
	 * @param array $sections The original settings sections array.
	 * @return array The updated settings sections array.
	 */
	private function get_settings_sections( array $sections ): array {
		if ( ! $this->is_feature_visible() ) {
			return $sections;
		}

		$sections['custom_data_stores'] = __( 'Custom data stores', 'woocommerce' );

		return $sections;
	}

	/**
	 * Get the settings for the "Custom data stores" section in the "Advanced" tab,
	 * with entries for managing the custom orders tables if appropriate.
	 *
	 * @param array  $settings The original settings array.
	 * @param string $section_id The settings section to get the settings for.
	 * @return array The updated settings array.
	 */
	private function get_settings( array $settings, string $section_id ): array {
		if ( ! $this->is_feature_visible() || $section_id !== 'custom_data_stores' ) {
			return $settings;
		}

		if ( $this->data_synchronizer->check_orders_table_exists() ) {
			$settings[] = array(
				'title' => __( 'Custom orders tables', 'woocommerce' ),
				'type'  => 'title',
				'id'    => 'cot-title',
				'desc'  => sprintf(
					/* translators: %1$s = <strong> tag, %2$s = </strong> tag. */
					__( '%1$sWARNING:%2$s This feature is currently under development and may cause database instability. For contributors only.', 'woocommerce' ),
					'<strong>',
					'</strong>'
				),
			);

			$sync_status     = $this->data_synchronizer->get_sync_status();
			$sync_is_pending = $sync_status['current_pending_count'] !== 0;

			$settings[] = array(
				'title'         => __( 'Data store for orders', 'woocommerce' ),
				'id'            => self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
				'default'       => 'no',
				'type'          => 'radio',
				'options'       => array(
					'yes' => __( 'Use the WooCommerce orders tables', 'woocommerce' ),
					'no'  => __( 'Use the WordPress posts table', 'woocommerce' ),
				),
				'checkboxgroup' => 'start',
				'disabled'      => $sync_is_pending ? array( 'yes', 'no' ) : array(),
			);

			if ( $sync_is_pending ) {
				$initial_pending_count = $sync_status['initial_pending_count'];
				$current_pending_count = $sync_status['current_pending_count'];
				if ( $initial_pending_count ) {
					$text =
						sprintf(
							/* translators: %1$s=current number of orders pending sync, %2$s=initial number of orders pending sync */
							_n( 'There\'s %1$s order (out of a total of %2$s) pending sync!', 'There are %1$s orders (out of a total of %2$s) pending sync!', $current_pending_count, 'woocommerce' ),
							$current_pending_count,
							$initial_pending_count
						);
				} else {
					$text =
						/* translators: %s=initial number of orders pending sync */
						sprintf( _n( 'There\'s %s order pending sync!', 'There are %s orders pending sync!', $current_pending_count, 'woocommerce' ), $current_pending_count, 'woocommerce' );
				}

				if ( $this->batch_processing_controller->is_enqueued( get_class( $this->data_synchronizer ) ) ) {
					$text .= __( "<br/>Synchronization for these orders is currently in progress.<br/>The authoritative table can't be changed until sync completes.", 'woocommerce' );
				} else {
					$text .= __( "<br/>The authoritative table can't be changed until these orders are synchronized.", 'woocommerce' );
				}

				$settings[] = array(
					'type' => 'info',
					'id'   => 'cot-out-of-sync-warning',
					'css'  => 'color: #C00000',
					'text' => $text,
				);
			}

			$settings[] = array(
				'desc' => __( 'Keep the posts table and the orders tables synchronized', 'woocommerce' ),
				'id'   => DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
				'type' => 'checkbox',
			);

			if ( $sync_is_pending ) {
				if ( $this->data_synchronizer->data_sync_is_enabled() ) {
					$message    = $this->custom_orders_table_usage_is_enabled() ?
						__( 'Switch to using the posts table as the authoritative data store for orders when sync finishes', 'woocommerce' ) :
						__( 'Switch to using the orders table as the authoritative data store for orders when sync finishes', 'woocommerce' );
					$settings[] = array(
						'desc' => $message,
						'id'   => self::AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION,
						'type' => 'checkbox',
					);
				}
			}

			$settings[] = array(
				'desc' => __( 'Use database transactions for the orders data synchronization', 'woocommerce' ),
				'id'   => self::USE_DB_TRANSACTIONS_OPTION,
				'type' => 'checkbox',
			);

			$isolation_level_names = self::get_valid_transaction_isolation_levels();
			$settings[]            = array(
				'desc'    => __( 'Database transaction isolation level to use', 'woocommerce' ),
				'id'      => self::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION,
				'type'    => 'select',
				'options' => array_combine( $isolation_level_names, $isolation_level_names ),
				'default' => self::DEFAULT_DB_TRANSACTIONS_ISOLATION_LEVEL,
			);
		} else {
			$settings[] = array(
				'title' => __( 'Custom orders tables', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => sprintf(
					/* translators: %1$s = <em> tag, %2$s = </em> tag. */
					__( 'Create the tables first by going to %1$sWooCommerce > Status > Tools%2$s and running %1$sCreate the custom orders tables%2$s.', 'woocommerce' ),
					'<em>',
					'</em>'
				),
			);
		}

		$settings[] = array( 'type' => 'sectionend' );

		return $settings;
	}

	/**
	 * Get the valid database transaction isolation level names.
	 *
	 * @return string[]
	 */
	public static function get_valid_transaction_isolation_levels() {
		return array(
			'REPEATABLE READ',
			'READ COMMITTED',
			'READ UNCOMMITTED',
			'SERIALIZABLE',
		);
	}

	/**
	 * Handler for the individual setting updated hook.
	 *
	 * @param string $option Setting name.
	 * @param mixed  $old_value Old value of the setting.
	 * @param mixed  $value New value of the setting.
	 */
	private function process_updated_option( $option, $old_value, $value ) {
		if ( $option === DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION && $value === 'no' ) {
			$this->data_synchronizer->cleanup_synchronization_state();
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
		if ( $option !== self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION || $value === $old_value || $old_value === false ) {
			return $value;
		}

		/**
		 * Commenting out for better testability.
		$sync_is_pending = 0 !== $this->data_synchronizer->get_current_orders_pending_sync_count();
		if ( $sync_is_pending ) {
			throw new \Exception( "The authoritative table for orders storage can't be changed while there are orders out of sync" );
		}
		 */

		return $value;
	}

	/**
	 * Handler for the synchronization finished hook.
	 * Here we switch the authoritative table if needed.
	 */
	private function process_sync_finished() {
		if ( ! $this->auto_flip_authoritative_table_enabled() ) {
			return;
		}

		update_option( self::AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION, 'no' );

		if ( $this->custom_orders_table_usage_is_enabled() ) {
			update_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		} else {
			update_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		}
	}

	/**
	 * Is the automatic authoritative table switch setting set?
	 *
	 * @return bool
	 */
	private function auto_flip_authoritative_table_enabled(): bool {
		return get_option( self::AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION ) === 'yes';
	}

	/**
	 * Handler for the all settings updated hook.
	 */
	private function process_options_updated() {
		$data_sync_is_enabled = $this->data_synchronizer->data_sync_is_enabled();

		// Disabling the sync implies disabling the automatic authoritative table switch too.
		if ( ! $data_sync_is_enabled && $this->auto_flip_authoritative_table_enabled() ) {
			update_option( self::AUTO_FLIP_AUTHORITATIVE_TABLE_ROLES_OPTION, 'no' );
		}

		// Enabling/disabling the sync implies starting/stopping it too, if needed.
		// We do this check here, and not in process_pre_update_option, so that if for some reason
		// the setting is enabled but no sync is in process, sync will start by just saving the
		// settings even without modifying them (and the opposite: sync will be stopped if for
		// some reason it was ongoing while it was disabled).
		if ( $data_sync_is_enabled ) {
			$this->batch_processing_controller->enqueue_processor( DataSynchronizer::class );
		} else {
			$this->batch_processing_controller->remove_processor( DataSynchronizer::class );
		}
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
}
