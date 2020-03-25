/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './stylesheets/_index.scss';
import { PageLayout, EmbedLayout, PrimaryLayout as NoticeArea } from './layout';
import 'wc-api/wp-data-store';
import { withSettingsHydration } from '@woocommerce/data';

const appRoot = document.getElementById( 'root' );
const settingsGroup = 'wc_admin';

if ( appRoot ) {
	const HydratedPageLayout = withSettingsHydration( settingsGroup, window.wcSettings )(
		PageLayout
	);
	render( <HydratedPageLayout />, appRoot );
} else {
	const embeddedRoot = document.getElementById( 'woocommerce-embedded-root' );
	const HydratedEmbedLayout = withSettingsHydration( settingsGroup, window.wcSettings )(
		EmbedLayout
	);
	// Render the header.
	render( <HydratedEmbedLayout />, embeddedRoot );

	embeddedRoot.classList.remove( 'is-embed-loading' );

	// Render notices just above the WP content div.
	const wpBody = document.getElementById( 'wpbody-content' );
	const wrap = wpBody.querySelector( '.wrap' );
	const noticeContainer = document.createElement( 'div' );

	render(
		<div className="woocommerce-layout">
			<NoticeArea />
		</div>,
		wpBody.insertBefore( noticeContainer, wrap )
	);
}
