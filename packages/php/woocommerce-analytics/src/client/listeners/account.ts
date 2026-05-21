/* global jQuery */

import type { RecordEventFunction } from '../types/shared';

/**
 * Attach event listeners for my account page
 *
 * @param recordEvent - Record event function
 */
export function initListeners( recordEvent: RecordEventFunction ): void {
	jQuery( '.woocommerce-MyAccount-navigation-link--customer-logout' ).on( 'click', function () {
		recordEvent( 'my_account_tab_click', {
			tab: 'logout',
		} );
	} );
}
