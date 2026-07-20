/* global JSX */

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
	/** Original form value used while the canonical field value is unchanged. */
	initialValue?: string | string[];
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

/** Serializable validation supported by built-in Settings UI fields. */
export type SettingsUIFieldValidation = {
	min?: number;
	max?: number;
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
	validation?: SettingsUIFieldValidation;
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
	/**
	 * Header visibility: drill-down pages show it, top-level pages hide it
	 * and save from the bottom of the page. Defaults to 'hidden'.
	 */
	header?: 'visible' | 'hidden';
	title?: string;
	subtitle?: string;
	breadcrumbs?: SettingsUIShellBreadcrumb[];
	badges?: SettingsUIShellBadge[];
	navigation?: SettingsUIShellNavigationItem[];
	sectionNavigation?: SettingsUIShellNavigationItem[];
	/** @deprecated Use schema navigation arrays instead. */
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

/** The stable validity state passed to registered settings controls. */
export type SettingsFieldValidity = {
	state: 'valid' | 'invalid' | 'validating';
	message?: string;
};

/** The stable field metadata passed to registered settings controls. */
export type SettingsEditControlField = {
	id: string;
	label: string;
	description?: string;
	placeholder?: string;
	elements?: SettingsUIOption[];
	getValue: ( args: { item: SettingsValues } ) => SettingsValue | undefined;
};

/**
 * The props a registered settings field component receives. This contract is
 * owned by WooCommerce so DataForm generation changes stay inside the adapter.
 */
export type SettingsEditControlProps = {
	data: SettingsValues;
	field: SettingsEditControlField;
	onChange: ( values: Partial< SettingsValues > ) => void;
	hideLabelFromVision: boolean;
	disabled: boolean;
	validity?: SettingsFieldValidity;
};

export type SettingsEditControl = (
	props: SettingsEditControlProps
) => JSX.Element | null;

/**
 * The field component props shipped with WooCommerce 10.9.
 *
 * @deprecated Migrate the component to SettingsEditControlProps. This bridge
 * will be removed when Settings UI leaves its experimental feature flag.
 */
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

/**
 * A field component using the contract shipped with WooCommerce 10.9.
 *
 * @deprecated Register a SettingsEditControl through an object definition.
 */
export type SettingsFieldComponent = (
	props: SettingsFieldComponentProps
) => JSX.Element | null;

/** Values and settings metadata passed to a custom field validator. */
export type SettingsFieldValidatorArgs = {
	value: SettingsValue | undefined;
	values: SettingsValues;
	field: SettingsUIField;
	context: SettingsFieldContext;
};

/** A synchronous custom field validator that returns an error message or null. */
export type SettingsFieldValidator = (
	args: SettingsFieldValidatorArgs
) => string | null;

/** A custom field component and its optional DataForm validator. */
export type SettingsFieldComponentRegistration = {
	component: SettingsEditControl;
	validate?: SettingsFieldValidator;
};

/** A direct field component or a component registration with validation. */
export type SettingsFieldComponentDefinition =
	| SettingsFieldComponent
	| SettingsFieldComponentRegistration;

/**
 * Page-level state exposed to registered components through the
 * `useSettingsUIContext` hook.
 */
export type SettingsUIPageContextValue = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
};

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

/** @deprecated Region components will be removed with the legacy bridge. */
export type SettingsRegionComponentProps = {
	values: SettingsValues;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	schema: SettingsUISchema;
};

/** @deprecated Use schema navigation arrays instead. */
export type SettingsRegionComponent = (
	props: SettingsRegionComponentProps
) => JSX.Element | null;

export type SettingsExtensionScope = {
	page: string;
	section?: string;
};

export type SettingsExtensionRegistration = {
	scope: SettingsExtensionScope;
	components?: Record< string, SettingsFieldComponentDefinition >;
	fieldOverrides?: Record< string, SettingsFieldComponentDefinition >;
	typeRenderers?: Record< string, SettingsFieldComponentDefinition >;
	fieldVisibility?: Record< string, SettingsVisibilityPredicate >;
	groupVisibility?: Record< string, SettingsVisibilityPredicate >;
	saveHandlers?: Record< string, SettingsSaveHandler >;
	/** @deprecated Region registration will be removed with the legacy bridge. */
	regions?: Record< string, SettingsRegionComponent >;
};

/** @deprecated Import registerSettingsExtension from the package instead. */
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
