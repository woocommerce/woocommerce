<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CostOfGoodsSold;

use Automattic\WooCommerce\Enums\FeaturePluginCompatibility;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Main controller for the Cost of Goods Sold feature.
 */
class CostOfGoodsSoldController implements RegisterHooksInterface {

	/**
	 * The instance of FeaturesController to use.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Register hooks.
	 */
	public function register() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'add_debug_tools_entry' ), 999, 1 );
	}

	/**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 */
	final public function init( FeaturesController $features_controller ) {
		$this->features_controller = $features_controller;
	}

	/**
	 * Is the Cost of Goods Sold engine enabled?
	 *
	 * @return bool True if the engine is enabled, false otherwise.
	 */
	public function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( 'cost_of_goods_sold' );
	}

	/**
	 * Add the feature information for the features settings page.
	 *
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_feature_definition( $features_controller ) {
		$definition = array(
			'description'                  => __( 'Allows entering cost of goods sold information for products.', 'woocommerce' ),
			'is_experimental'              => false,
			'enabled_by_default'           => false,
			'default_plugin_compatibility' => FeaturePluginCompatibility::COMPATIBLE,
		);

		$features_controller->add_feature_definition(
			'cost_of_goods_sold',
			__( 'Cost of Goods Sold', 'woocommerce' ),
			$definition
		);
	}

	/**
	 * Add the entry for "add/remove COGS value column to/from the product meta lookup table" to the WooCommerce admin tools.
	 *
	 * @internal Hook handler, not to be explicitly used from outside the class.
	 *
	 * @param array $tools_array Array to add the tool to.
	 * @return array Updated tools array.
	 */
	public function add_debug_tools_entry( array $tools_array ): array {
		// If the feature is disabled we show the tool for removing the column, but not for adding it.
		$column_exists = $this->product_meta_lookup_table_cogs_value_columns_exist();
		if ( ! $this->feature_is_enabled() && ! $column_exists ) {
			return $tools_array;
		}

		$tools_array['generate_cogs_value_meta_column'] = array(
			'name'     => $column_exists ?
				__( 'Remove COGS columns from the product meta lookup table', 'woocommerce' ) :
				__( 'Create COGS columns in the product meta lookup table', 'woocommerce' ),
			'button'   => $column_exists ?
				__( 'Remove columns', 'woocommerce' ) :
				__( 'Create columns', 'woocommerce' ),
			'desc'     =>
				$column_exists ?
				__( 'This tool will remove the Cost of Goods Sold (COGS) related columns from the product meta lookup table. COGS will continue working (if the feature is enabled) but some functionality will not be available.', 'woocommerce' ) :
				__( 'This tool will generate the necessary Cost of Goods Sold (COGS) related columns in the product meta lookup table, and populate them from existing product data.', 'woocommerce' ),
			'callback' =>
				$column_exists ? array( $this, 'remove_lookup_cogs_columns' ) : array( $this, 'generate_lookup_cogs_columns' ),
		);

		return $tools_array;
	}

	/**
	 * Handler for the "add COGS value column to the product meta lookup table" admin tool.
	 *
	 * @internal Tool callback, not to be explicitly used from outside the class.
	 */
	public function generate_lookup_cogs_columns() {
		global $wpdb;

		if ( $this->feature_is_enabled() && ! $this->product_meta_lookup_table_cogs_value_columns_exist() ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_product_meta_lookup ADD COLUMN cogs_total_value DECIMAL(19,4)" );
			$wpdb->query(
				"UPDATE {$wpdb->prefix}wc_product_meta_lookup AS lookup
    			JOIN {$wpdb->prefix}postmeta AS pm ON lookup.product_id = pm.post_id
    			SET lookup.cogs_total_value = CAST(pm.meta_value AS DECIMAL(19, 4))
    			WHERE pm.meta_key = '_cogs_total_value';"
			);
		}
	}

	/**
	 * Handler for the "remove COGS value column to the product meta lookup table" admin tool.
	 *
	 * @internal Tool callback, not to be explicitly used from outside the class.
	 */
	public function remove_lookup_cogs_columns() {
		global $wpdb;

		if ( $this->product_meta_lookup_table_cogs_value_columns_exist() ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_product_meta_lookup DROP COLUMN cogs_total_value" );
		}
	}

	/**
	 * Tells if the COGS value column exists in the product meta lookup table.
	 *
	 * @return bool True if the column exists, false otherwise.
	 */
	public function product_meta_lookup_table_cogs_value_columns_exist(): bool {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$wpdb->prefix}wc_product_meta_lookup LIKE %s",
				'cogs_total_value'
			)
		);
	}

	/**
	 * Get the tooltip text for the COGS value field in the product editor.
	 *
	 * @param bool $for_variable_products True to get the value for variable products, false for other types of products.
	 * @return string The string to use as tooltip (translated but not escaped).
	 */
	public function get_general_cost_edit_field_tooltip( bool $for_variable_products ) {
		return $for_variable_products ?
			__( 'Add the amount it costs you to buy or make this product. This will be applied as the default value for variations.', 'woocommerce' ) :
			__( 'Add the amount it costs you to buy or make this product.', 'woocommerce' );
	}
}
