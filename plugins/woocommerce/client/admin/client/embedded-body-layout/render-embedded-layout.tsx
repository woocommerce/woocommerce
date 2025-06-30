/**
 * External dependencies
 */
import {
	withCurrentUserHydration,
	withSettingsHydration,
	WCUser,
} from '@woocommerce/data';
import debugFactory from 'debug';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PrimaryLayout as NoticeArea } from '../layout/shared';
import { EmbedLayout } from '../layout/embed';
import { EmbeddedBodyLayout } from './embedded-body-layout';
import {
	possiblyRenderOrderAttributionSlot,
	registerOrderAttributionSlotFill,
} from '../order-attribution-install-banner/order-editor/slot';

const debug = debugFactory( 'wc-admin:client' );

/**
 * Renders the hydrated layout. This will render the header.
 *
 * @param {HTMLElement} embeddedRoot  - The root element of the embedded layout.
 * @param {WCUser}      hydrateUser   - The user to hydrate.
 * @param {string}      settingsGroup - The settings group to hydrate.
 */
const renderHydratedLayout = (
	embeddedRoot: HTMLElement,
	hydrateUser: WCUser,
	settingsGroup: string
) => {
	let HydratedEmbedLayout = withSettingsHydration(
		settingsGroup,
		window.wcSettings?.admin
	)( EmbedLayout as React.ComponentType< Record< string, unknown > > );

	if ( hydrateUser ) {
		HydratedEmbedLayout = withCurrentUserHydration( hydrateUser )(
			HydratedEmbedLayout as React.ComponentType<
				Record< string, unknown >
			>
		);
	}

	createRoot( embeddedRoot ).render( <HydratedEmbedLayout /> );
};

/**
 * Finds the wrap element.
 *
 * @param {HTMLElement} wpBody - The WP body element.
 * @return {Element | null} The wrap element or null if not found.
 */
const findWrapElement = ( wpBody: HTMLElement ) => {
	const wrap =
		wpBody.querySelector( '.wrap.woocommerce' ) ||
		document.querySelector( '#wpbody-content > .woocommerce' ) ||
		wpBody.querySelector( '.wrap' );

	if ( ! wrap ) {
		debug( 'Wrap element not found' );
		return null;
	}
	return wrap;
};

/**
 * Renders the notices.
 *
 * @param {HTMLElement} wpBody - The WP body element.
 * @param {Element}     wrap   - The wrap element.
 */
const renderNotices = ( wpBody: HTMLElement, wrap: Element ) => {
	const noticeContainer = document.createElement( 'div' );
	createRoot( wpBody.insertBefore( noticeContainer, wrap ) ).render(
		<div className="woocommerce-layout">
			<NoticeArea />
		</div>
	);
};

/**
 * Renders the embedded body.
 *
 * @param {HTMLElement} wpBody - The WP body element.
 * @param {Element}     wrap   - The wrap element.
 */
const renderEmbeddedBody = ( wpBody: HTMLElement, wrap: Element ) => {
	const embeddedBodyContainer = document.createElement( 'div' );
	createRoot(
		wpBody.insertBefore( embeddedBodyContainer, wrap.nextSibling )
	).render( <EmbeddedBodyLayout /> );
};

/**
 * Registers slot fills for the embedded layout. This should be used only for pages other than the settings page. For settings pages, slot fills are registered in wp-admin-scripts/settings/
 */
const registerSlotFills = () => {
	possiblyRenderOrderAttributionSlot();
	registerOrderAttributionSlotFill();
};

/**
 * Initializes the embedded layout.
 *
 * @param {HTMLElement} embeddedRoot  - The root element of the embedded layout.
 * @param {WCUser}      hydrateUser   - The user to hydrate.
 * @param {string}      settingsGroup - The settings group to hydrate.
 */
export const renderEmbeddedLayout = (
	embeddedRoot: HTMLElement,
	hydrateUser: WCUser,
	settingsGroup: string
) => {
	try {
		// Render the header
		renderHydratedLayout( embeddedRoot, hydrateUser, settingsGroup );

		// Remove the loading class
		embeddedRoot.classList.remove( 'is-embed-loading' );

		// Get and verify wpBody exists
		const wpBody = document.getElementById( 'wpbody-content' );
		if ( ! wpBody ) {
			debug( 'WP Body content element not found' );
			return false;
		}

		// Find and verify wrap element
		const wrap = findWrapElement( wpBody );
		if ( ! wrap ) {
			return false;
		}

		// Render components
		renderNotices( wpBody, wrap );
		renderEmbeddedBody( wpBody, wrap );
		registerSlotFills();

		return true;
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to initialize embedded layout:', error );
	}
};
