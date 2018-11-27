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
 * A tax completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'taxes$',
	className: 'woocommerce-search__tax-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				search: encodeURIComponent( search ),
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: `/wc/v3/taxes${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( tax ) {
		return [ tax.code ];
	},
	getOptionLabel( tax, query ) {
		const match = computeSuggestionMatch( tax.code, query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ tax.code }>
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
	getOptionCompletion( tax ) {
		const value = {
			id: tax.tax_rate_id,
			label: tax.code,
		};
		return value;
	},
};
