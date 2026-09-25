/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_hide_out_of_stock_items',
	label: __( 'Hide out of stock items from the catalog', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
