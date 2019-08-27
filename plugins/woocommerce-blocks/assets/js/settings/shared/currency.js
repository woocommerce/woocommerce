/**
 * Wrapper for the wcSettings global, which sets defaults if data is missing.
 *
 * Only settings used by blocks are defined here. Component settings are left out.
 */
const currency = wcSettings.currency || {
	code: 'USD',
	precision: 2,
	symbol: '$',
	position: 'left',
	decimal_separator: '.',
	thousand_separator: ',',
	price_format: '%1$s%2$s',
};

export default currency;
