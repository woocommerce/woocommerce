window.jQuery( document ).ready( function () {
	const expiredNoticeSelector = '#woo-subscription-expired-notice';
	const expiringNoticeSelector = '#woo-subscription-expiring-notice';

	window
		.jQuery( expiredNoticeSelector )
		.on( 'click', 'button.notice-dismiss', function () {
			const notice_id = 'woo-subscription-expired-notice';
			const data = {
				notice_id,
			};

			window.wp.apiFetch( {
				path: `/wc-admin/notice/dismiss`,
				method: 'POST',
				data,
			} );
		} );

	window
		.jQuery( expiringNoticeSelector )
		.on( 'click', 'button.notice-dismiss', function () {
			const notice_id = 'woo-subscription-expiring-notice';
			const data = {
				notice_id,
			};

			window.wp.apiFetch( {
				path: `/wc-admin/notice/dismiss`,
				method: 'POST',
				data,
			} );
		} );
} );
