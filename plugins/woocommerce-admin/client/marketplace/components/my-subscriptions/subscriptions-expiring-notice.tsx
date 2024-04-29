/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from "@wordpress/api-fetch";

/**
 * Internal dependencies
 */
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export default function SubscriptionsExpiringNotice(): JSX.Element | null {

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const notice = wccomSettings?.subscription_expiring_notice || {};

	if (!wccomSettings.isConnected || !notice?.description) {
		return null;
	}

	const handleClose = () => {
		const data = {notice_id:'woo-subscription-expiring-notice'}
		apiFetch( {
			path: `/wc-admin/woo_subscription_notice_dissmiss/`,
			method: 'POST',
			data,
		} )
	};

	return (
		<Notice
			id="woo-subscription-expiring-notice"
			description={ notice.description }
			isDismissible={ true }
			variant="error"
			onClose={ handleClose }
		>
			<Button href={ notice.button_link } variant="secondary">
				{ __( notice.button_text, 'woocommerce' ) }
			</Button>
		</Notice>
	);
}
