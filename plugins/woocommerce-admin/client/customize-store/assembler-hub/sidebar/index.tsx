// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/sidebar/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { memo, useCallback, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreenColorPalette } from './sidebar-navigation-screen-color-palette';
import { SidebarNavigationScreenFooter } from './sidebar-navigation-screen-footer/sidebar-navigation-screen-footer';
import { SidebarNavigationScreenHeader } from './sidebar-navigation-screen-header/sidebar-navigation-screen-header';
import { SidebarNavigationScreenHomepage } from './sidebar-navigation-screen-homepage/sidebar-navigation-screen-homepage';
import { SidebarNavigationScreenMain } from './sidebar-navigation-screen-main';
import { SidebarNavigationScreenTypography } from './sidebar-navigation-screen-typography/sidebar-navigation-screen-typography';
// import { SidebarNavigationScreenPages } from './sidebar-navigation-screen-pages';

import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';
import { SaveHub } from './save-hub';
// In some cases, the assembler is loaded in an iframe, so we have to re-apply the filter.
import '~/customize-store/design-with-ai/entrepreneur-flow';
import {
	SidebarContent,
	SidebarNavigationAnimationDirection,
	SidebarNavigationContext,
} from '../components/sidebar';
import { SidebarNavigationScreenLogo } from './sidebar-navigation-screen-logo';
import { isFullComposabilityFeatureAndAPIAvailable } from '../utils/is-full-composability-enabled';
import { SidebarNavigationScreenHomepagePTK } from './sidebar-navigation-screen-homepage-ptk/sidebar-navigation-screen-homepage-ptk';

const getComponentByPathParams = (
	params: string,
	onNavigateBackClick: () => void
) => {
	if ( params === '/customize-store/assembler-hub' ) {
		return <SidebarNavigationScreenMain />;
	}

	if ( params === '/customize-store/assembler-hub/color-palette' ) {
		return (
			<SidebarNavigationScreenColorPalette
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	if ( params === '/customize-store/assembler-hub/logo' ) {
		return (
			<SidebarNavigationScreenLogo
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	if ( params === '/customize-store/assembler-hub/typography' ) {
		return (
			<SidebarNavigationScreenTypography
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	if ( params === '/customize-store/assembler-hub/header' ) {
		return (
			<SidebarNavigationScreenHeader
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	if (
		isFullComposabilityFeatureAndAPIAvailable() &&
		params?.includes( '/customize-store/assembler-hub/homepage' )
	) {
		return (
			<SidebarNavigationScreenHomepagePTK
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	if (
		! isFullComposabilityFeatureAndAPIAvailable() &&
		params === '/customize-store/assembler-hub/homepage'
	) {
		return (
			<SidebarNavigationScreenHomepage
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	if ( params === '/customize-store/assembler-hub/footer' ) {
		return (
			<SidebarNavigationScreenFooter
				onNavigateBackClick={ onNavigateBackClick }
			/>
		);
	}

	return <SidebarNavigationScreenMain />;
};

function SidebarScreens() {
	const params = useQuery().path;

	const { navigate } = useContext( SidebarNavigationContext );

	const onNavigateBackClick = useCallback( () => {
		const assemblerUrl = getNewPath(
			{ customizing: true },
			'/customize-store/assembler-hub',
			{}
		);
		navigateTo( { url: assemblerUrl } );
		navigate( SidebarNavigationAnimationDirection.Back );
	}, [ navigate ] );

	return <>{ getComponentByPathParams( params, onNavigateBackClick ) }</>;
}

function Sidebar() {
	return (
		<>
			<SidebarContent>
				<SidebarScreens />
			</SidebarContent>
			<SaveHub />
		</>
	);
}

export default memo( Sidebar );
