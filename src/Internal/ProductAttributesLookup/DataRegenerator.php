<?php
/**
 * DataRegenerator class file.
 */

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the (re)generation of the product attributes lookup table.
 * It schedules the regeneration in small product batches by itself, so it can be used outside the
 * regular WooCommerce data regenerations mechanism.
 *
 * After the regeneration is completed a wp_wc_product_attributes_lookup table will exist with entries for
 * all the products that existed when initiate_regeneration was invoked; entries for products created after that
 * are supposed to be created/updated by the appropriate data store classes (or by the code that uses
 * the data store classes) whenever a product is created/updated.
 *
 * Additionally, after the regeneration is completed a 'woocommerce_attribute_lookup__enabled' option
 * with a value of 'no' will have been created.
 *
 * This class also adds two entries to the Status - Tools menu: one for manually regenerating the table contents,
 * and another one for enabling or disabling the actual lookup table usage.
 */
class DataRegenerator {

	const PRODUCTS_PER_GENERATION_STEP = 10;

	/**
	 * The data store to use.
	 *
	 * @var LookupDataStore
	 */
	private $data_store;

	/**
	 * The lookup table name.
	 *
	 * @var string
	 */
	private $lookup_table_name;

	/**
	 * DataRegenerator constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->lookup_table_name = $wpdb->prefix . 'wc_product_attributes_lookup';

		add_filter(
			'woocommerce_debug_tools',
			function( $tools ) {
				return $this->add_initiate_regeneration_entry_to_tools_array( $tools );
			},
			1,
			999
		);

		add_action(
			'woocommerce_run_product_attribute_lookup_update_callback',
			function () {
				$this->run_regeneration_step_callback();
			}
		);
	}

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param LookupDataStore $data_store The data store to use.
	 */
	final public function init( LookupDataStore $data_store ) {
		$this->data_store = $data_store;
	}

	/**
	 * Initialize the regeneration procedure:
	 * deletes the lookup table and related options if they exist,
	 * then it creates the table and runs the first step of the regeneration process.
	 *
	 * This is the method that should be used as a callback for a data regeneration in wc-update-functions, e.g.:
	 *
	 * function wc_update_XX_regenerate_product_attributes_lookup_table() {
	 *   wc_get_container()->get(DataRegenerator::class)->initiate_regeneration();
	 *   return false;
	 * }
	 *
	 * (Note how we are returning "false" since the class handles the step scheduling by itself).
	 */
	public function initiate_regeneration() {
		$this->delete_all_attributes_lookup_data();
		$products_exist = $this->initialize_table_and_data();
		if ( $products_exist ) {
			$this->enqueue_regeneration_step_run();
		} else {
			$this->finalize_regeneration();
		}
	}

	/**
	 * Tells if a regeneration is already in progress.
	 *
	 * @return bool True if a regeneration is already in progress.
	 */
	public function regeneration_is_in_progress() {
		return ! is_null( get_option( 'woocommerce_attribute_lookup__last_products_page_processed', null ) );
	}

	/**
	 * Delete all the existing data related to the lookup table, including the table itself.
	 *
	 * Shortcut to run this method in case the debug tools UI isn't available or for quick debugging:
	 *
	 * wp eval "wc_get_container()->get(Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator::class)->delete_all_attributes_lookup_data();"
	 */
	public function delete_all_attributes_lookup_data() {
		global $wpdb;

		delete_option( 'woocommerce_attribute_lookup__enabled' );
		delete_option( 'woocommerce_attribute_lookup__last_product_id_to_process' );
		delete_option( 'woocommerce_attribute_lookup__last_products_page_processed' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $this->lookup_table_name );
	}

