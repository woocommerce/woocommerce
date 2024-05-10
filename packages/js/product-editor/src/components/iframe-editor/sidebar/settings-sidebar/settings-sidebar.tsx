/**
 * External dependencies
 */
import { BlockInspector } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import { isRTL, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import drawerLeft from './drawer-left';
import drawerRight from './drawer-right';
import { PluginSidebar } from '../plugin-sidebar';

const SettingsHeader = () => {
	return <strong>{ __( 'Settings', 'woocommerce' ) }</strong>;
};

export const SettingsSidebar = () => {
	return (
		<PluginSidebar
			// By not providing a name, the sidebar will not be listed in
			// the more menu's Plugins menu group.
			identifier="woocommerce-product-editor-settings-sidebar"
			title={ __( 'Settings', 'woocommerce' ) }
			icon={ isRTL() ? drawerRight : drawerLeft }
			isActiveByDefault={ true }
			// We need to pass a custom header to the sidebar to prevent
			// the pin button in the default header from being displayed.
			header={ <SettingsHeader /> }
			closeLabel={ __( 'Close settings', 'woocommerce' ) }
		>
			<BlockInspector />
		</PluginSidebar>
	);
};
