<?php
/**
 * CustomOrdersTableController class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

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

	/**
	 * The name of the option for enabling the usage of the custom orders tables
	 */
	const CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION = 'woocommerce_custom_orders_table_enabled';

	/**
	 * The data store object to use.
	 *
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	/**
	 * The data synchronizer object to use.
	 *
	 * @var DataSynchronizer
	 */
	private $data_synchronizer;

	/**
	 * Is the feature visible?
	 *
	 * @var bool
	 */
	private $is_feature_visible;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->is_feature_visible = false;

		$this->init_hooks();
	}

	/**
	 * Initialize the hooks used by the class.
	 */
	private function init_hooks() {
		add_filter(
			'woocommerce_order_data_store',
			function ( $default_data_store ) {
				return $this->get_data_store_instance( $default_data_store );
			},
			999,
			1
		);

		add_filter(
			'woocommerce_debug_tools',
			function( $tools ) {
				return $this->add_initiate_regeneration_entry_to_tools_array( $tools );
			},
			999,
			1
		);

		add_filter(
			'woocommerce_get_sections_advanced',
			function( $sections ) {
				return $this->get_settings_sections( $sections );
			},
			999,
			1
		);

		add_filter(
			'woocommerce_get_settings_advanced',
			function ( $settings, $section_id ) {
				return $this->get_settings( $settings, $section_id );
			},
			999,
			2
		);
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrdersTableDataStore $data_store The data store to use.
	 * @param DataSynchronizer     $data_synchronizer The data synchronizer to use.
	 */
	final public function init( OrdersTableDataStore $data_store, DataSynchronizer $data_synchronizer ) {
		$this->data_store        = $data_store;
		$this->data_synchronizer = $data_synchronizer;
	}

	/**
	 * Checks if the feature is visible (so that dedicated entries will be added to the debug tools page).
	 *
	 * @return bool True if the feature is visible.
	 */
	public function is_feature_visible(): bool {
		return $this->is_feature_visible;
	}

	/**
	 * Makes the feature visible, so that dedicated entries will be added to the debug tools page.
	 */
	public function show_feature() {
		$this->is_feature_visible = true;
	}

	/**
	 * Hides the feature, so that no entries will be added to the debug tools page.
	 */
	public function hide_feature() {
		$this->is_feature_visible = false;
	}

	/**
	 * Is the custom orders table usage enabled via settings?
	 * This can be true only if the feature is enabled and a table regeneration has been completed.
	 *
	 * @return bool True if the custom orders table usage is enabled
	 */
	public function custom_orders_table_usage_is_enabled(): bool {
		return 'yes' === get_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION );
	}

	/**
	 * Gets the instance of the orders data store to use.
	 *
	 * @param WC_Object_Data_Store_Interface|string $default_data_store The default data store (as received via the woocommerce_order_data_store hooks).
	 * @return WC_Object_Data_Store_Interface|string The actual data store to use.
	 */
	private function get_data_store_instance( $default_data_store ) {
		if ( $this->is_feature_visible() && $this->custom_orders_table_usage_is_enabled() ) {
			return $this->data_store;
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
		if ( ! isset( $_REQUEST['_wpnonce'] ) || false === wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {
			throw new \Exception( 'Invalid nonce' );
		}

		if ( ! $this->is_feature_visible() ) {
			throw new \Exception( "Can't create the custom orders tables: the feature isn't enabled" );
		}

		$this->data_synchronizer->create_database_tables();
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
		if ( ! $this->is_feature_visible() || 'custom_data_stores' !== $section_id ) {
			return $settings;
		}

		$title_item = array(
			'title' => __( 'Custom orders tables', 'woocommerce' ),
			'type'  => 'title',
			'desc'  => sprintf(
				/* translators: %1$s = <strong> tag, %2$s = </strong> tag. */
				__( '%1$sWARNING:%2$s This feature is currently under development and may cause database instability. For contributors only.', 'woocommerce' ),
				'<strong>',
				'</strong>'
			),
		);

		if ( $this->data_synchronizer->check_orders_table_exists() ) {
			$settings[] = $title_item;

			$settings[] = array(
				'title'         => __( 'Enable tables usage', 'woocommerce' ),
				'desc'          => __( 'Use the custom orders tables as the main orders data store.', 'woocommerce' ),
				'id'            => self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
			);
		} else {
			$title_item['desc'] = sprintf(
				/* translators: %1$s = <em> tag, %2$s = </em> tag. */
				__( 'Create the tables first by going to %1$sWooCommerce > Status > Tools%2$s and running %1$sCreate the custom orders tables%2$s.', 'woocommerce' ),
				'<em>',
				'</em>'
			);
			$settings[] = $title_item;
		}

		$settings[] = array( 'type' => 'sectionend' );

		return $settings;
	}
}
