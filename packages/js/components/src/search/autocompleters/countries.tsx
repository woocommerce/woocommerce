/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { createElement, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Country } from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import Flag from '../../flag';
import { AutoCompleter } from './types';

// Cache countries to avoid repeated requests.
let allCountries: Country[] | null = null;

const isCountries = ( value: unknown ): value is Country[] => {
	return (
		Array.isArray( value ) &&
		value.length > 0 &&
		typeof value[ 0 ] === 'object' &&
		typeof value[ 0 ].code === 'string' &&
		typeof value[ 0 ].name === 'string'
	);
};

const completer: AutoCompleter = {
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
				if ( isCountries( result ) ) {
					// Cache the response.
					allCountries = result;
					return allCountries;
				}

				// If the response is not valid, return an empty array.
				// eslint-disable-next-line no-console
				console.warn( 'Invalid countries response', result );
				return [];
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
		const match = computeSuggestionMatch( name, query );

		return (
			<Fragment>
				{ /* @ts-expect-error TODO: migrate Flag component to TS. */ }
				<Flag
					key="thumbnail"
					className="woocommerce-search__result-thumbnail"
					code={ country.code }
					// @ts-expect-error TODO: migrate Flag component.
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
							{ match?.suggestionBeforeMatch }
							<strong className="components-form-token-field__suggestion-match">
								{ match?.suggestionMatch }
							</strong>
							{ match?.suggestionAfterMatch }
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

export default completer;
