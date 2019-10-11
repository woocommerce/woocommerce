/** @format */

/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import Flag from '../../flag';

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
		const { countries } = getSetting(
			'dataEndpoints',
			{ countries: undefined }
		);
		return countries || [];
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
		return [
			<Flag
				key="thumbnail"
				className="woocommerce-search__result-thumbnail"
				code={ country.code }
				size={ 18 }
				hideFromScreenReader
			/>,
			<span key="name" className="woocommerce-search__result-name" aria-label={ name }>
				{ match.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match.suggestionMatch }
				</strong>
				{ match.suggestionAfterMatch }
			</span>,
		];
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( country ) {
		const value = {
			id: country.code,
			label: decodeEntities( country.name ),
		};
		return value;
	},
};
