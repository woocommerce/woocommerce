/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import { AutoCompleter } from './types';

const completer: AutoCompleter = {
	name: 'orders',
	className: 'woocommerce-search__order-result',
	options( search ) {
		const query = search
			? {
					number: search,
					per_page: 10,
			  }
			: {};
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/orders', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( order ) {
		return order.id;
	},
	getOptionKeywords( order ) {
		return [ '#' + order.number ];
	},
	getOptionLabel( order, query ) {
		const match = computeSuggestionMatch( '#' + order.number, query );
		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ '#' + order.number }
			>
				{ match?.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match?.suggestionMatch }
				</strong>
				{ match?.suggestionAfterMatch }
			</span>
		);
	},
	getOptionCompletion( order ) {
		return {
			key: order.id,
			label: '#' + order.number,
		};
	},
};

export default completer;
