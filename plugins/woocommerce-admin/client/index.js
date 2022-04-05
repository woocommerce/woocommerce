/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';
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
import { CustomerEffortScoreTracksContainer } from './customer-effort-score-tracks';
import { EmbeddedBodyLayout } from './embedded-body-layout';
import { PaymentsRecommendationsBanner } from './payments-recommendations-banner';

// Modify webpack pubilcPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = global.wcAdminAssets.path;

const appRoot = document.getElementById( 'root' );
const embeddedRoot = document.getElementById( 'woocommerce-embedded-root' );
const settingsGroup = 'wc_admin';
const hydrateUser = getAdminSetting( 'currentUserData' );

// TODO: move this to another module
const Banner = () => {
	// if feature flag is enabled
	// show banned
	// else show existing h2 description and stuff
	const featureFlag = true;

	if ( featureFlag ) {
		return (<>
			<PaymentsRecommendationsBanner>
			</PaymentsRecommendationsBanner>
		</>)
	} else {
		return (
			<>
				<h2>Payment Methods</h2>
				<div id="payment_gateways_options-description">
					<p>
						Installed payment methods are listed below and can be
						sorted to control their display order on the frontend.
					</p>
				</div>
			</>
		);
	}
};


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
		HydratedPageLayout = withCurrentUserHydration( hydrateUser )(
			HydratedPageLayout
		);
	}
	render( <HydratedPageLayout />, appRoot );
} else if ( embeddedRoot ) {
	let HydratedEmbedLayout = withSettingsHydration(
		settingsGroup,
		window.wcSettings.admin
	)( EmbedLayout );
	if ( hydrateUser ) {
		HydratedEmbedLayout = withCurrentUserHydration( hydrateUser )(
			HydratedEmbedLayout
		);
	}
	// Render the header.
	render( <HydratedEmbedLayout />, embeddedRoot );

	embeddedRoot.classList.remove( 'is-embed-loading' );

	// Render notices just above the WP content div.
	const wpBody = document.getElementById( 'wpbody-content' );

	const mainform = document.getElementById( 'wc_payment_gateways_banner_slotfill' );
	const wrap =
		wpBody.querySelector( '.wrap.woocommerce' ) ||
		wpBody.querySelector( '.wrap' );
	const noticeContainer = document.createElement( 'div' );
	// always insert the slotfill -> problem because mainform element does not always exist
	// note this slotfill is not part of the form
	// how do we know which page we are on?
	render( Banner(), mainform );

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

		render(
			<CustomerEffortScoreTracksContainer />,
			root.insertBefore( document.createElement( 'div' ), null )
		);
	} )();
}

