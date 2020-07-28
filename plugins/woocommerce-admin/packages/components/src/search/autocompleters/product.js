/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { Fragment } from '@wordpress/element';
import interpolateComponents from 'interpolate-components';

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
					orderby: 'popularity',
			  }
			: {};
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/products', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( product ) {
		return product.id;
	},
	getOptionKeywords( product ) {
		return [ product.name, product.sku ];
	},
	getFreeTextOptions( query ) {
		const label = (
			<span key="name" className="woocommerce-search__result-name">
				{ interpolateComponents( {
					mixedString: __(
						'All products with titles that include {{query /}}',
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
	getOptionLabel( product, query ) {
		const match = computeSuggestionMatch( product.name, query ) || {};
		return (
			<Fragment>
				<ProductImage
					key="thumbnail"
					className="woocommerce-search__result-thumbnail"
					product={ product }
					width={ 18 }
					height={ 18 }
					alt=""
				/>
				<span
					key="name"
					className="woocommerce-search__result-name"
					aria-label={ product.name }
				>
					{ match.suggestionBeforeMatch }
					<strong className="components-form-token-field__suggestion-match">
						{ match.suggestionMatch }
					</strong>
					{ match.suggestionAfterMatch }
				</span>
			</Fragment>
		);
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( product ) {
		const value = {
			key: product.id,
			label: product.name,
		};
		return value;
	},
};
