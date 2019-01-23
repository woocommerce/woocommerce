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
import { computeSuggestionMatch, getTaxCode } from './utils';

/**
 * A tax completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'taxes',
	className: 'woocommerce-search__tax-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				search,
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: `/wc/v4/taxes${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( tax ) {
		return [ tax.id, getTaxCode( tax ) ];
	},
	getOptionLabel( tax, query ) {
		const match = computeSuggestionMatch( getTaxCode( tax ), query ) || {};
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
			id: tax.id,
			label: getTaxCode( tax ),
		};
		return value;
	},
};
