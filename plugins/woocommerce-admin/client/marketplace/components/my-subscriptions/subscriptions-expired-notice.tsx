/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { connectUrl } from '~/marketplace/utils/functions';
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export default function SubscriptionsExpiredNotice(): JSX.Element | null {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const notice = wccomSettings?.subscription_expired_notice || {};

	if (!wccomSettings.isConnected || !notice?.description) {
		return null;
	}
	return (
		<Notice
			id="woo-subscription-expired-notice"
			description={ notice.description }
			isDismissible={ true }
			variant="error"
		>
			<Button href={ notice.button_link } variant="secondary">
				{ __( notice.button_text, 'woocommerce' ) }
			</Button>
		</Notice>
	);
}
