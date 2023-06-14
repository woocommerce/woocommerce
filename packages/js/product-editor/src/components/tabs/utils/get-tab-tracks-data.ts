/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

/**
 * Get the data for a tab click.
 * @param {string} tabId Clicked tab.
 * @param {product} Product Current product. 
 * @returns {object} The data for the event.
 */
export function getTabTracksData( tabId: string, product: Product ) {
    const data = {
        product_tab: tabId,
        product_type: product.type,
        source: 'product-block-editor-v1',
    }

    if ( tabId === 'inventory' ) {
        return {
            ...data,
            is_store_stock_management_enabled: product.manage_stock,
        }
    }

    return data;
}