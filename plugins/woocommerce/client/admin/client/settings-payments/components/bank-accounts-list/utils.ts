export const getDefaultRoutingField = ( country: string ) => {
	if ( country === 'US' ) return 'routing_number';
	if ( country === 'AU' ) return 'sort_code';
	return 'iban';
};
