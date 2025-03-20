declare global {
	interface BaseSettingsField {
		title?: string;
		type:
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
			| 'relative_date_selector'
			| 'textarea'
			| 'sectionend'
			| 'single_select_page'
			| 'single_select_page_with_search'
			| 'single_select_country'
			| 'slotfill_placeholder';
		id: string;
		desc?: string;
		description?: string;
		desc_tip?: boolean | string;
		default?: string | number | boolean | object;
		value: string | number | boolean | object;
		placeholder?: string;
		custom_attributes?: {
			[ key: string ]: string | number;
		};
		options?: {
			[ key: string ]: string;
		};
		css?: string;
		class?: string;
		autoload?: boolean;
		show_if_checked?: string;
		content?: string;
		[ key: string ]: any;
	}

	interface CustomSettingsField {
		id: string;
		type: 'custom';
		content: string;
	}

	interface GroupSettingsField {
		type: 'group';
		id: string;
		settings: Exclude< SettingsField, GroupSettingsField >[];
		label?: string;
		desc?: string;
		title?: string;
	}

	interface CheckboxSettingsField extends BaseSettingsField {
		type: 'checkbox';
		checkboxgroup?: 'start' | 'end' | '';
	}

	interface CheckboxGroupSettingsField {
		id: string;
		type: 'checkboxgroup';
		title: string;
		settings: CheckboxSettingsField[];
	}

	interface InfoSettingsField {
		id: string;
		title: string;
		type: 'info';
		text: string;
		row_class?: string;
		css?: string;
	}

	type SettingsField =
		| BaseSettingsField
		| CustomSettingsField
		| GroupSettingsField
		| CheckboxGroupSettingsField
		| CheckboxSettingsField
		| InfoSettingsField;

	interface SettingsSection {
		label: string;
		settings: SettingsField[];
	}

	interface SettingsPage {
		label: string;
		slug: string;
		icon: string;
		sections: {
			[ key: string ]: SettingsSection;
		};
		is_modern: boolean;
		start: CustomSettingsField | null;
		end: CustomSettingsField | null;
	}

	interface SettingsPages {
		[ key: string ]: SettingsPage;
	}

	interface SettingsData {
		start: CustomSettingsField | null;
		pages: SettingsPages;
		_wpnonce: string;
	}
}

declare global {
	interface Window {
		wcSettings: {
			admin: {
				settingsData: SettingsData;
			};
		};
		wcTracks: {
			isEnabled: boolean;
			validateEvent: ( name: string, properties: unknown ) => void;
			recordEvent: ( name: string, properties: unknown ) => void;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