	/**
	 * Create the lookup table and initialize the options that will be temporarily used
	 * while the regeneration is in progress.
	 *
	 * @return bool True if there's any product at all in the database, false otherwise.
	 */
	private function initialize_table_and_data() {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			'
CREATE TABLE ' . $this->lookup_table_name . '(
  product_id bigint(20) NOT NULL,
  product_or_parent_id bigint(20) NOT NULL,
  taxonomy varchar(32) NOT NULL,
  term_id bigint(20) NOT NULL,
  is_variation_attribute tinyint(1) NOT NULL,
  in_stock tinyint(1) NOT NULL
 );
		'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$last_existing_product_id =
			WC()->call_function(
				'wc_get_products',
				array(
					'return'  => 'ids',
					'limit'   => 1,
					'orderby' => array(
						'ID' => 'DESC',
					),
				)
			);

		if ( ! $last_existing_product_id ) {
			// No products exist, nothing to (re)generate.
			return false;
		}

		update_option( 'woocommerce_attribute_lookup__last_product_id_to_process', current( $last_existing_product_id ) );
		update_option( 'woocommerce_attribute_lookup__last_products_page_processed', 0 );

		return true;
	}

	/**
	 * Action scheduler callback, performs one regeneration step and then
	 * schedules the next step if necessary.
	 */
	private function run_regeneration_step_callback() {
		if ( ! $this->regeneration_is_in_progress() ) {
			return;
		}

		$result = $this->do_regeneration_step();
		if ( $result ) {
			$this->enqueue_regeneration_step_run();
		} else {
			$this->finalize_regeneration();
		}
	}

	/**
	 * Enqueue one regeneration step in action scheduler.
	 */
	private function enqueue_regeneration_step_run() {
		$queue = WC()->get_instance_of( \WC_Queue::class );
		$queue->schedule_single(
			WC()->call_function( 'time' ) + 1,
			'woocommerce_run_product_attribute_lookup_update_callback',
			array(),
			'woocommerce-db-updates'
		);
	}

	/**
	 * Perform one regeneration step: grabs a chunk of products and creates
	 * the appropriate entries for them in the lookup table.
	 *
	 * @return bool True if more steps need to be run, false otherwise.
	 */
	private function do_regeneration_step() {
		$last_products_page_processed = get_option( 'woocommerce_attribute_lookup__last_products_page_processed' );
		$current_products_page        = (int) $last_products_page_processed + 1;

		$product_ids = WC()->call_function(
			'wc_get_products',
			array(
				'limit'   => self::PRODUCTS_PER_GENERATION_STEP,
				'page'    => $current_products_page,
				'orderby' => array(
					'ID' => 'ASC',
				),
				'return'  => 'ids',
			)
		);

		if ( ! $product_ids ) {
			return false;
		}

		foreach ( $product_ids as $id ) {
			$this->data_store->update_data_for_product( $id );
		}

		update_option( 'woocommerce_attribute_lookup__last_products_page_processed', $current_products_page );

		$last_product_id_to_process = get_option( 'woocommerce_attribute_lookup__last_product_id_to_process' );
		return end( $product_ids ) < $last_product_id_to_process;
	}

	/**
	 * Cleanup/final option setup after the regeneration has been completed.
	 */
	private function finalize_regeneration() {
		delete_option( 'woocommerce_attribute_lookup__last_product_id_to_process' );
		delete_option( 'woocommerce_attribute_lookup__last_products_page_processed' );
		update_option( 'woocommerce_attribute_lookup__enabled', 'no' );
	}

	/**
	 * Check if the lookup table exists in the database.
	 *
	 * @return bool True if the lookup table exists in the database.
	 */
	private function lookup_table_exists() {
		global $wpdb;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $this->lookup_table_name ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->lookup_table_name === $wpdb->get_var( $query );
	}

