<?php
/**
 * LookupDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

defined( 'ABSPATH' ) || exit;

/**
 * Data store class for the product attributes lookup table.
 */
class LookupDataStore {

	/**
	 * Insert or update the lookup data for a given product or variation.
	 * If a variable product is passed the information is updated for all of its variations.
	 *
	 * @param int|WC_Product|WC_Product_Variation $product Product or variation object or id.
	 */
	public function update_data_for_product( $product ) {
		// TODO: Implement.
	}
}
