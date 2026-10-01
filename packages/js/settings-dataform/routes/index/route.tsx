/**
 * External dependencies
 */
import { redirect } from '@wordpress/route';

export const route = {
	beforeLoad: () => {
		throw redirect( {
			to: '/settings/$page',
			params: { page: 'products' },
			replace: true,
		} );
	},
};
