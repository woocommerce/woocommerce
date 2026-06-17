const WCPAY_NATIVE_ONBOARDING_PATH =
	'admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding';

export function getWcpayNativeOnboardingUrl(
	from = 'WCADMIN_PAYMENT_TASK'
) {
	return `${ WCPAY_NATIVE_ONBOARDING_PATH }&from=${ encodeURIComponent(
		from
	) }`;
}

export function connectWcpay() {
	window.location.href = getWcpayNativeOnboardingUrl();
}

export function installActivateAndConnectWcpay(
	_reject,
	_createNotice,
	_installAndActivatePlugins
) {
	connectWcpay();
}

export function isWCPaySupported( countryCode ) {
	const supportedCountries = [
		'US',
		'PR',
		'AU',
		'CA',
		'DE',
		'ES',
		'FR',
		'GB',
		'IE',
		'IT',
		'NZ',
		'AT',
		'BE',
		'NL',
		'PL',
		'PT',
		'CH',
		'HK',
		'SG',
		'CY',
		'DK',
		'EE',
		'FI',
		'GR',
		'LU',
		'LT',
		'LV',
		'NO',
		'MT',
		'SI',
		'SK',
		'BG',
		'CZ',
		'HR',
		'HU',
		'RO',
		'SE',
		'JP',
		'AE',
	];

	return supportedCountries.includes( countryCode );
}
