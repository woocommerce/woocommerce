export type SettingsValue = string | number | boolean | string[] | null;

export type SettingsValues = Record< string, SettingsValue >;

export type ModernSettingsOption = {
	label: string;
	value: string;
};

export type ModernSettingsSaveSchema = {
	adapter: 'form_post' | 'none' | string;
	name?: string;
};

export type ModernSettingsSaveStrategy =
	| { adapter: 'form_post' }
	| { adapter: 'custom'; handler: string }
	| { adapter: 'none' }
	| { adapter: string; handler?: string };

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

export type ModernSettingsShellBreadcrumb = {
	label: string;
	href?: string;
};

export type ModernSettingsShellNavigationItem = {
	id: string;
	label: string;
	href: string;
	active?: boolean;
};

export type ModernSettingsShell = {
	title?: string;
	breadcrumbs?: ModernSettingsShellBreadcrumb[];
	navigation?: ModernSettingsShellNavigationItem[];
	sectionNavigation?: ModernSettingsShellNavigationItem[];
	navigationComponent?: string;
};

export type ModernSettingsSchema = {
	id: string;
	title?: string;
	section?: string;
	save?: ModernSettingsSaveStrategy;
	shell?: ModernSettingsShell;
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
	values: SettingsValues;
	initialValues: SettingsValues;
	setValue: ( fieldId: string, value: SettingsValue ) => void;
	setValues: ( values: Partial< SettingsValues > ) => void;
	context: SettingsFieldContext;
};

export type SettingsFieldComponent = (
	props: SettingsFieldComponentProps
) => JSX.Element | null;

export type SettingsVisibilityPredicateArgs = {
	values: SettingsValues;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	schema: ModernSettingsSchema;
};

export type SettingsVisibilityPredicate = (
	args: SettingsVisibilityPredicateArgs
) => boolean;

export type SettingsSaveHandlerArgs = {
	values: SettingsValues;
	initialValues: SettingsValues;
	changedValues: Partial< SettingsValues >;
	dirtyFields: string[];
	context: SettingsFieldContext;
	schema: ModernSettingsSchema;
};

export type SettingsSaveResult = void | {
	values?: SettingsValues;
	notice?: string;
};

export type SettingsSaveHandler = (
	args: SettingsSaveHandlerArgs
) => Promise< SettingsSaveResult > | SettingsSaveResult;

export type SettingsRegionComponentProps = {
	values: SettingsValues;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	schema: ModernSettingsSchema;
};

export type SettingsRegionComponent = (
	props: SettingsRegionComponentProps
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
	fieldVisibility?: Record< string, SettingsVisibilityPredicate >;
	groupVisibility?: Record< string, SettingsVisibilityPredicate >;
	saveHandlers?: Record< string, SettingsSaveHandler >;
	regions?: Record< string, SettingsRegionComponent >;
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
