/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import Flag from '../../flag';

/**
 * @typedef {Object} Completer
 * @property
 */

// Cache countries to avoid repeated requests.
let allCountries = null;

/**
 * A country completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'countries',
	className: 'woocommerce-search__country-result',
	isDebounced: true,
	options() {
		// Returned cached countries if we've already received them.
		if ( allCountries ) {
			return Promise.resolve( allCountries );
		}
		// Make the request for country data.
		return apiFetch( { path: '/wc-analytics/data/countries' } ).then(
			( result ) => {
				// Cache the response.
				allCountries = result;
				return allCountries;
			}
		);
	},
	getOptionIdentifier( country ) {
		return country.code;
	},
	getSearchExpression( query ) {
		return '^' + query;
	},
	getOptionKeywords( country ) {
		return [ country.code, decodeEntities( country.name ) ];
	},
	getOptionLabel( country, query ) {
		const name = decodeEntities( country.name );
		const match = computeSuggestionMatch( name, query ) || {};

		return (
			<Fragment>
				<Flag
					key="thumbnail"
					className="woocommerce-search__result-thumbnail"
					code={ country.code }
					size={ 18 }
					hideFromScreenReader
				/>
				<span
					key="name"
					className="woocommerce-search__result-name"
					aria-label={ name }
				>
					{ query ? (
						<Fragment>
							{ match.suggestionBeforeMatch }
							<strong className="components-form-token-field__suggestion-match">
								{ match.suggestionMatch }
							</strong>
							{ match.suggestionAfterMatch }
						</Fragment>
					) : (
						name
					) }
				</span>
			</Fragment>
		);
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( country ) {
		const value = {
			key: country.code,
			label: decodeEntities( country.name ),
		};
		return value;
	},
};
