/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';
import { CustomerEffortScoreTracksContainer } from '@woocommerce/customer-effort-score';
import {
	withCurrentUserHydration,
	withSettingsHydration,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './stylesheets/_index.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import { PageLayout, EmbedLayout, PrimaryLayout as NoticeArea } from './layout';
import { EmbeddedBodyLayout } from './embedded-body-layout';
import './xstate.js';
import { deriveWpAdminBackgroundColours } from './utils/derive-wp-admin-background-colours';
import { possiblyRenderSettingsSlots } from './settings/settings-slots';
import { registerTaxSettingsConflictErrorFill } from './settings/conflict-error-slotfill';
import { registerPaymentsSettingsBannerFill } from './payments/payments-settings-banner-slotfill';
import { registerSiteVisibilitySlotFill } from './launch-your-store';

const appRoot = document.getElementById( 'root' );
const embeddedRoot = document.getElementById( 'woocommerce-embedded-root' );
const settingsGroup = 'wc_admin';
const hydrateUser = getAdminSetting( 'currentUserData' );

deriveWpAdminBackgroundColours();

if ( appRoot ) {
	let HydratedPageLayout = withSettingsHydration(
		settingsGroup,
		window.wcSettings.admin
	)( PageLayout );
	const preloadSettings = window.wcSettings.admin
		? window.wcSettings.admin.preloadSettings
		: false;
	const hydrateSettings = preloadSettings && preloadSettings.general;

	if ( hydrateSettings ) {
		HydratedPageLayout = withSettingsHydration( 'general', {
			general: preloadSettings.general,
		} )( HydratedPageLayout );
	}
	if ( hydrateUser ) {
		HydratedPageLayout =
			withCurrentUserHydration( hydrateUser )( HydratedPageLayout );
	}
	render( <HydratedPageLayout />, appRoot );
} else if ( embeddedRoot ) {
	let HydratedEmbedLayout = withSettingsHydration(
		settingsGroup,
		window.wcSettings.admin
	)( EmbedLayout );
	if ( hydrateUser ) {
		HydratedEmbedLayout =
			withCurrentUserHydration( hydrateUser )( HydratedEmbedLayout );
	}
	// Render the header.
	render( <HydratedEmbedLayout />, embeddedRoot );

	embeddedRoot.classList.remove( 'is-embed-loading' );

	// Render notices just above the WP content div.
	const wpBody = document.getElementById( 'wpbody-content' );

	const wrap =
		wpBody.querySelector( '.wrap.woocommerce' ) ||
		document.querySelector( '#wpbody-content > .woocommerce' ) ||
		wpBody.querySelector( '.wrap' );
	const noticeContainer = document.createElement( 'div' );

	render(
		<div className="woocommerce-layout">
			<NoticeArea />
		</div>,
		wpBody.insertBefore( noticeContainer, wrap )
	);
	const embeddedBodyContainer = document.createElement( 'div' );
	render(
		<EmbeddedBodyLayout />,
		wpBody.insertBefore( embeddedBodyContainer, wrap.nextSibling )
	);

	possiblyRenderSettingsSlots();

	registerTaxSettingsConflictErrorFill();
	registerPaymentsSettingsBannerFill();
	if (
		window.wcAdminFeatures &&
		window.wcAdminFeatures[ 'launch-your-store' ] === true
	) {
		registerSiteVisibilitySlotFill();
	}
}

// Set up customer effort score survey.
( function () {
	const root = appRoot || embeddedRoot;
	render(
		<CustomerEffortScoreTracksContainer />,
		root.insertBefore( document.createElement( 'div' ), null )
	);
} )();
