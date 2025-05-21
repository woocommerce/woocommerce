/**
 * Represents the value of a setting. It's unknown because any value can be stored.
 */
export type SettingValue = unknown;

/**
 * Represents the type of a setting
 */
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

/**
 * Represents a setting group
 */
export type SettingsGroup = {
	id: string;
	label: string;
	description: string;
	parent_id?: string;
	sub_groups: string[];
	_links?: {
		options: Array< {
			href: string;
		} >;
	};
};

/**
 * Represents a setting
 */
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

/**
 * Represents an error that occurred during a batch settings update
 */
export type APIError = {
	code: string;
	message: string;
	data?: Record< string, unknown >;
};

/**
 * Represents an error that occurred during a batch settings update
 */
export type BatchSettingsError = Error & {
	settingErrors: Array< {
		id: string;
		error: APIError;
	} >;
};

export type SettingsState = {
	/**
	 * Array of setting groups
	 */
	groups: SettingsGroup[];

	/**
	 * Settings organized by group ID and setting ID
	 */
	settings: {
		[ groupId: string ]:
			| {
					[ settingId: string ]: Setting;
			  }
			| undefined;
	};

	/**
	 * Edited setting values by group ID and setting ID
	 */
	edits: {
		[ groupId: string ]:
			| {
					[ settingId: string ]: SettingValue;
			  }
			| undefined;
	};

	/**
	 * Tracks save operations in progress
	 */
	isSaving: {
		/**
		 * Tracks save operations in progress by group ID
		 */
		groups: {
			[ groupId: string ]: boolean;
		};
		/**
		 * Tracks save operations in progress by group ID and setting ID
		 */
		settings: {
			[ groupId: string ]:
				| {
						[ settingId: string ]: boolean;
				  }
				| undefined;
		};
	};

	/**
	 * Error states by group ID and setting ID
	 */
	errors: {
		[ groupId: string ]:
			| {
					[ settingId: string ]: unknown;
			  }
			| undefined;
	};
};

/**
 * Represents an edit to a setting
 */
export type SettingEdit = {
	id: string;
	value: SettingValue;
};

/**
 * Represents an object of setting IDs and their values
 */
export type SettingsEditObject = Record< string, SettingEdit >;
