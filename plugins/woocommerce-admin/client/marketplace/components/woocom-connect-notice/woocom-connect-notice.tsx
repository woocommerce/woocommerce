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

export default function WoocomConnectNotice(): JSX.Element | null {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	if ( wccomSettings?.isConnected ) {
		return null;
	}

	return (
		<Notice
			id="woo-connect-notice"
			description={ __(
				'<strong>Connect your store to Woo.com</strong> to get updates and streamlined support for your subscriptions.',
				'woocommerce'
			) }
			isDismissible={ true }
			variant="error"
		>
			<Button href={ connectUrl() } variant="secondary">
				{ __( 'Connect', 'woocommerce' ) }
			</Button>
		</Notice>
	);
}
