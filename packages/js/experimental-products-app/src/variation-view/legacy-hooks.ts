/**
 * Internal dependencies
 */
import type { LegacyHookMapping } from '../fields/legacy';

/**
 * Maps each legacy variation hook to its insertion point in the DataForm field list.
 *
 * Hook names are the same ones plugins register on via `add_action()`.
 * The `insertAfter` value matches a native field ID; the legacy fields
 * captured from that hook appear directly after that anchor field.
 */
export const VARIATION_LEGACY_HOOK_MAP: LegacyHookMapping = {
	woocommerce_variation_options: { insertAfter: 'product_status' },
	woocommerce_variation_options_pricing: {
		insertAfter: 'date_on_sale_to',
	},
	woocommerce_variation_options_inventory: {
		insertAfter: 'manage_stock',
	},
	woocommerce_variation_options_dimensions: {
		insertAfter: 'shipping_class',
	},
	woocommerce_variation_options_download: {
		insertAfter: 'downloadable',
	},
	woocommerce_product_after_variable_attributes: { insertAt: 'end' },
};
