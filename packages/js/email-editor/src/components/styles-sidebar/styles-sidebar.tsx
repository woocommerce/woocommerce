/**
 * External dependencies
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
import { styles } from '@wordpress/icons';
import {
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { storeName, stylesSidebarId } from '../../store';
import {
	ScreenTypography,
	ScreenTypographyElement,
	ScreenLayout,
	ScreenRoot,
	ScreenColors,
} from './screens';

type Props = ComponentProps< typeof ComplementaryArea >;

export function RawStylesSidebar( props: Props ): JSX.Element {
	const { userCanEditGlobalStyles } = useSelect( ( select ) => {
		const { canEdit } = select( storeName ).canUserEditGlobalEmailStyles();
		return {
			userCanEditGlobalStyles: canEdit,
		};
	}, [] );

	return (
		userCanEditGlobalStyles && (
			<ComplementaryArea
				identifier={ stylesSidebarId }
				className="woocommerce-email-editor-styles-panel"
				header={ __( 'Styles', 'woocommerce' ) }
				closeLabel={ __( 'Close styles sidebar', 'woocommerce' ) }
				icon={ styles }
				scope={ storeName }
				smallScreenTitle={ __( 'No title', 'woocommerce' ) }
				{ ...props }
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
			</ComplementaryArea>
		)
	);
}

export const StylesSidebar = memo( RawStylesSidebar );
