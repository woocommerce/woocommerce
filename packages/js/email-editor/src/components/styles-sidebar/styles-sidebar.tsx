/**
 * External dependencies
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { styles } from '@wordpress/icons';
import { Navigator } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import {
	ScreenTypography,
	ScreenTypographyElement,
	ScreenLayout,
	ScreenRoot,
	ScreenColors,
} from './screens';

export function RawStylesSidebar(): JSX.Element {
	const { userCanEditGlobalStyles } = useSelect( ( select ) => {
		const { canEdit } = select( storeName ).canUserEditGlobalEmailStyles();
		return {
			userCanEditGlobalStyles: canEdit,
		};
	}, [] );

	return (
		userCanEditGlobalStyles && (
			<>
				<PluginSidebarMoreMenuItem
					target="email-styles-sidebar"
					icon={ styles }
				>
					{ __( 'Email styles', 'woocommerce' ) }
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="email-styles-sidebar"
					icon={ styles }
					title={ __( 'Styles', 'woocommerce' ) }
					className="woocommerce-email-editor-styles-panel"
					header={ __( 'Styles', 'woocommerce' ) }
				>
					<Navigator initialPath="/">
						<Navigator.Screen path="/">
							<ScreenRoot />
						</Navigator.Screen>

						<Navigator.Screen path="/typography">
							<ScreenTypography />
						</Navigator.Screen>

						<Navigator.Screen path="/typography/text">
							<ScreenTypographyElement element="text" />
						</Navigator.Screen>

						<Navigator.Screen path="/typography/link">
							<ScreenTypographyElement element="link" />
						</Navigator.Screen>

						<Navigator.Screen path="/typography/heading">
							<ScreenTypographyElement element="heading" />
						</Navigator.Screen>

						<Navigator.Screen path="/typography/button">
							<ScreenTypographyElement element="button" />
						</Navigator.Screen>

						<Navigator.Screen path="/colors">
							<ScreenColors />
						</Navigator.Screen>

						<Navigator.Screen path="/layout">
							<ScreenLayout />
						</Navigator.Screen>
					</Navigator>
				</PluginSidebar>
			</>
		)
	);
}

export const StylesSidebar = memo( RawStylesSidebar );
