export function isWCPaySupported( countryCode ) {
	const supportedCountries = [ 'US', 'PR' ];
	if (
		window.wcAdminFeatures &&
		window.wcAdminFeatures[ 'wcpay/support-international-countries' ]
	) {
		supportedCountries.push(
			'AU',
			'CA',
			'DE',
			'ES',
			'FR',
			'GB',
			'IE',
			'IT',
			'NZ'
		);
	}
	return supportedCountries.includes( countryCode );
}
