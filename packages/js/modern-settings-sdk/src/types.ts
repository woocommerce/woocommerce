export type SettingsValue = string | number | boolean | string[] | null;

export type ModernSettingsOption = {
	label: string;
	value: string;
};

export type ModernSettingsSaveSchema = {
	adapter: 'form_post' | 'none' | string;
	name?: string;
};

export type ModernSettingsField = {
	id: string;
	label: string;
	type: string;
	description?: string;
	value?: SettingsValue;
	options?: ModernSettingsOption[];
	component?: string;
	placeholder?: string;
	disabled?: boolean;
	customAttributes?: Record< string, string | number | boolean >;
	save?: ModernSettingsSaveSchema;
};

export type ModernSettingsGroupAction = {
	id: string;
	label: string;
	href: string;
	variant?: 'primary' | 'secondary' | 'tertiary' | 'link' | string;
	target?: string;
	rel?: string;
};

export type ModernSettingsGroup = {
	id: string;
	title?: string;
	description?: string;
	actions?: ModernSettingsGroupAction[];
	fields: ModernSettingsField[];
};

export type ModernSettingsSchema = {
	id: string;
	title?: string;
	section?: string;
	groups: Record< string, ModernSettingsGroup >;
};

export type SettingsFieldContext = {
	page: string;
	section?: string;
};

export type SettingsFieldComponentProps = {
	field: ModernSettingsField;
	value: SettingsValue;
	onChange: ( value: SettingsValue ) => void;
	context: SettingsFieldContext;
};

export type SettingsFieldComponent = (
	props: SettingsFieldComponentProps
) => JSX.Element | null;

export type SettingsExtensionScope = {
	page: string;
	section?: string;
};

export type SettingsExtensionRegistration = {
	scope: SettingsExtensionScope;
	components?: Record< string, SettingsFieldComponent >;
	fieldOverrides?: Record< string, SettingsFieldComponent >;
	typeRenderers?: Record< string, SettingsFieldComponent >;
};

export type ModernSettingsRegistry = {
	registerSettingsExtension: (
		registration: SettingsExtensionRegistration
	) => void;
};

declare global {
	interface Window {
		wcModernSettings?: ModernSettingsRegistry;
	}
}
