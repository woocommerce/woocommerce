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
 * A coupon completer.
 * See https://github.com/WordPress/gutenberg/tree/master/packages/components/src/autocomplete#the-completer-interface
 *
 * @type {Completer}
 */
export default {
	name: 'coupons',
	className: 'woocommerce-search__coupon-result',
	options( search ) {
		let payload = '';
		if ( search ) {
			const query = {
				search,
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: `/wc/v4/coupons${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( coupon ) {
		return [ coupon.code ];
	},
	getOptionLabel( coupon, query ) {
		const match = computeSuggestionMatch( coupon.code, query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ coupon.code }>
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
	getOptionCompletion( coupon ) {
		const value = {
			id: coupon.id,
			label: coupon.code,
		};
		return value;
	},
};
