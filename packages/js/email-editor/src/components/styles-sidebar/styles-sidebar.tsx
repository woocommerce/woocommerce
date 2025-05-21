/**
 * External dependencies
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { styles } from '@wordpress/icons';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error Type for PluginSidebar is missing in @types/wordpress__editor
	PluginSidebar,
	// @ts-expect-error Type for PluginSidebarMoreMenuItem is missing in @types/wordpress__editor
	PluginSidebarMoreMenuItem,
} from '@wordpress/editor';

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
import { Navigator } from './navigator';

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
