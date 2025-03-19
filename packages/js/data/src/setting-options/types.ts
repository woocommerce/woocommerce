export type SettingValue = string | number | boolean | null | string[];

export type SettingType =
	| 'text'
	| 'password'
	| 'title'
	| 'multi_select_countries'
	| 'color'
	| 'datetime'
	| 'datetime-local'
	| 'date'
	| 'month'
	| 'time'
	| 'week'
	| 'number'
	| 'email'
	| 'url'
	| 'tel'
	| 'select'
	| 'radio'
	| 'multiselect'
	| 'checkbox'
	| 'relative_date_selector'
	| 'textarea'
	| 'sectionend'
	| 'single_select_page'
	| 'single_select_page_with_search'
	| 'single_select_country'
	| 'slotfill_placeholder';

export type SettingsGroup = {
	id: string;
	label: string;
	description: string;
	parent_id: string;
	sub_groups: string[];
	_links?: {
		options: Array< {
			href: string;
		} >;
	};
};

export type Setting = {
	id: string;
	label: string;
	description: string;
	type: SettingType;
	value: SettingValue;
	default?: SettingValue;
	options?: Record< string, string >;
	tip?: string;
	placeholder?: string;
};

export type APIError = {
	code: string;
	message: string;
	data?: Record< string, unknown >;
};

export type BatchSettingsError = Error & {
	settingErrors: Array< {
		id: string;
		error: APIError;
	} >;
};

export type SettingsState = {
	groups: SettingsGroup[];
	settings: {
		[ groupId: string ]: {
			[ settingId: string ]: Setting;
		};
	};
	edits: {
		[ groupId: string ]: {
			[ settingId: string ]: SettingValue;
		};
	};
	isSaving: {
		groups: {
			[ groupId: string ]: boolean;
		};
		settings: {
			[ groupId: string ]: {
				[ settingId: string ]: boolean;
			};
		};
	};
	errors: {
		[ groupId: string ]: {
			[ settingId: string ]: unknown;
		};
	};
};

export type SettingUpdate = {
	id: string;
	value: SettingValue;
};

export type SettingsUpdateObject = Record< string, SettingValue >;
