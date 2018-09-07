/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import { stringifyQuery } from 'lib/nav-utils';

/**
 * A product categories completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'product_cats',
	className: 'woocommerce-search__product-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				search: encodeURIComponent( search ),
				per_page: 10,
				orderby: 'count',
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: '/wc/v3/products/categories' + payload } );
	},
	isDebounced: true,
	getOptionKeywords( cat ) {
		return [ cat.name ];
	},
	getOptionLabel( cat, query ) {
		const match = computeSuggestionMatch( cat.name, query ) || {};
		// @todo bring back ProductImage, but allow for product category image
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ cat.name }>
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
	getOptionCompletion( cat ) {
		const value = {
			id: cat.id,
			label: cat.name,
		};
		return value;
	},
};
