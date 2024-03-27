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

	const localWooPluginsCount = ( wccomSettings?.localWooPlugins || [] )
		.length;
	let description = '';

	if ( localWooPluginsCount > 0 ) {
		description = description.concat(
			__(
				'Your store might be at risk as you are running old versions of Woo plugins.',
				'woocommerce'
			)
		);

		description = description.concat( ' ' );
	}

	description = description.concat(
		__(
			'<strong>Connect your store to Woo.com</strong> to get updates and streamlined support for your subscriptions.',
			'woocommerce'
		)
	);

	return (
		<Notice
			id="woo-connect-notice"
			description={ description }
			isDismissible={ true }
			variant="error"
		>
			<Button href={ connectUrl() } variant="secondary">
				{ __( 'Connect', 'woocommerce' ) }
			</Button>
		</Notice>
	);
}
