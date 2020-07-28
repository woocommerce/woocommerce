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
 * A customer completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
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
