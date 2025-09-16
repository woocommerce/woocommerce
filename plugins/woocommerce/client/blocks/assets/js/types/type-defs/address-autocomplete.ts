/**
 * External dependencies
 */
import type { AddressFormValues, FormType } from '@woocommerce/settings';

// Declare window
declare global {
	interface Window {
		wc: {
			addressAutocomplete: {
				providers: Record< string, ClientAddressAutocompleteProvider >;
				activeProvider: {
					[ key in FormType ]?: ClientAddressAutocompleteProvider;
				};
				registerAddressAutocompleteProvider: (
					provider: ClientAddressAutocompleteProvider
				) => boolean;
			};
		};
	}
}

export interface ServerAddressAutocompleteProvider {
	name: string;
	id: string;
	branding_html: string;
}

export interface AddressAutocompleteResult {
	label: string;
	id: string;
	matchedSubstrings: { length: number; offset: number }[];
}

export interface ClientAddressAutocompleteProvider {
	id: string;
	canSearch: ( country: string ) => boolean;
	search: (
		inputValue: string,
		country: string
	) => Promise< AddressAutocompleteResult[] >;
	select: (
		addressId: string,
		country: string
	) => Promise<
		Omit<
			AddressFormValues,
			'first_name' | 'last_name' | 'company' | 'phone'
		>
	>;
}
