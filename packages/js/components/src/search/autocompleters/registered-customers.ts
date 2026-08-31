/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import customers from './customers';
import { AutoCompleter } from './types';

/**
 * The `customers` completer restricted to registered customers, for reports
 * that can't match guests (e.g. Downloads, which matches by user id).
 */
const completer: AutoCompleter = {
	...customers,
	name: 'registered-customers',
	className: 'woocommerce-search__registered-customers-result',
	options( search ) {
		const query = search
			? {
					search,
					searchby: 'all',
					user_type: 'registered',
					per_page: 10,
			  }
			: { user_type: 'registered' };
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/customers', query ),
		} );
	},
};

export default completer;
