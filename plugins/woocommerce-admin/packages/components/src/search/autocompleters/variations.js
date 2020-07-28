/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Fragment } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import ProductImage from '../../product-image';

/**
 * @typedef {Object} Completer
 * @property
 */

/**
 * Create a variation name by concatenating each of the variation's
 * attribute option strings.
 *
 * @param {Object} variation - variation returned by the api
 * @return {string} - variation name
 */
function getVariationName( variation ) {
	return variation.attributes.reduce(
		( desc, attribute, index, arr ) =>
			desc +
			`${ attribute.option }${ arr.length === index + 1 ? '' : ', ' }`,
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
		const query = search
			? {
					search,
					per_page: 10,
			  }
			: {};
		const product = getQuery().products;
		if ( ! product || product.includes( ',' ) ) {
			// eslint-disable-next-line no-console
			console.warn(
				'Invalid product id supplied to Variations autocompleter'
			);
		}
		return apiFetch( {
			path: addQueryArgs(
				`/wc-analytics/products/${ product }/variations`,
				query
			),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( variation ) {
		return variation.id;
	},
	getOptionKeywords( variation ) {
		return [ getVariationName( variation ), variation.sku ];
	},
	getOptionLabel( variation, query ) {
		const match =
			computeSuggestionMatch( getVariationName( variation ), query ) ||
			{};
		return (
			<Fragment>
				<ProductImage
					key="thumbnail"
					className="woocommerce-search__result-thumbnail"
					product={ variation }
					width={ 18 }
					height={ 18 }
					alt=""
				/>
				,
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
				</span>
				,
			</Fragment>
		);
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( variation ) {
		return {
			key: variation.id,
			label: getVariationName( variation ),
		};
	},
};
