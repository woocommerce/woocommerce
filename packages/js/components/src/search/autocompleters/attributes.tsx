/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import interpolateComponents from '@automattic/interpolate-components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import { AutoCompleter } from './types';

const completer: AutoCompleter = {
	name: 'attributes',
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
			path: addQueryArgs( '/wc-analytics/products/attributes', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( attribute ) {
		return attribute.id;
	},
	getOptionKeywords( attribute ) {
		return [ attribute.name ];
	},
	getFreeTextOptions( query ) {
		const label = (
			<span key="name" className="woocommerce-search__result-name">
				{ interpolateComponents( {
					mixedString: __(
						'All attributes with names that include {{query /}}',
						'woocommerce'
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
		const nameOption = {
			key: 'name',
			label,
			value: { id: query, name: query },
		};

		return [ nameOption ];
	},
	getOptionLabel( attribute, query ) {
		const match = computeSuggestionMatch( attribute.name, query );

		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ attribute.name }
			>
				{ match?.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match?.suggestionMatch }
				</strong>
				{ match?.suggestionAfterMatch }
			</span>
		);
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( attribute ) {
		const value = {
			key: attribute.id,
			label: attribute.name,
		};
		return value;
	},
};

export default completer;
