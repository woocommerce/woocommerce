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

/**
 * A customer email completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'emails',
	className: 'woocommerce-search__emails-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				email: search,
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: `/wc/v4/customers${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( customer ) {
		return [ customer.email ];
	},
	getOptionLabel( customer, query ) {
		const match = computeSuggestionMatch( customer.email, query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ customer.email }>
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
			label: customer.email,
		};
	},
};
