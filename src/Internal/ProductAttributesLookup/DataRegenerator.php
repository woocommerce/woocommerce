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

	/**
	 * The data store to use.
	 *
	 * @var LookupDataStore
	 */
	private $data_store;

	/**
	 * DataRegenerator constructor.
	 */
	public function __construct() {
		add_filter(
			'woocommerce_debug_tools',
			function( $tools ) {
				return $this->add_initiate_regeneration_entry_to_tools_array( $tools );
			},
			1,
			999
		);

		if ( $this->regeneration_is_in_progress() ) {
			add_action(
				'woocommerce_run_product_attribute_lookup_update_callback',
				function () {
					$this->run_regeneration_step_callback();
				}
			);
		}
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
	 * There's no UI to delete the lookup table and the related options, but wp cli can be used
	 * as follows for that purpose:
	 *
	 * wp eval "wc_get_container()->get(Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator::class)->delete_all_attributes_lookup_data();"
	 */
	public function delete_all_attributes_lookup_data() {
		global $wpdb;

		delete_option( 'woocommerce_attribute_lookup__enabled' );
		delete_option( 'woocommerce_attribute_lookup__last_product_id_to_process' );
		delete_option( 'woocommerce_attribute_lookup__last_products_page_processed' );

		$wpdb->query( 'DROP TABLE IF EXISTS wp_wc_product_attributes_lookup' );
	}

	/**
	 * Create the lookup table and initialize the options that will be temporarily used
	 * while the regeneration is in progress.
	 *
	 * @return bool True if there's any product at all in the database, false otherwise.
	 */
	private function initialize_table_and_data() {
		global $wpdb;

		$wpdb->query(
			'
CREATE TABLE wp_wc_product_attributes_lookup (
  product_id bigint(20) NOT NULL,
  product_or_parent_id bigint(20) NOT NULL,
  taxonomy varchar(32) NOT NULL,
  term_id bigint(20) NOT NULL,
  is_variation_attribute tinyint(1) NOT NULL,
  in_stock tinyint(1) NOT NULL
 );
		'
		);

		$last_existing_product_id = current(
			wc_get_products(
				array(
					'return'  => 'ids',
					'limit'   => 1,
					'orderby' => 'id',
					'order'   => 'DESC',
				)
			)
		);
		if ( false === $last_existing_product_id ) {
			// New shop, no products yet, nothing to regenerate.
			return false;
		}

		update_option( 'woocommerce_attribute_lookup__last_product_id_to_process', $last_existing_product_id );
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
		WC()->queue()->schedule_single(
			time() + 1,
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

		$product_ids = wc_get_products(
			array(
				'limit'   => 10,
				'page'    => $current_products_page,
				'orderby' => array(
					'ID' => 'ASC',
				),
				'return'  => 'ids',
			)
		);

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
	 * Add a 'Regenerate product attributes lookup table' entry to the Status - Tools page.
	 *
	 * @param array $tools_array The tool definitions array that is passed ro the woocommerce_debug_tools filter.
	 * @return array The tools array with the entry added.
	 */
	private function add_initiate_regeneration_entry_to_tools_array( $tools_array ) {
		// Regenerate table entry.

		$entry = array(
			'name'             => __( 'Regenerate product attributes lookup table', 'woocommerce' ),
			'desc'             => __( 'This tool will regenerate the product attributes lookup table data. This process may take a while.', 'woocommerce' ),
			'requires_refresh' => true,
			'callback'         => function() {
				$this->initiate_regeneration_from_tools_page();
				return __( 'Product attributes lookup table is regenerating', 'woocommerce' );
			},
		);

		if ( $this->regeneration_is_in_progress() ) {
			$entry['button']   = __( 'Regeneration in progress', 'woocommerce' );
			$entry['disabled'] = true;
		} else {
			$entry['button'] = __( 'Regenerate', 'woocommerce' );
		}

		$tools_array['regenerate_product_attributes_lookup_table'] = $entry;

		// Enable or disable table usage entry.

		if ( 'yes' === get_option( 'woocommerce_attribute_lookup__enabled' ) ) {
			$tools_array['disable_product_attributes_lookup_table_usage'] = array(
				'name'             => __( 'Disable the product attributes lookup table usage', 'woocommerce' ),
				'desc'             => __( 'The product attributes lookup table usage is currently enabled, use this tool to disable it.', 'woocommerce' ),
				'button'           => __( 'Disable', 'woocommerce' ),
				'requires_refresh' => true,
				'callback'         => function() {
					$this->enable_or_disable_lookup_table_usage( false );
					return __( 'Product attributes lookup table usage has been disabled.', 'woocommerce' );
				},
			);
		} elseif ( 'no' === get_option( 'woocommerce_attribute_lookup__enabled' ) ) {
			$tools_array['enable_product_attributes_lookup_table_usage'] = array(
				'name'             => __( 'Enable the product attributes lookup table usage', 'woocommerce' ),
				'desc'             => __( 'The product attributes lookup table usage is currently disabled, use this tool to enable it.', 'woocommerce' ),
				'button'           => __( 'Enable', 'woocommerce' ),
				'requires_refresh' => true,
				'callback'         => function() {
					$this->enable_or_disable_lookup_table_usage( true );
					return __( 'Product attributes lookup table usage has been enabled.', 'woocommerce' );
				},
			);
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
