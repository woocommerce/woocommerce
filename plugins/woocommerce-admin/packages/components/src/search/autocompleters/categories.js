/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import interpolateComponents from 'interpolate-components';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';

/**
 * @typedef {Object} Completer
 * @property
 */

/**
 * A product categories completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'categories',
	className: 'woocommerce-search__product-result',
	options( search ) {
		const query = search
			? {
					search,
					per_page: 10,
					orderby: 'count',
			  }
			: {};
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/products/categories', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( category ) {
		return category.id;
	},
	getOptionKeywords( cat ) {
		return [ cat.name ];
	},
	getFreeTextOptions( query ) {
		const label = (
			<span key="name" className="woocommerce-search__result-name">
				{ interpolateComponents( {
					mixedString: __(
						'All categories with titles that include {{query /}}',
						'woocommerce-admin'
					),
					components: {
						query: (
							<strong className="components-form-token-field__suggestion-match">
								{ query }
							</strong>
						),
					},
				} ) }
			</span>
		);
		const titleOption = {
			key: 'title',
			label,
			value: { id: query, name: query },
		};

		return [ titleOption ];
	},
	getOptionLabel( cat, query ) {
		const match = computeSuggestionMatch( cat.name, query ) || {};
		// @todo Bring back ProductImage, but allow for product category image
		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ cat.name }
			>
				{ match.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match.suggestionMatch }
				</strong>
				{ match.suggestionAfterMatch }
			</span>
		);
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( cat ) {
		const value = {
			key: cat.id,
			label: cat.name,
		};
		return value;
	},
};
