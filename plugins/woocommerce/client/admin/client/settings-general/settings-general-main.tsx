/**
 * Internal dependencies
 */
import { ReactSettingsPage } from '../settings/react-settings-page';
import { useGeneralSettings } from './hooks/use-general-settings';
import { fieldTransformer, rowConfigurations } from './react-settings-config';
import './settings-general-main.scss';

/**
 * Main component for the General Settings page.
 * Uses WordPress DataForms for rendering settings.
 */
export const SettingsGeneralMain = () => {
	const { data, isLoading, error } = useGeneralSettings();
	return (
		<ReactSettingsPage
			className="woocommerce-settings-general"
			data={ data }
			error={ error }
			fieldTransformer={ fieldTransformer }
			isLoading={ isLoading }
			rowConfigurations={ rowConfigurations }
		/>
	);
};
