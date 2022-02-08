<?php
/**
 * CustomOrdersTableController class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * This is the main class that controls the custom orders table feature. Its responsibilities are:
 *
 * - Allowing to enable and disable the feature while it's in development (show_feature method)
 * - Displaying UI components (entries in the tools page and in settings)
 * - Providing the proper data store for orders via 'woocommerce_order_data_store' hook
 *
 * ...and in general, any functionality that doesn't imply database access.
 */
class CustomOrdersTableController {

	/**
	 * The name of the option for enabling the usage of the custom orders table
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
			10
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
		if ( $this->is_feature_visible() && $this->custom_orders_table_usage_is_enabled() && ! $this->data_synchronizer->data_regeneration_is_in_progress() ) {
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
	private function add_initiate_regeneration_entry_to_tools_array(array $tools_array ): array {
		if ( ! $this->is_feature_visible() ) {
			return $tools_array;
		}

		$orders_table_exists       = $this->data_synchronizer->check_orders_table_exists();
		$generation_is_in_progress = $this->data_synchronizer->data_regeneration_is_in_progress();

		if ( $orders_table_exists ) {
			$generate_item_name   = __( 'Regenerate the custom orders table', 'woocommerce' );
			$generate_item_desc   = __( 'This tool will regenerate the custom orders table data from existing orders data from the posts table. This process may take a while.', 'woocommerce' );
			$generate_item_return = __( 'Custom orders table is being regenerated', 'woocommerce' );
			$generate_item_button = __( 'Regenerate', 'woocommerce' );
		} else {
			$generate_item_name   = __( 'Create and fill custom orders table', 'woocommerce' );
			$generate_item_desc   = __( 'This tool will create the custom orders table and fill it with existing orders data from the posts table. This process may take a while.', 'woocommerce' );
			$generate_item_return = __( 'Custom orders table is being filled', 'woocommerce' );
			$generate_item_button = __( 'Create', 'woocommerce' );
		}

		$entry = array(
			'name'             => $generate_item_name,
			'desc'             => $generate_item_desc,
			'requires_refresh' => true,
			'callback'         => function() use ( $generate_item_return ) {
				$this->initiate_regeneration_from_tools_page();
				return $generate_item_return;
			},
		);

		if ( $generation_is_in_progress ) {
			$entry['button'] = sprintf(
			/* translators: %d: How many orders have been processed so far. */
				__( 'Filling in progress (%d)', 'woocommerce' ),
				$this->data_synchronizer->get_regeneration_processed_orders_count()
			);
			$entry['disabled'] = true;
		} else {
			$entry['button'] = $generate_item_button;
		}

		$tools_array['regenerate_custom_orders_table'] = $entry;

		if ( $orders_table_exists ) {

			// Delete the table.

			$tools_array['delete_custom_orders_table'] = array(
				'name'             => __( 'Delete the custom orders table', 'woocommerce' ),
				'desc'             => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This will delete the custom orders table. You can create it again with the "Create and fill custom orders table" tool.', 'woocommerce' )
				),
				'button'           => __( 'Delete', 'woocommerce' ),
				'requires_refresh' => true,
				'callback'         => function () {
					$this->delete_custom_orders_table();
					return __( 'Custom orders table has been deleted.', 'woocommerce' );
				},
			);
		}

		return $tools_array;
	}

	/**
	 * Initiate the custom orders table (re)generation in response to the user pressing the tool button.
	 *
	 * @throws \Exception Can't initiate regeneration.
	 */
	private function initiate_regeneration_from_tools_page() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! isset( $_REQUEST['_wpnonce'] ) || false === wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {
			throw new \Exception( 'Invalid nonce' );
		}

		$this->check_can_do_table_regeneration();
		$this->data_synchronizer->initiate_regeneration();
	}

	/**
	 * Can the custom orders table regeneration be started?
	 *
	 * @throws \Exception The table regeneration can't be started.
	 */
	private function check_can_do_table_regeneration() {
		if ( ! $this->is_feature_visible() ) {
			throw new \Exception( "Can't do custom orders table regeneration: the feature isn't enabled" );
		}

		if ( $this->data_synchronizer->data_regeneration_is_in_progress() ) {
			throw new \Exception( "Can't do custom orders table regeneration: regeneration is already in progress" );
		}
	}

	/**
	 * Delete the custom orders table and any related options and data.
	 */
	private function delete_custom_orders_table() {
		delete_option( self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION );
		$this->data_synchronizer->delete_custom_orders_table();
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
	 * with entries for managing the custom orders table if appropriate.
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
			'title' => __( 'Custom orders table', 'woocommerce' ),
			'type'  => 'title',
		);

		$settings[] = $title_item;

		$settings[] = array(
			'title'         => __( 'Enable table usage', 'woocommerce' ),
			'desc'          => __( 'Use the custom orders table as the main orders data store.', 'woocommerce' ),
			'id'            => self::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION,
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => 'start',
		);

		$settings[] = array( 'type' => 'sectionend' );

		return $settings;
	}
}
