/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_shop_page_id',
	label: __( 'Shop page', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		"This sets your shop's base page — where your product archive lives. You can also use it in your product permalinks.",
		'woocommerce'
	),
	Edit: 'select',
	elements: [],
};
