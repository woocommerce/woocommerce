export type SettingsValue = string | number | boolean | string[] | null;

export type SettingsValues = Record< string, SettingsValue >;

export type SettingsUIOption = {
	label: string;
	value: string;
};

export type SettingsUISaveAdapter =
	| 'form_post'
	| 'none'
	| ( string & NonNullable< unknown > );

export type SettingsUISaveSchema = {
	adapter: SettingsUISaveAdapter;
	name?: string;
};

export type SettingsUISaveStrategy =
	| { adapter: 'form_post' }
	| { adapter: 'custom'; handler: string }
	| { adapter: 'none' }
	| { adapter: string & NonNullable< unknown >; handler?: string };

export type SettingsUIVisibilityRule = {
	controller: string;
	value?: SettingsValue | SettingsValue[];
};

export type SettingsUIField = {
	id: string;
	label: string;
	type: string;
	description?: string;
	value?: SettingsValue;
	options?: SettingsUIOption[];
	component?: string;
	placeholder?: string;
	disabled?: boolean;
	customAttributes?: Record< string, string | number | boolean >;
	visibility?: SettingsUIVisibilityRule;
	save?: SettingsUISaveSchema;
};

export type SettingsUIGroupAction = {
	id: string;
	label: string;
	href: string;
	variant?: 'primary' | 'secondary' | 'tertiary' | 'link' | string;
	target?: string;
	rel?: string;
};

export type SettingsUIGroup = {
	id: string;
	title?: string;
	description?: string;
	actions?: SettingsUIGroupAction[];
	fields: SettingsUIField[];
};

export type SettingsUIShellBreadcrumb = {
	label: string;
	href?: string;
};

export type SettingsUIShellNavigationItem = {
	id: string;
	label: string;
	href: string;
	active?: boolean;
};

/**
 * Visual intent for a shell header badge. Mirrors common web semantic
 * conventions and maps to the design system Badge intents. Intent conveys
 * color only; the `label` text must carry the actual meaning for
 * screen-reader and color-blind users.
 */
export type SettingsUIShellBadgeIntent =
	| 'default'
	| 'info'
	| 'success'
	| 'warning'
	| 'error';

export type SettingsUIShellBadge = {
	label: string;
	intent?: SettingsUIShellBadgeIntent;
};

export type SettingsUIShell = {
	title?: string;
	subtitle?: string;
	breadcrumbs?: SettingsUIShellBreadcrumb[];
	badges?: SettingsUIShellBadge[];
	navigation?: SettingsUIShellNavigationItem[];
	sectionNavigation?: SettingsUIShellNavigationItem[];
	navigationComponent?: string;
};

export type SettingsUISchema = {
	id: string;
	title?: string;
	section?: string;
	save?: SettingsUISaveStrategy;
	shell?: SettingsUIShell;
	groups: Record< string, SettingsUIGroup >;
};

export type SettingsFieldContext = {
	page: string;
	section?: string;
};

export type SettingsFieldComponentProps = {
	field: SettingsUIField;
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
	schema: SettingsUISchema;
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
	schema: SettingsUISchema;
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
	schema: SettingsUISchema;
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

export type SettingsUIRegistry = {
	registerSettingsExtension: (
		registration: SettingsExtensionRegistration
	) => void;
};

declare global {
	interface Window {
		wcSettingsUI?: SettingsUIRegistry;
	}
}
