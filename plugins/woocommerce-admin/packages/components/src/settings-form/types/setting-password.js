/**
 * Internal dependencies
 */
import { SettingText } from './setting-text';

export const SettingPassword = ( props ) => {
	return <SettingText { ...props } type="password" />;
};
