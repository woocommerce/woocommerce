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
import { computeSuggestionMatch, getTaxCode } from './utils';
import { AutoCompleter } from './types';

const completer: AutoCompleter = {
	name: 'taxes',
	className: 'woocommerce-search__tax-result',
	options( search ) {
		// Request the full page (up to the `/wc-analytics/taxes` endpoint's
		// `per_page` maximum) instead of the default of 10. The dropdown has no
		// "load more" control, so a low page size hid most of the configured tax
		// rates from the advanced filter.
		const query = {
			per_page: 100,
			...( search ? { search } : {} ),
		};
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/taxes', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( tax ) {
		return tax.id;
	},
	getOptionKeywords( tax ) {
		return [ tax.id, getTaxCode( tax ) ];
	},
	getFreeTextOptions( query ) {
		const label = (
			<span key="name" className="woocommerce-search__result-name">
				{ interpolateComponents( {
					mixedString: __(
						'All taxes with codes that include {{query /}}',
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
		const codeOption = {
			key: 'code',
			label,
			value: { id: query, name: query },
		};

		return [ codeOption ];
	},
	getOptionLabel( tax, query ) {
		const match = computeSuggestionMatch( getTaxCode( tax ), query );
		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ tax.code }
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
	getOptionCompletion( tax ) {
		const value = {
			key: tax.id,
			label: getTaxCode( tax ),
		};
		return value;
	},
};

export default completer;
