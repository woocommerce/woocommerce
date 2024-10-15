/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export interface SubscriptionsExpiredExpiringNoticeProps {
	type: string;
}

export interface TrackEvents {
	shown: string;
	clicked: string;
	dismissed: string;
}

export default function SubscriptionsExpiredExpiringNotice(
	props: SubscriptionsExpiredExpiringNoticeProps
): JSX.Element | null {
	const { type } = props;
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const eventKeys: Record< string, TrackEvents > = {
		'woo-subscription-expired-notice': {
			shown: 'woo_subscription_expired_notice_in_marketplace_shown',
			clicked: 'woo_subscription_expired_notice_in_marketplace_clicked',
			dismissed:
				'woo_subscription_expired_notice_in_marketplace_dismissed',
		},
		'woo-subscription-expiring-notice': {
			shown: 'woo_subscription_expiring_notice_in_marketplace_shown',
			clicked: 'woo_subscription_expiring_notice_in_marketplace_clicked',
			dismissed:
				'woo_subscription_expiring_notice_in_marketplace_dismissed',
		},
		'woo-subscription-missing-notice': {
			shown: 'woo_subscription_missing_notice_in_marketplace_shown',
			clicked: 'woo_subscription_missing_notice_in_marketplace_clicked',
			dismissed:
				'woo_subscription_missing_notice_in_marketplace_dismissed',
		},
	};

	let notice = null;
	let notice_id = '';
	const dismiss_notice_nonce = wccomSettings?.dismissNoticeNonce || '';

	if ( type === 'expired' ) {
		notice = wccomSettings?.subscription_expired_notice || {};
		notice_id = 'woo-subscription-expired-notice';
	} else if ( type === 'expiring' ) {
		notice = wccomSettings?.subscription_expiring_notice || {};
		notice_id = 'woo-subscription-expiring-notice';
	} else if ( type === 'missing' ) {
		notice = wccomSettings?.subscription_missing_notice || {};
		notice_id = 'woo-subscription-missing-notice';
	} else {
		return null;
	}

	if ( ! wccomSettings.isConnected || ! notice?.description ) {
		return null;
	}

	const handleClose = () => {
		recordEvent( eventKeys[ notice_id ].dismissed );
		const data = { notice_id, dismiss_notice_nonce };
		apiFetch( {
			path: `/wc-admin/notice/dismiss`,
			method: 'POST',
			data,
		} );
	};

	function handleClick() {
		recordEvent( eventKeys[ notice_id ].clicked );
	}

	function handleLoad() {
		recordEvent( eventKeys[ notice_id ].shown );
	}

	return (
		<Notice
			id={ notice_id }
			description={ notice.description }
			isDismissible={ true }
			variant="error"
			onClose={ handleClose }
			onLoad={ handleLoad }
		>
			<Button
				href={ notice.button_link }
				variant="secondary"
				onClick={ handleClick }
			>
				{ notice.button_text }
			</Button>
		</Notice>
	);
}
