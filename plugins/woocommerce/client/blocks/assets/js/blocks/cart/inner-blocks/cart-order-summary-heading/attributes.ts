/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	content: {
		type: 'string',
		default: __( 'Cart totals', 'woocommerce' ),
	},
	lock: {
		type: 'object',
		default: {
			remove: false,
			move: false,
		},
	},
};
