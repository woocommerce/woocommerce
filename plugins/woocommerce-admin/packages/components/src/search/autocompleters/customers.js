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

/**
 * A raw completer option.
 *
 * @typedef {*} CompleterOption
 */

/**
 * @callback FnGetOptions
 *
 * @return {(CompleterOption[]|Promise.<CompleterOption[]>)} The completer options or a promise for them.
 */

/**
 * @callback FnGetOptionKeywords
 * @param {CompleterOption} option a completer option.
 *
 * @return {string[]} list of key words to search.
 */

/**
 * @callback FnIsOptionDisabled
 * @param {CompleterOption} option a completer option.
 *
 * @return {string[]} whether or not the given option is disabled.
 */

/**
 * @callback FnGetOptionLabel
 * @param {CompleterOption} option a completer option.
 *
 * @return {(string|Array.<(string|Node)>)} list of react components to render.
 */

/**
 * @callback FnAllowContext
 * @param {string} before the string before the auto complete trigger and query.
 * @param {string} after  the string after the autocomplete trigger and query.
 *
 * @return {boolean} true if the completer can handle.
 */

/**
 * @typedef {Object} OptionCompletion
 * @property {'insert-at-caret'|'replace'} action the intended placement of the completion.
 * @property {OptionCompletionValue} value the completion value.
 */

/**
 * A completion value.
 *
 * @typedef {(string|WPElement|Object)} OptionCompletionValue
 */

/**
 * @callback FnGetOptionCompletion
 * @param {CompleterOption} value the value of the completer option.
 * @param {string} query the text value of the autocomplete query.
 *
 * @return {(OptionCompletion|OptionCompletionValue)} the completion for the given option. If an
 * 													   OptionCompletionValue is returned, the
 * 													   completion action defaults to `insert-at-caret`.
 */

/**
 * @typedef {Object} WPCompleter
 * @property {string} name a way to identify a completer, useful for selective overriding.
 * @property {?string} className A class to apply to the popup menu.
 * @property {string} triggerPrefix the prefix that will display the menu.
 * @property {(CompleterOption[]|FnGetOptions)} options the completer options or a function to get them.
 * @property {?FnGetOptionKeywords} getOptionKeywords get the keywords for a given option.
 * @property {?FnIsOptionDisabled} isOptionDisabled get whether or not the given option is disabled.
 * @property {FnGetOptionLabel} getOptionLabel get the label for a given option.
 * @property {?FnAllowContext} allowContext filter the context under which the autocomplete activates.
 * @property {FnGetOptionCompletion} getOptionCompletion get the completion associated with a given option.
 */

/**
 * A customer completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {WPCompleter}
 */
export default {
	name: 'customers',
	className: 'woocommerce-search__customers-result',
	options( name ) {
		const query = name
			? {
					search: name,
					searchby: 'name',
					per_page: 10,
			  }
			: {};
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/customers', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( customer ) {
		return customer.id;
	},
	getOptionKeywords( customer ) {
		return [ customer.name ];
	},
	getFreeTextOptions( query ) {
		const label = (
			<span key="name" className="woocommerce-search__result-name">
				{ interpolateComponents( {
					mixedString: __(
						'All customers with names that include {{query /}}',
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
		const nameOption = {
			key: 'name',
			label,
			value: { id: query, name: query },
		};

		return [ nameOption ];
	},
	getOptionLabel( customer, query ) {
		const match = computeSuggestionMatch( customer.name, query ) || {};
		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ customer.name }
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
	getOptionCompletion( customer ) {
		return {
			key: customer.id,
			label: customer.name,
		};
	},
};
