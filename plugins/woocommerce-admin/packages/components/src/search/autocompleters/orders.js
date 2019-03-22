/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';

/**
 * A orders autocompleter.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'orders',
	className: 'woocommerce-search__order-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				number: search,
				per_page: 10,
			};
			payload = stringifyQuery( query );
			return apiFetch( { path: `/wc/v4/orders${ payload }` } );
		}
	},
	isDebounced: true,
	getOptionKeywords( order ) {
		return [ '#' + order.number ];
	},
	getOptionLabel( order, query ) {
		const match = computeSuggestionMatch( '#' + order.number, query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ '#' + order.number }>
				{ match.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match.suggestionMatch }
				</strong>
				{ match.suggestionAfterMatch }
			</span>,
		];
	},
	getOptionCompletion( order ) {
		return {
			id: order.id,
			label: '#' + order.number,
		};
	},
};
