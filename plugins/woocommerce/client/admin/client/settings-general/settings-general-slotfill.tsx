/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';
import { registerPlugin, getPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import './settings-general-main.scss';
import { SettingsGeneralMainWrapper } from './index';
import { SETTINGS_SLOT_FILL_CONSTANT } from '../settings/settings-slots';

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );
const PLUGIN_ID = 'woocommerce-admin-general-settings';

const GeneralSettingsFill = () => {
	return (
		<Fill>
			<SettingsGeneralMainWrapper />
		</Fill>
	);
};

export const registerGeneralSettingsFill = () => {
	if ( getPlugin( PLUGIN_ID ) ) {
		return;
	}

	registerPlugin( PLUGIN_ID, {
		scope: 'woocommerce-general-settings',
		render: GeneralSettingsFill,
	} );
};
