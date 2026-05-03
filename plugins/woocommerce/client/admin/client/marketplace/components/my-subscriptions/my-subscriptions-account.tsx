/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Icon, link } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { MARKETPLACE_MY_ACCOUNT_PATH } from '../constants';

export default function MySubscriptionsAccount(): JSX.Element | null {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const isConnected = wccomSettings?.isConnected ?? false;

	if ( ! isConnected ) {
		return null;
	}

	const userEmail = wccomSettings?.userEmail;

	return (
		<section className="woocommerce-marketplace__my-subscriptions__account">
			<h2 className="woocommerce-marketplace__my-subscriptions__account-header">
				<Icon icon={ link } size={ 24 } />
				{ sprintf(
					// translators: %s is user email
					__( 'Connected to %s', 'woocommerce' ),
					userEmail
				) }
			</h2>
			<p className="woocommerce-marketplace__my-subscriptions__account-content">
				{ createInterpolateElement(
					sprintf(
						// translators: %s is user email
						__(
							'Your store is currently connected to <strong>%s</strong> account on WooCommerce.com. If you think this is a mistake, you can disconnect your account and connect it to your current WooCommerce.com account. Doing this will not affect WooCommerce or any related extensions running on your site.',
							'woocommerce'
						),
						userEmail
					),
					{
						strong: <strong />,
					}
				) }
			</p>
			<div className="woocommerce-marketplace__my-subscriptions__account-actions">
				<Button
					variant="secondary"
					href={ MARKETPLACE_MY_ACCOUNT_PATH }
					target="_blank"
				>
					{ __( 'View account', 'woocommerce' ) }
				</Button>
			</div>
		</section>
	);
}
