/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useContext } from '@wordpress/element';
import { Icon, external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../../utils/admin-settings';
import { SubscriptionsContext } from '../../contexts/subscriptions-context';
import './my-subscriptions.scss';
import {
	AvailableSubscriptionsTable,
	InstalledSubscriptionsTable,
} from './table/table';
import { subscriptionRow } from './table/table-rows';
import { Subscription } from './types';
import { RefreshButton } from './table/actions/refresh-button';
import Notices from './notices';
import InstallModal from './table/actions/install-modal';
import { connectUrl } from '../../utils/functions';

export default function MySubscriptions(): JSX.Element {
	const { subscriptions, isLoading } = useContext( SubscriptionsContext );
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	const installedTableDescription = createInterpolateElement(
		__(
			'WooCommerce.com extensions and themes installed on this store. To see all your subscriptions go to <a>your account<custom_icon /></a> on WooCommerce.com.',
			'woocommerce'
		),
		{
			a: (
				<a
					href="https://woocommerce.com/my-account/my-subscriptions"
					target="_blank"
					rel="nofollow noopener noreferrer"
				>
					your account
				</a>
			),
			custom_icon: <Icon icon={ external } size={ 12 } />,
		}
	);

	const subscriptionsInstalled: Array< Subscription > = subscriptions.filter(
		( subscription: Subscription ) => subscription.subscription_installed
	);

	const subscriptionsAvailable: Array< Subscription > = subscriptions.filter(
		( subscription: Subscription ) =>
			! subscription.subscription_installed &&
			wccomSettings?.wooUpdateManagerPluginSlug !==
				subscription.product_slug &&
			! subscription.maxed // no more connections allowed for the subscription so it's no longer "available to use"
	);

	if ( ! wccomSettings?.isConnected ) {
		const connectMessage = __(
			"Connect your store to WooCommerce.com using the WooCommerce.com Update Manager. Once connected, you'll be able to manage your subscriptions, receive product updates, and access streamlined support from this screen.",
			'woocommerce'
		);

		return (
			<div className="woocommerce-marketplace__my-subscriptions--connect">
				<InstallModal />
				<div className="woocommerce-marketplace__my-subscriptions__icon" />
				<h2 className="woocommerce-marketplace__my-subscriptions__header">
					{ __( 'Manage your subscriptions', 'woocommerce' ) }
				</h2>
				<p className="woocommerce-marketplace__my-subscriptions__description">
					{ connectMessage }
				</p>
				<Button href={ connectUrl() } variant="primary">
					{ __( 'Connect your store', 'woocommerce' ) }
				</Button>
			</div>
		);
	}

	return (
		<div className="woocommerce-marketplace__my-subscriptions">
			<InstallModal />
			<section className="woocommerce-marketplace__my-subscriptions__notices">
				<Notices />
			</section>
			<section className="woocommerce-marketplace__my-subscriptions-section woocommerce-marketplace__my-subscriptions__installed">
				<header className="woocommerce-marketplace__my-subscriptions__header">
					<div className="woocommerce-marketplace__my-subscriptions__header-content">
						<h2 className="woocommerce-marketplace__my-subscriptions__heading">
							{ __( 'Installed on this store', 'woocommerce' ) }
						</h2>
						<p className="woocommerce-marketplace__my-subscriptions__table-description">
							{ installedTableDescription }
						</p>
					</div>
					<div className="woocommerce-marketplace__my-subscriptions__header-refresh">
						<RefreshButton />
					</div>
				</header>
				<div className="woocommerce-marketplace__my-subscriptions__table-wrapper">
					<InstalledSubscriptionsTable
						isLoading={ isLoading }
						rows={ subscriptionsInstalled.map( ( item ) => {
							return subscriptionRow( item, 'installed' );
						} ) }
					/>
				</div>
			</section>
			{ subscriptionsAvailable.length > 0 && (
				<section className="woocommerce-marketplace__my-subscriptions-section woocommerce-marketplace__my-subscriptions__available">
					<h2 className="woocommerce-marketplace__my-subscriptions__heading">
						{ __( 'Available to use', 'woocommerce' ) }
					</h2>
					<p className="woocommerce-marketplace__my-subscriptions__table-description">
						{ __(
							"WooCommerce.com subscriptions you haven't used yet.",
							'woocommerce'
						) }
					</p>
					<div className="woocommerce-marketplace__my-subscriptions__table-wrapper">
						<AvailableSubscriptionsTable
							isLoading={ isLoading }
							rows={ subscriptionsAvailable.map( ( item ) => {
								return subscriptionRow( item, 'available' );
							} ) }
						/>
					</div>
				</section>
			) }
		</div>
	);
}
