/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import apiFetch from '@wordpress/api-fetch';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { computeSuggestionMatch } from './utils';
import { AutoCompleter } from './types';

type Customer = {
	id: number;
	name?: string;
	username?: string;
	email?: string;
};

/**
 * Get the name to display for a customer. Customers can be registered without a
 * first or last name, so fall back to the fields that are always set.
 *
 * @param customer Customer as returned by the API.
 * @return The customer's display name.
 */
const getCustomerName = ( customer: Customer ) =>
	customer.name || customer.username || customer.email || '';

/**
 * Get the text of a suggestion. The name is used on its own when it matches the
 * search term, otherwise the username or email that did match is appended so
 * it's clear why the customer is listed.
 *
 * @param customer Customer as returned by the API.
 * @param query    The search term.
 * @return The suggestion text.
 */
const getSuggestion = ( customer: Customer, query: string ) => {
	const name = getCustomerName( customer );
	const search = query.toLocaleLowerCase();

	if ( name.toLocaleLowerCase().includes( search ) ) {
		return name;
	}

	const matched = [ customer.username, customer.email ].find(
		( field ) => field?.toLocaleLowerCase().includes( search )
	);

	if ( ! matched ) {
		return name;
	}

	return sprintf(
		/* translators: 1: Customer name. 2: The customer username or email address that matched the search term. */
		__( '%1$s (%2$s)', 'woocommerce' ),
		name,
		matched
	);
};

/**
 * A customer search across name, username and email, restricted to registered
 * customers for reports that can't match guests (e.g. Downloads, which matches
 * customers to downloads through their user id).
 */
const completer: AutoCompleter = {
	name: 'registered-customers',
	className: 'woocommerce-search__registered-customers-result',
	options( search ) {
		const query = search
			? {
					search,
					searchby: 'all',
					user_type: 'registered',
					per_page: 10,
			  }
			: { user_type: 'registered' };
		return apiFetch( {
			path: addQueryArgs( '/wc-analytics/customers', query ),
		} );
	},
	isDebounced: true,
	getOptionIdentifier( customer ) {
		return customer.id;
	},
	getOptionKeywords( customer ) {
		const fields = [
			customer.name,
			customer.username,
			customer.email,
		].filter( Boolean );
		// The API matches the search term against these fields joined together,
		// so the joined string has to be a keyword too. Without it a term that
		// spans two fields is matched by the API and then filtered back out.
		return [ ...fields, fields.join( ' ' ) ];
	},
	getOptionLabel( customer, query ) {
		const suggestion = getSuggestion( customer, query );

		// A term can match the customer's fields joined together without
		// appearing in any single one (see getOptionKeywords). There is no
		// match to highlight in the suggestion then, so render it plain.
		if (
			! suggestion
				.toLocaleLowerCase()
				.includes( query.toLocaleLowerCase() )
		) {
			return (
				<span
					key="name"
					className="woocommerce-search__result-name"
					aria-label={ suggestion }
				>
					{ decodeEntities( suggestion ) }
				</span>
			);
		}

		const match = computeSuggestionMatch( suggestion, query );
		return (
			<span
				key="name"
				className="woocommerce-search__result-name"
				aria-label={ suggestion }
			>
				{ match?.suggestionBeforeMatch }
				<strong className="components-form-token-field__suggestion-match">
					{ match?.suggestionMatch }
				</strong>
				{ match?.suggestionAfterMatch }
			</span>
		);
	},
	// This is slightly different than gutenberg/Autocomplete, we don't support different methods
	// of replace/insertion, so we can just return the value.
	getOptionCompletion( customer ) {
		return {
			key: customer.id,
			label: getCustomerName( customer ),
		};
	},
};

export default completer;
