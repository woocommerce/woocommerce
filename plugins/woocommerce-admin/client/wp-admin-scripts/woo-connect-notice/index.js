window.jQuery( document ).ready( function() {
	// hide the notice when the customer clicks the dismiss button up until 1 month, then it will be shown again.
	const wooConnectNoticeSelector = '.woo-connect-notice';
	const localStorageKey = 'woo-connect-notice-settings-dismissed';

	window.jQuery( wooConnectNoticeSelector ).on( "click", "button.notice-dismiss", function() {
			window.localStorage.setItem( localStorageKey, new Date().toString() );
	});

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
	}
} );
