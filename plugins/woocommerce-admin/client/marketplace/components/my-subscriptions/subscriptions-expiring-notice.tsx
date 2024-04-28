/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { connectUrl } from '~/marketplace/utils/functions';
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export default function SubscriptionsExpiringNotice(): JSX.Element | null {

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const notice = wccomSettings?.subscription_expiring_notice || {};

	if (!wccomSettings.isConnected || !notice?.description) {
		return null;
	}

	return (
		<Notice
			id="woo-subscription-expiring-notice"
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
