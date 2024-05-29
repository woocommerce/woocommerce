/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export interface SubscriptionsExpiredExpiringNoticeProps {
	type: string;
}

export default function SubscriptionsExpiredExpiringNotice(
	props: SubscriptionsExpiredExpiringNoticeProps
): JSX.Element | null {
	const { type } = props;
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	let notice = null;
	let notice_id = '';
	const dismiss_notice_nonce = wccomSettings?.dismissNoticeNonce || '';

	if ( type === 'expired' ) {
		notice = wccomSettings?.subscription_expired_notice || {};
		notice_id = 'woo-subscription-expired-notice';
	} else if ( type === 'expiring' ) {
		notice = wccomSettings?.subscription_expiring_notice || {};
		notice_id = 'woo-subscription-expiring-notice';
	} else {
		return null;
	}

	if ( ! wccomSettings.isConnected || ! notice?.description ) {
		return null;
	}

	const handleClose = () => {
		const data = { notice_id, dismiss_notice_nonce };
		apiFetch( {
			path: `/wc-admin/notice/dismiss`,
			method: 'POST',
			data,
		} );
	};

	return (
		<Notice
			id={ notice_id }
			description={ notice.description }
			isDismissible={ true }
			variant="error"
			onClose={ handleClose }
		>
			<Button href={ notice.button_link } variant="secondary">
				{ notice.button_text }
			</Button>
		</Notice>
	);
}
