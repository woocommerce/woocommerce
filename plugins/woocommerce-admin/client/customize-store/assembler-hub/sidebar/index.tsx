// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/sidebar/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { memo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreenColorPalette } from './sidebar-navigation-screen-color-palette';
import { SidebarNavigationScreenFooter } from './sidebar-navigation-screen-footer';
import { SidebarNavigationScreenHeader } from './sidebar-navigation-screen-header';
import { SidebarNavigationScreenHomepage } from './sidebar-navigation-screen-homepage';
import { SidebarNavigationScreenMain } from './sidebar-navigation-screen-main';
import { SidebarNavigationScreenTypography } from './sidebar-navigation-screen-typography';
// import { SidebarNavigationScreenPages } from './sidebar-navigation-screen-pages';

import { useQuery } from '@woocommerce/navigation';
import { SaveHub } from './save-hub';
// In some cases, the assembler is loaded in an iframe, so we have to re-apply the filter.
import '~/customize-store/design-with-ai/entrepreneur-flow';
import { SidebarContent } from '../components/sidebar';
import { SidebarNavigationScreenLogo } from './sidebar-navigation-screen-logo';
import { isPatternToolkitFullComposabilityFeatureFlagEnabled } from '../utils/is-full-composability-enabled';
import { SidebarNavigationScreenHomepagePTK } from './sidebar-navigation-screen-homepage-ptk';

const getComponentByPathParams = ( params: string | undefined ) => {
	if ( params === '/customize-store/assembler-hub' ) {
		return <SidebarNavigationScreenMain />;
	}

	if ( params === '/customize-store/assembler-hub/color-palette' ) {
		return <SidebarNavigationScreenColorPalette />;
	}

	if ( params === '/customize-store/assembler-hub/logo' ) {
		return <SidebarNavigationScreenLogo />;
	}

	if ( params === '/customize-store/assembler-hub/typography' ) {
		return <SidebarNavigationScreenTypography />;
	}

	if ( params === '/customize-store/assembler-hub/header' ) {
		return <SidebarNavigationScreenHeader />;
	}

	if (
		isPatternToolkitFullComposabilityFeatureFlagEnabled() &&
		params?.includes( '/customize-store/assembler-hub/homepage' )
	) {
		return <SidebarNavigationScreenHomepagePTK />;
	}

	if ( params === '/customize-store/assembler-hub/homepage' ) {
		return <SidebarNavigationScreenHomepage />;
	}

	if ( params === '/customize-store/assembler-hub/footer' ) {
		return <SidebarNavigationScreenFooter />;
	}

	return <SidebarNavigationScreenMain />;
};

function SidebarScreens() {
	const params = useQuery().path;

	return (
		<>
			<SidebarContent routeKey={ 'default' }>
				{ getComponentByPathParams( params ) }
			</SidebarContent>
		</>
	);
}

function Sidebar() {
	return (
		<>
			<SidebarScreens />
			<SaveHub />
		</>
	);
}

export default memo( Sidebar );
