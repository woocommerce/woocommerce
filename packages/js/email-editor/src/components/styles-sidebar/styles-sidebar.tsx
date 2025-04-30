/**
 * External dependencies
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { styles } from '@wordpress/icons';
import {
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
} from '@wordpress/components';

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
					<NavigatorProvider initialPath="/">
						<NavigatorScreen path="/">
							<ScreenRoot />
						</NavigatorScreen>

						<NavigatorScreen path="/typography">
							<ScreenTypography />
						</NavigatorScreen>

						<NavigatorScreen path="/typography/text">
							<ScreenTypographyElement element="text" />
						</NavigatorScreen>

						<NavigatorScreen path="/typography/link">
							<ScreenTypographyElement element="link" />
						</NavigatorScreen>

						<NavigatorScreen path="/typography/heading">
							<ScreenTypographyElement element="heading" />
						</NavigatorScreen>

						<NavigatorScreen path="/typography/button">
							<ScreenTypographyElement element="button" />
						</NavigatorScreen>

						<NavigatorScreen path="/colors">
							<ScreenColors />
						</NavigatorScreen>

						<NavigatorScreen path="/layout">
							<ScreenLayout />
						</NavigatorScreen>
					</NavigatorProvider>
				</PluginSidebar>
			</>
		)
	);
}

export const StylesSidebar = memo( RawStylesSidebar );
