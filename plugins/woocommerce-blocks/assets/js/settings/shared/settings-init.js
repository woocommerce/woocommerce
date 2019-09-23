const defaults = {
	adminUrl: '',
	countries: [],
	currency: {
		code: 'USD',
		precision: 2,
		symbol: '$',
		symbolPosition: 'left',
		decimalSeparator: '.',
		priceFormat: '%1$s%2$s',
		thousandSeparator: ',',
	},
	defaultDateRange: 'period=month&compare=previous_year',
	locale: {
		siteLocale: 'en_US',
		userLocale: 'en_US',
		weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	},
	orderStatuses: [],
	siteTitle: '',
	wcAssetUrl: '',
};

const globalSharedSettings = typeof wcSettings === 'object' ? wcSettings : {};

// Use defaults or global settings, depending on what is set.
const allSettings = {
	...defaults,
	...globalSharedSettings,
};

allSettings.currency = {
	...defaults.currency,
	...allSettings.currency,
};

allSettings.locale = {
	...defaults.locale,
	...allSettings.locale,
};

export { allSettings };
