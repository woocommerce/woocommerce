/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';

/**
 * Get the data for a tab click.
 *
 * @param {string}  tabId   Clicked tab.
 * @param {Product} product Current product.
 * @return {Object} The data for the event.
 */
export function getTabTracksData( tabId: string, product: Product ) {
	const data = {
		product_tab: tabId,
		product_type: product.type,
		source: TRACKS_SOURCE,
	};

	if ( tabId === 'inventory' ) {
		return {
			...data,
			is_store_stock_management_enabled: product.manage_stock,
		};
	}

	return data;
}
