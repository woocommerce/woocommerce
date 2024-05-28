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

	window
		.jQuery( expiredNoticeSelector )
		.on( 'click', 'button.notice-dismiss', function () {
			recordEvent(
				'woo_subscription_expired_notice_in_settings_dismissed'
			);

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

	window.jQuery( expiringNoticeSelector ).on( 'click', 'a', function () {
		recordEvent( 'woo_subscription_expiring_notice_in_settings_clicked' );
		return true;
	} );
} );
