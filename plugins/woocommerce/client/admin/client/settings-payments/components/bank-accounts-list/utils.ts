/**
 * Determines the default routing field based on the country.
 *
 * @param country The selected country code.
 */
export const getDefaultRoutingField = ( country: string ) => {
	if ( country === 'US' ) return 'routing_number';
	if ( country === 'AU' ) return 'sort_code';
	return 'iban';
};
