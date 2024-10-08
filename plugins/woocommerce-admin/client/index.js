/**
 * External dependencies
 */
import '@wordpress/notices';
import { createRoot } from '@wordpress/element';
import { CustomerEffortScoreTracksContainer } from '@woocommerce/customer-effort-score';
import {
	withCurrentUserHydration,
	withSettingsHydration,
} from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { initRemoteLogging } from './lib/init-remote-logging';
// Initialize remote logging early to log any errors that occur during initialization.
initRemoteLogging();

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
import {
	SettingsPaymentsMainWrapper,
	SettingsPaymentsOfflineWrapper,
	SettingsPaymentsWooCommercePaymentsWrapper,
} from './settings-payments';
import { ErrorBoundary } from './error-boundary';
import { registerBlueprintSlotfill } from './blueprint';

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

	createRoot( appRoot ).render(
		<ErrorBoundary>
			<HydratedPageLayout />
		</ErrorBoundary>
	);
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
	createRoot( embeddedRoot ).render( <HydratedEmbedLayout /> );

	embeddedRoot.classList.remove( 'is-embed-loading' );

	// Render notices just above the WP content div.
	const wpBody = document.getElementById( 'wpbody-content' );

	const wrap =
		wpBody.querySelector( '.wrap.woocommerce' ) ||
		document.querySelector( '#wpbody-content > .woocommerce' ) ||
		wpBody.querySelector( '.wrap' );
	const noticeContainer = document.createElement( 'div' );

	createRoot( wpBody.insertBefore( noticeContainer, wrap ) ).render(
		<div className="woocommerce-layout">
			<NoticeArea />
		</div>
	);
	const embeddedBodyContainer = document.createElement( 'div' );

	createRoot(
		wpBody.insertBefore( embeddedBodyContainer, wrap.nextSibling )
	).render( <EmbeddedBodyLayout /> );

	possiblyRenderSettingsSlots();

	registerTaxSettingsConflictErrorFill();
	registerPaymentsSettingsBannerFill();
	if (
		window.wcAdminFeatures &&
		window.wcAdminFeatures[ 'launch-your-store' ] === true
	) {
		registerSiteVisibilitySlotFill();
	}

	if ( window.wcAdminFeatures && window.wcAdminFeatures.blueprint === true ) {
		registerBlueprintSlotfill();
	}
}

// Render the CustomerEffortScoreTracksContainer only if
// the feature flag is enabled.
if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'customer-effort-score-tracks' ] === true
) {
	// Set up customer effort score survey.
	( function () {
		const root = appRoot || embeddedRoot;
		createRoot(
			root.insertBefore( document.createElement( 'div' ), null )
		).render( <CustomerEffortScoreTracksContainer /> );
	} )();
}

// Render the payment settings components only if
// the feature flag is enabled.
if (
	window.wcAdminFeatures &&
	window.wcAdminFeatures[ 'reactify-classic-payments-settings' ] === true
) {
	( function () {
		const paymentsMainRoot = document.getElementById(
			'experimental_wc_settings_payments_main'
		);
		const paymentsOfflineRoot = document.getElementById(
			'experimental_wc_settings_payments_offline'
		);
		const paymentsWooCommercePaymentsRoot = document.getElementById(
			'experimental_wc_settings_payments_woocommerce_payments'
		);

		if ( paymentsMainRoot ) {
			createRoot(
				paymentsMainRoot.insertBefore(
					document.createElement( 'div' ),
					null
				)
			).render( <SettingsPaymentsMainWrapper /> );
		}

		if ( paymentsOfflineRoot ) {
			createRoot(
				paymentsOfflineRoot.insertBefore(
					document.createElement( 'div' ),
					null
				)
			).render( <SettingsPaymentsOfflineWrapper /> );
		}

		if ( paymentsWooCommercePaymentsRoot ) {
			createRoot(
				paymentsWooCommercePaymentsRoot.insertBefore(
					document.createElement( 'div' ),
					null
				)
			).render( <SettingsPaymentsWooCommercePaymentsWrapper /> );
		}
	} )();
}
