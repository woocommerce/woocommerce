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
};
