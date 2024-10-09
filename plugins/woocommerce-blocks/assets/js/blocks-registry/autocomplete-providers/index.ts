/**
 * External dependencies
 */
import { CoreAddressForm, getSetting } from '@woocommerce/settings';

let provider: AutocompleteProviderRegistrationOptions | null = null;

interface SearchResult {
	label: string;
	value: string;
	metadata: Record< string, unknown >;
}

type AutocompleteResult = Record< keyof CoreAddressForm, string >;

interface AutocompleteProviderRegistrationOptions {
	search: ( search: string ) => SearchResult[];
	getAddress: ( result: SearchResult ) => AutocompleteResult;
	fields: ( keyof CoreAddressForm )[];
	id: string;
}

export const getAddressSuggestionProvider = () => {
	if ( provider ) {
		return provider;
	}

	const serverProvider = getSetting( 'hasAddressSuggestion', false );
	if ( serverProvider === false ) {
		return null;
	}
	return {
		id: 'server',
		fields: serverProvider,
		search: ( search: string ) => {
			console.log( 'searching via store api', search );
			return [
				{
					id: 1,
					label: '5 King Street, London',
				},
				{
					id: 2,
					label: '10 Queen Street, London',
				},
				{
					id: 3,
					label: '15 Prince Street, London',
				},
				{
					id: 4,
					label: '20 Duke Street, London',
				},
				{
					id: 5,
					label: '25 Earl Street, London',
				},
			];
		},
		getAddress: ( result: SearchResult ) => {
			console.log( { result } );
			console.log( 'getting address via store api', result );
			const results = {
				1: {
					address_1: '5 King Street',
					city: 'London',
					postcode: 'SW1 0NQ',
					state: 'Buckinghamshire',
					country: 'GB',
				},
				2: {
					address_1: '10 Queen Street',
					city: 'London',
					postcode: 'SW1 0NQ',
					state: 'Buckinghamshire',
					country: 'GB',
				},
				3: {
					address_1: '15 Prince Street',
					city: 'London',
					postcode: 'SW1 0NQ',
					state: 'Buckinghamshire',
					country: 'GB',
				},
				4: {
					address_1: '20 Duke Street',
					city: 'London',
					postcode: 'SW1 0NQ',
					state: 'Buckinghamshire',
					country: 'GB',
				},
				5: {
					address_1: '25 Earl Street',
					city: 'London',
					postcode: 'SW1 0NQ',
					state: 'Buckinghamshire',
					country: 'GB',
				},
			};
			return results[ result.id ];
		},
	};
};

export const registerAutocompleteProvider = ( {
	options,
}: {
	options: AutocompleteProviderRegistrationOptions;
} ) => {
	// check if options has search function
	if ( ! options.search ) {
		throw new Error( 'search function is required' );
	}

	// check if options has getAddress function
	if ( ! options.getAddress ) {
		throw new Error( 'getAddress function is required' );
	}

	// check if options has fields
	if ( ! options.fields ) {
		throw new Error( 'fields are required' );
	}

	// make sure fields don't include address_1
	if ( options.fields.includes( 'address_1' ) ) {
		throw new Error(
			'Address field cannot be hidden as its used for search.'
		);
	}
	provider = options;
};
