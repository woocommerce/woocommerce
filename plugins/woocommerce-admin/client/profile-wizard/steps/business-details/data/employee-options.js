/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const employeeOptions = [
	{
		key: '1',
		label: __( "It's just me", 'woocommerce' ),
	},
	{
		key: '<10',
		label: __( '< 10', 'woocommerce' ),
	},
	{
		key: '10-50',
		label: '10 - 50',
	},
	{
		key: '50-250',
		label: '50 - 250',
	},
	{
		key: '+250',
		label: __( '+250', 'woocommerce' ),
	},
	{
		key: 'not specified',
		label: __( "I'd rather not say", 'woocommerce' ),
	},
];
