/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

window.jQuery( document ).ready( function () {
	const expiredNoticeSelector = '#woo-subscription-expired-notice';
	const expiringNoticeSelector = '#woo-subscription-expiring-notice';

	if ( window.jQuery( expiredNoticeSelector ).length ) {
		recordEvent( 'woo_subscription_expired_notice_in_settings_shown' );
	}

	if ( window.jQuery( expiringNoticeSelector ).length ) {
		recordEvent( 'woo_subscription_expiring_notice_in_settings_shown' );
	}

	const callDismissNoticeAPI = function ( notice_id, nonce ) {
		const data = {
			notice_id,
			dismiss_notice_nonce: nonce,
		};

		window.wp.apiFetch( {
			path: `/wc-admin/notice/dismiss`,
			method: 'POST',
			data,
		} );
	};

	window
		.jQuery( expiredNoticeSelector )
		.on( 'click', 'button.notice-dismiss', function () {
			recordEvent(
				'woo_subscription_expired_notice_in_settings_dismissed'
			);

			const dismiss_notice_nonce = window
				.jQuery( expiredNoticeSelector )
				.data( 'dismissnonce' );

			callDismissNoticeAPI(
				'woo-subscription-expired-notice',
				dismiss_notice_nonce
			);
		} );

	window.jQuery( expiredNoticeSelector ).on( 'click', 'a', function () {
		recordEvent( 'woo_subscription_expired_notice_in_settings_clicked' );
		return true;
	} );

	window
		.jQuery( expiringNoticeSelector )
		.on( 'click', 'button.notice-dismiss', function () {
			recordEvent(
				'woo_subscription_expiring_notice_in_settings_dismissed'
			);

			const dismiss_notice_nonce = window
				.jQuery( expiredNoticeSelector )
				.data( 'dismissnonce' );

			callDismissNoticeAPI(
				'woo-subscription-expiring-notice',
				dismiss_notice_nonce
			);
		} );

	window.jQuery( expiringNoticeSelector ).on( 'click', 'a', function () {
		recordEvent( 'woo_subscription_expiring_notice_in_settings_clicked' );
		return true;
	} );
} );