	/**
	 * Add a 'Regenerate product attributes lookup table' entry to the Status - Tools page.
	 *
	 * @param array $tools_array The tool definitions array that is passed ro the woocommerce_debug_tools filter.
	 * @return array The tools array with the entry added.
	 */
	private function add_initiate_regeneration_entry_to_tools_array( array $tools_array ) {
		if ( ! $this->data_store->is_feature_visible() ) {
			return $tools_array;
		}

		$lookup_table_exists       = $this->lookup_table_exists();
		$generation_is_in_progress = $this->regeneration_is_in_progress();

		// Regenerate table.

		if ( $lookup_table_exists ) {
			$generate_item_name   = __( 'Regenerate the product attributes lookup table', 'woocommerce' );
			$generate_item_desc   = __( 'This tool will regenerate the product attributes lookup table data from existing products data. This process may take a while.', 'woocommerce' );
			$generate_item_return = __( 'Product attributes lookup table data is regenerating', 'woocommerce' );
			$generate_item_button = __( 'Regenerate', 'woocommerce' );
		} else {
			$generate_item_name   = __( 'Create and fill product attributes lookup table', 'woocommerce' );
			$generate_item_desc   = __( 'This tool will create the product attributes lookup table data and fill it with existing products data. This process may take a while.', 'woocommerce' );
			$generate_item_return = __( 'Product attributes lookup table is being filled', 'woocommerce' );
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
				/* translators: %d: How many products have been processed so far. */
				__( 'Filling in progress (%d)', 'woocommerce' ),
				get_option( 'woocommerce_attribute_lookup__last_products_page_processed', 0 ) * self::PRODUCTS_PER_GENERATION_STEP
			);
			$entry['disabled'] = true;
		} else {
			$entry['button'] = $generate_item_button;
		}

		$tools_array['regenerate_product_attributes_lookup_table'] = $entry;

		if ( $lookup_table_exists ) {

			// Delete the table.

			$tools_array['delete_product_attributes_lookup_table'] = array(
				'name'             => __( 'Delete the product attributes lookup table', 'woocommerce' ),
				'desc'             => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This will delete the product attributes lookup table. You can create it again with the "Create and fill product attributes lookup table" tool.', 'woocommerce' )
				),
				'button'           => __( 'Delete', 'woocommerce' ),
				'requires_refresh' => true,
				'callback'         => function () {
					$this->delete_all_attributes_lookup_data();
					return __( 'Product attributes lookup table has been deleted.', 'woocommerce' );
				},
			);
		}

		if ( $lookup_table_exists && ! $generation_is_in_progress ) {

			// Enable or disable table usage.

			if ( 'yes' === get_option( 'woocommerce_attribute_lookup__enabled' ) ) {
				$tools_array['disable_product_attributes_lookup_table_usage'] = array(
					'name'             => __( 'Disable the product attributes lookup table usage', 'woocommerce' ),
					'desc'             => __( 'The product attributes lookup table usage is currently enabled, use this tool to disable it.', 'woocommerce' ),
					'button'           => __( 'Disable', 'woocommerce' ),
					'requires_refresh' => true,
					'callback'         => function () {
						$this->enable_or_disable_lookup_table_usage( false );
						return __( 'Product attributes lookup table usage has been disabled.', 'woocommerce' );
					},
				);
			} else {
				$tools_array['enable_product_attributes_lookup_table_usage'] = array(
					'name'             => __( 'Enable the product attributes lookup table usage', 'woocommerce' ),
					'desc'             => __( 'The product attributes lookup table usage is currently disabled, use this tool to enable it.', 'woocommerce' ),
					'button'           => __( 'Enable', 'woocommerce' ),
					'requires_refresh' => true,
					'callback'         => function () {
						$this->enable_or_disable_lookup_table_usage( true );
						return __( 'Product attributes lookup table usage has been enabled.', 'woocommerce' );
					},
				);
			}
		}

		return $tools_array;
	}

	/**
	 * Callback to initiate the regeneration process from the Status - Tools page.
	 *
	 * @throws \Exception The regeneration is already in progress.
	 */
	private function initiate_regeneration_from_tools_page() {
		if ( $this->regeneration_is_in_progress() ) {
			throw new \Exception( 'Product attributes lookup table is already regenerating.' );
		}

		$this->initiate_regeneration();
	}

	/**
	 * Enable or disable the actual lookup table usage.
	 *
	 * @param bool $enable True to enable, false to disable.
	 * @throws \Exception A lookup table regeneration is currently in progress.
	 */
	private function enable_or_disable_lookup_table_usage( $enable ) {
		if ( $this->regeneration_is_in_progress() ) {
			throw new \Exception( "Can't enable or disable the attributes lookup table usage while it's regenerating." );
		}

		update_option( 'woocommerce_attribute_lookup__enabled', $enable ? 'yes' : 'no' );
	}
}
