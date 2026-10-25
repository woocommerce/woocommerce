/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Icon, closeSmall, link } from '@wordpress/icons';
import { speak } from '@wordpress/a11y';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { MARKETPLACE_MY_ACCOUNT_PATH } from '../constants';
import { connectUrl } from '../../utils/functions';
import HeaderAccountModal from '../header-account/header-account-modal';

const NOTICE_ID = 'woo-connected-account-notice';

/**
 * Whether the banner was dismissed during this page load.
 *
 * Module scope because the component unmounts whenever the merchant switches
 * marketplace tabs, while the admin settings that carry the server-side
 * dismissal are only refreshed on a full page load.
 */
let dismissedThisPageLoad = false;

interface MySubscriptionsAccountProps {
	/**
	 * Called after the banner is dismissed, so the parent can move focus
	 * somewhere sensible now that the dismiss button is gone.
	 */
	onDismiss?: () => void;
}

export default function MySubscriptionsAccount( {
	onDismiss,
}: MySubscriptionsAccountProps ): React.JSX.Element | null {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const isConnected = wccomSettings?.isConnected ?? false;
	const [ isDismissed, setIsDismissed ] = useState( dismissedThisPageLoad );
	const [ isDisconnectModalOpen, setIsDisconnectModalOpen ] =
		useState( false );

	if (
		! isConnected ||
		isDismissed ||
		wccomSettings?.show_connected_account_notice === false
	) {
		return null;
	}

	const userEmail = wccomSettings?.userEmail;

	const handleDismiss = () => {
		dismissedThisPageLoad = true;
		setIsDismissed( true );
		void apiFetch( {
			path: '/wc-admin/notice/dismiss',
			method: 'POST',
			data: {
				notice_id: NOTICE_ID,
				dismiss_notice_nonce: wccomSettings?.dismissNoticeNonce || '',
			},
		} );
		speak(
			__( 'Connected account notice dismissed.', 'woocommerce' ),
			'polite'
		);
		onDismiss?.();
	};

	return (
		<>
			<section className="woocommerce-marketplace__my-subscriptions__account">
				{ /* Rendered first so it is first in the tab order, matching where it sits visually. */ }
				<Button
					className="woocommerce-marketplace__my-subscriptions__account-dismiss"
					icon={ closeSmall }
					label={ __( 'Dismiss this notice', 'woocommerce' ) }
					onClick={ handleDismiss }
				/>
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
								'Your store is currently connected to <strong>%s</strong> account on WooCommerce.com. If you think this is a mistake, you can <disconnect>disconnect your account</disconnect> and connect it to your current WooCommerce.com account. Doing this will not affect WooCommerce or any related extensions running on your site.',
								'woocommerce'
							),
							userEmail
						),
						{
							strong: <strong />,
							disconnect: (
								<Button
									variant="link"
									onClick={ () =>
										setIsDisconnectModalOpen( true )
									}
								/>
							),
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
			{ isDisconnectModalOpen && (
				<HeaderAccountModal
					setIsModalOpen={ setIsDisconnectModalOpen }
					disconnectURL={ connectUrl( 'wc-addons' ) }
				/>
			) }
		</>
	);
}
