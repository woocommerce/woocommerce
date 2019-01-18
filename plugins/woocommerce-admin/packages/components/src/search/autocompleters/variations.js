/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import ProductImage from '../../product-image';

/**
 * Create a variation name by concatenating each of the variation's
 * attribute option strings.
 *
 * @param {object} variation - variation returned by the api
 * @returns {string} - variation name
 */
function getVariationName( variation ) {
	return variation.attributes.reduce(
		( desc, attribute, index, arr ) =>
			desc + `${ attribute.option }${ arr.length === index + 1 ? '' : ', ' }`,
		''
	);
}

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
				search,
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		const product = getQuery().products;
		if ( ! product || product.includes( ',' ) ) {
			console.warn( 'Invalid product id supplied to Variations autocompleter' );
		}
		return apiFetch( { path: `/wc/v4/products/${ product }/variations${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( variation ) {
		return [ getVariationName( variation ) ];
	},
	getOptionLabel( variation, query ) {
		const match = computeSuggestionMatch( getVariationName( variation ), query ) || {};
		return [
			<ProductImage
				key="thumbnail"
				className="woocommerce-search__result-thumbnail"
				product={ variation }
				width={ 18 }
				height={ 18 }
				alt=""
			/>,
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ variation.description }
			>
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
	getOptionCompletion( variation ) {
		return {
			id: variation.id,
			label: getVariationName( variation ),
		};
	},
};
