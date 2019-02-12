/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';

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
		let payload = '';
		if ( name ) {
			const query = {
				search: name,
				per_page: 10,
			};
			payload = stringifyQuery( query );
		}
		return apiFetch( { path: `/wc/v4/customers${ payload }` } );
	},
	isDebounced: true,
	getOptionKeywords( customer ) {
		return [ customer.name ];
	},
	getFreeTextOptions( query ) {
		const label = (
			<span key="name" className="woocommerce-search__result-name">
				{ interpolateComponents( {
					mixedString: __( 'All customers with names that include {{query /}}', 'wc-admin' ),
					components: {
						query: <strong className="components-form-token-field__suggestion-match">{ query }</strong>,
					},
				} ) }
			</span>
		);
		const nameOption = {
			key: 'name',
			label: label,
			value: { id: query, name: query },
		};

		return [ nameOption ];
	},
	getOptionLabel( customer, query ) {
		const match = computeSuggestionMatch( customer.name, query ) || {};
		return [
			<span key="name" className="woocommerce-search__result-name" aria-label={ customer.name }>
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
	getOptionCompletion( customer ) {
		return {
			id: customer.id,
			label: customer.name,
		};
	},
};
