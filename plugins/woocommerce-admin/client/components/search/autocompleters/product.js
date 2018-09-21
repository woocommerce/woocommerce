/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import ProductImage from 'components/product-image';
import { stringifyQuery } from 'lib/nav-utils';
import { NAMESPACE } from 'store/constants';

/**
 * A products completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'products',
	className: 'woocommerce-search__product-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				search: encodeURIComponent( search ),
				per_page: 10,
				orderby: 'popularity',
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: `${ NAMESPACE }products${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( product ) {
		return [ product.name ];
	},
	getOptionLabel( product, query ) {
		const match = computeSuggestionMatch( product.name, query ) || {};
		return [
			<ProductImage
				key="thumbnail"
				className="woocommerce-search__result-thumbnail"
				product={ product }
				width={ 18 }
				height={ 18 }
				alt=""
			/>,
			<span key="name" className="woocommerce-search__result-name" aria-label={ product.name }>
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
	getOptionCompletion( product ) {
		const value = {
			id: product.id,
			label: product.name,
		};
		return value;
	},
};
