/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const initMarketplaceLinkTracking = () => {
	const container = document.querySelector( '.wc-settings-marketplace-link' );
	if ( ! container ) {
		return;
	}

	const link = container.querySelector( 'a' );
	const settingsTab = container.getAttribute( 'data-settings-tab' );
	const settingsSection = container.getAttribute( 'data-settings-section' );

	if ( link && settingsTab ) {
		link.addEventListener( 'click', () => {
			recordEvent( 'settings_marketplace_link_click', {
				settings_area: settingsTab,
				settings_section: settingsSection || undefined,
			} );
		} );
	}
};

initMarketplaceLinkTracking();
