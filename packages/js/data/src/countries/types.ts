export type SettingProperties = {
	label?: string;
	label_class?: string[];
	placeholder?: string;
	class?: string[];
	autocomplete?: string;
	priority?: number;
	required?: boolean;
	type?: string;
	hidden?: boolean;
};

export type Locale = {
	address_1?: SettingProperties;
	address_2?: SettingProperties;
	city?: SettingProperties;
	company?: SettingProperties;
	first_name?: SettingProperties;
	last_name?: SettingProperties;
	postcode?: SettingProperties;
	state?: SettingProperties;
};

export type Country = {
	code: string;
	name: string;
	states: Array< {
		code: string;
		name: string;
	} >;
};

export type Locales = {
	[ key: string ]: Locale;
};

export type CountriesState = {
	errors: {
		[ key: string ]: unknown;
	};
	locales: Locales;
	countries: Country[];
	geolocation: GeolocationResponse | undefined;
};

/**
 * Geolocation response from the WPCOM API which geolocates using ip2location.
 * Example response:
 * {
 *   "latitude":"-38.23476",
 *   "longitude":"146.39499",
 *   "country_short":"AU",
 *   "country_long":"Australia",
 *   "region":"Victoria",
 *   "city":"Morwell"
 * }
 */
export type GeolocationResponse = {
	latitude: string;
	longitude: string;
	country_short: string;
	country_long: string;
	region: string;
	city: string;
};
