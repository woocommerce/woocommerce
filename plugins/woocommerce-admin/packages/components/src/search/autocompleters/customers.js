/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import { SWAGGERNAMESPACE } from 'store/constants';

const getName = customer => customer.first_name + ' ' + customer.last_name;

/**
 * A customer completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'customers',
	className: 'woocommerce-search__customers-result',
	async options( name ) {
		let payload = '';
		if ( name ) {
			const query = {
				name,
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		// TODO: Use wc endpoint when it's ready
		const response = await fetch( SWAGGERNAMESPACE + 'reports/customers' + payload );
		// const customers = await apiFetch( { path: `/wc/v3/reports/customers${ payload }` } );
		const customers = await response.json();
		const ids = customers.map( customer => customer.id );
		// @TODO load customers names from WC endpoint
		return ids.map( id => ( {
			id,
			first_name: 'Customer ' + id,
			last_name: '',
		} ) );
	},
	isDebounced: true,
	getOptionKeywords( customer ) {
		return [ getName( customer ) ];
	},
	getOptionLabel( customer, query ) {
		const match = computeSuggestionMatch( getName( customer ), query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ getName( customer ) }>
				{ match.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match.suggestionMatch }
				</strong>
				{ match.suggestionAfterMatch }
			</span>,
		];
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( customer ) {
		return {
			id: customer.id,
			label: getName( customer ),
		};
	},
};
