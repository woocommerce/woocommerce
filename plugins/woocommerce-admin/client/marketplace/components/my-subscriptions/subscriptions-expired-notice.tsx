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

export default function SubscriptionsExpiredNotice(): JSX.Element | null {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const notice = wccomSettings?.subscription_expired_notice || {};

	if ( ! wccomSettings.isConnected || ! notice?.description ) {
		return null;
	}

	const handleClose = () => {
		const data = { notice_id: 'woo-subscription-expired-notice' };
		apiFetch( {
			path: `/wc-admin/woo_subscription_notice_dissmiss/`,
			method: 'POST',
			data,
		} );
	};

	return (
		<Notice
			id="woo-subscription-expired-notice"
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
