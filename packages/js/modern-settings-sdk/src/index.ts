export { ModernSettingsPage } from './modern-settings-page';
export { NativeSettingsField } from './native-fields';
export {
	HiddenInputs,
	getHiddenInputs,
	getHiddenInputsForField,
} from './hidden-inputs';
export { registerSettingsExtension, resolveFieldComponent } from './registry';
export type {
	ModernSettingsField,
	ModernSettingsGroup,
	ModernSettingsGroupAction,
	ModernSettingsOption,
	ModernSettingsRegistry,
	ModernSettingsSaveSchema,
	ModernSettingsSchema,
	SettingsExtensionRegistration,
	SettingsExtensionScope,
	SettingsFieldComponent,
	SettingsFieldComponentProps,
	SettingsFieldContext,
	SettingsValue,
} from './types';
