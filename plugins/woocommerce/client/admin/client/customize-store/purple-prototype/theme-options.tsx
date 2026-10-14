/**
 * External dependencies
 */
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { PurpleCard } from './cards/purple-card';
import { MarketplaceCard } from './cards/marketplace-card';
import { CurrentThemeCard } from './cards/current-theme-card';
import { PurpleSuccess } from './cards/purple-success';
import { usePurpleState } from './use-purple-state';
import type { PurpleLayout, PurplePrototypeSettings } from './settings';

type LayoutProps = {
	settings: PurplePrototypeSettings;
};

/**
 * V3: the three cards stacked, each with its image on the left.
 */
const StackedImagesLeft = ( { settings }: LayoutProps ) => {
	const { state, justActivated, install, activate, retry } =
		usePurpleState( settings );

	if ( justActivated ) {
		return <PurpleSuccess homeUrl={ settings.homeUrl } />;
	}

	return (
		<div className="purple-cys-options purple-cys-options--stacked-images-left">
			<PurpleCard
				state={ state }
				slug={ settings.purpleSlug }
				homeUrl={ settings.homeUrl }
				onInstall={ install }
				onActivate={ activate }
				onRetry={ retry }
			/>
			<MarketplaceCard />
			{ state !== 'active' && (
				<CurrentThemeCard theme={ settings.activeTheme } />
			) }
		</div>
	);
};

/**
 * Card layouts that can be picked from the "Purple prototype" admin bar menu.
 */
const LAYOUTS: Record< PurpleLayout, ComponentType< LayoutProps > > = {
	'stacked-images-left': StackedImagesLeft,
};

export const ThemeOptions = ( { settings }: LayoutProps ) => {
	const Layout = LAYOUTS[ settings.layout ] ?? StackedImagesLeft;
	return <Layout settings={ settings } />;
};
