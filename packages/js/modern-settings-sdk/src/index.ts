export { ModernSettingsPage } from './modern-settings-page';
export { NativeSettingsField } from './native-fields';
export { HiddenInputs, getHiddenInputs } from './hidden-inputs';
export {
	registerSettingsExtension,
	resolveFieldComponent,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
	resolveRegionComponent,
	resolveSaveHandler,
} from './registry';
export type {
	ModernSettingsField,
	ModernSettingsGroup,
	ModernSettingsGroupAction,
	ModernSettingsOption,
	ModernSettingsRegistry,
	ModernSettingsSaveSchema,
	ModernSettingsSaveStrategy,
	ModernSettingsSchema,
	ModernSettingsShell,
	ModernSettingsShellBreadcrumb,
	SettingsExtensionRegistration,
	SettingsExtensionScope,
	SettingsFieldComponent,
	SettingsFieldComponentProps,
	SettingsFieldContext,
	SettingsRegionComponent,
	SettingsRegionComponentProps,
	SettingsSaveHandler,
	SettingsSaveHandlerArgs,
	SettingsSaveResult,
	SettingsValue,
	SettingsValues,
	SettingsVisibilityPredicate,
	SettingsVisibilityPredicateArgs,
} from './types';
