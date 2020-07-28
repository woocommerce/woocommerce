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
import { computeSuggestionMatch, getTaxCode } from './utils';

/**
 * @typedef {Object} Completer
 * @property
 */

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
		const query = search
			? {
					code: search,
					per_page: 10,
			  }
			: {};
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
		const codeOption = {
			key: 'code',
			label,
			value: { id: query, name: query },
		};

		return [ codeOption ];
	},
	getOptionLabel( tax, query ) {
		const match = computeSuggestionMatch( getTaxCode( tax ), query ) || {};
		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ tax.code }
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
	getOptionCompletion( tax ) {
		const value = {
			key: tax.id,
			label: getTaxCode( tax ),
		};
		return value;
	},
};
