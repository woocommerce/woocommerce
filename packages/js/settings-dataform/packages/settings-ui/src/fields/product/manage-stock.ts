/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_manage_stock',
	label: __( 'Enable stock management', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
