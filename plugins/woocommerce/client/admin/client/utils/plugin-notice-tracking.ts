/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Row attribute WooCommerce sets on the notice rows it prints under up-to-date plugins on the
 * Plugins screen. Core's own update rows don't carry it.
 */
const NOTICE_ROW_SELECTOR = 'tr[data-plugin-row-type]';

/**
 * Record Tracks events for the subscription links WooCommerce prints on the Plugins screen.
 *
 * Links inside a core update row fire `<eventPrefix>_shown` and `<eventPrefix>_clicked` with no
 * properties. Links inside a WooCommerce notice row, printed for a plugin with no update pending,
 * fire the same events with `no_update: true`. The shown event fires once per placement present.
 *
 * @param {string} selector    CSS selector matching the links.
 * @param {string} eventPrefix Event name without the `_shown` / `_clicked` suffix.
 */
export function trackPluginNoticeLinks(
	selector: string,
	eventPrefix: string
) {
	const links = Array.from(
		document.querySelectorAll< HTMLAnchorElement >( selector )
	);
	const updateRowLinks = links.filter(
		( link ) => ! link.closest( NOTICE_ROW_SELECTOR )
	);
	const noticeRowLinks = links.filter( ( link ) =>
		link.closest( NOTICE_ROW_SELECTOR )
	);

	if ( updateRowLinks.length > 0 ) {
		recordEvent( `${ eventPrefix }_shown` );
		updateRowLinks.forEach( ( link ) => {
			link.addEventListener( 'click', () => {
				recordEvent( `${ eventPrefix }_clicked` );
			} );
		} );
	}

	if ( noticeRowLinks.length > 0 ) {
		recordEvent( `${ eventPrefix }_shown`, { no_update: true } );
		noticeRowLinks.forEach( ( link ) => {
			link.addEventListener( 'click', () => {
				recordEvent( `${ eventPrefix }_clicked`, { no_update: true } );
			} );
		} );
	}
}
