/** @format */
/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';

/**
 * A download IP address autocompleter.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'download-ips',
	className: 'woocommerce-search__download-ip-result',
	options( match ) {
		const query = match ? {
			match,
		} : {};
		return apiFetch( { path: addQueryArgs( '/wc/v4/data/download-ips', query ) } );
	},
	isDebounced: true,
	getOptionKeywords( download ) {
		return [ download.user_ip_address ];
	},
	getOptionLabel( download, query ) {
		const match = computeSuggestionMatch( download.user_ip_address, query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ download.user_ip_address }>
				{ match.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match.suggestionMatch }
				</strong>
				{ match.suggestionAfterMatch }
			</span>,
		];
	},
	getOptionCompletion( download ) {
		return {
			id: download.user_ip_address,
			label: download.user_ip_address,
		};
	},
};
