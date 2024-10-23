/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

window.jQuery( document ).ready( function () {
	// hide the notice when the customer clicks the dismiss button up until 1 month, then it will be shown again.
	const wooConnectNoticeSelector = '.woo-connect-notice';
	const wooConnectNoticeLinkSelector = '#woo-connect-notice-url';
	const localStorageKey = 'woo-connect-notice-settings-dismissed';

	window
		.jQuery( wooConnectNoticeSelector )
		.on( 'click', 'button.notice-dismiss', function () {
			window.localStorage.setItem(
				localStorageKey,
				new Date().toString()
			);
			recordEvent( 'woo_connect_notice_in_settings_dismissed' );
		} );

	window.jQuery( wooConnectNoticeLinkSelector ).on( 'click', function () {
		recordEvent( 'woo_connect_notice_in_settings_clicked' );
		return true;
	} );

	let shouldHideNotice = false;

	const savedDismissedDate = window.localStorage.getItem( localStorageKey );
	const parsedDismissedDate = new Date( savedDismissedDate || '' );
	const aMonthAgo = new Date();
	aMonthAgo.setMonth( aMonthAgo.getMonth() - 1 );

	if (
		savedDismissedDate &&
		aMonthAgo.valueOf() < parsedDismissedDate.valueOf()
	) {
		shouldHideNotice = true;
	}

	if ( shouldHideNotice ) {
		window.jQuery( wooConnectNoticeSelector ).remove();
	} else {
		recordEvent( 'woo_connect_notice_in_settings_shown' );
	}
} );
