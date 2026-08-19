/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useContext,
	useEffect,
	useState,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Icon, external } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

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
import { noticeStore } from '../../contexts/notice-store';
import { REFRESH_SUBSCRIPTIONS_NOTICE_ID } from '../../contexts/types';
import Notice from '../notice/notice';
import MySubscriptionsAccount from './my-subscriptions-account';

export default function MySubscriptions(): React.JSX.Element {
	const { subscriptions, isLoading } = useContext( SubscriptionsContext );
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	// The Refresh button reports its own failure, and it reruns the same request
	// this page-load notice describes. Showing both would state the same problem
	// twice, so the refresh notice — which is dismissible and current — wins.
	const hasRefreshNotice = useSelect(
		( select ) =>
			select( noticeStore )
				.notices()
				.some(
					( notice: { productKey: string } ) =>
						notice.productKey === REFRESH_SUBSCRIPTIONS_NOTICE_ID
				),
		[]
	);

	// Latch it: this notice was rendered from state captured at page load, so
	// once a refresh has reported on the same request it is stale for good.
	// Without the latch, dismissing the refresh notice would bring this
	// non-dismissible one straight back and dismissal would achieve nothing.
	const [ refreshHasReported, setRefreshHasReported ] = useState( false );

	useEffect( () => {
		if ( hasRefreshNotice ) {
			setRefreshHasReported( true );
		}
	}, [ hasRefreshNotice ] );

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

	const handleConnectNoticeClose = () => {
		const data = {
			notice_id: 'woo-connect-notice',
			dismiss_notice_nonce: wccomSettings?.dismissNoticeNonce || '',
		};
		void apiFetch( {
			path: `/wc-admin/notice/dismiss`,
			method: 'POST',
			data,
		} );
		localStorage.setItem(
			'wc-marketplaceNoticeClosed-woo-connect-notice',
			'false'
		);
	};

	if ( ! wccomSettings?.isConnected ) {
		const connectMessage = __(
			'Connect your WooCommerce.com account to get product updates, manage your subscriptions from your store admin, and get streamlined support.',
			'woocommerce'
		);

		const handleDisconnectNoticeClose = () => {
			const data = {
				notice_id: 'woo-disconnect-notice',
				dismiss_notice_nonce: wccomSettings?.dismissNoticeNonce || '',
			};
			void apiFetch( {
				path: `/wc-admin/notice/dismiss`,
				method: 'POST',
				data,
			} );
			localStorage.setItem(
				'wc-marketplaceNoticeClosed-woo-disconnect-notice',
				'false'
			);
		};

		return (
			<>
				{ wccomSettings?.disconnected_notice && (
					<Notice
						id={ 'woo-disconnect-notice' }
						description={ wccomSettings?.disconnected_notice }
						isDismissible={ true }
						variant="info"
						onClose={ handleDisconnectNoticeClose }
					/>
				) }
				<div className="woocommerce-marketplace__my-subscriptions--connect">
					<InstallModal />
					<div className="woocommerce-marketplace__my-subscriptions__icon" />
					<h2 className="woocommerce-marketplace__my-subscriptions__header">
						{ __(
							'Connect your WooCommerce.com account',
							'woocommerce'
						) }
					</h2>
					<p className="woocommerce-marketplace__my-subscriptions__description">
						{ connectMessage }
					</p>
					<Button href={ connectUrl() } variant="primary">
						{ __( 'Connect', 'woocommerce' ) }
					</Button>
				</div>
			</>
		);
	}

	return (
		<>
			{ wccomSettings?.connected_notice && (
				<Notice
					id={ 'woo-connect-notice' }
					description={ wccomSettings?.connected_notice }
					isDismissible={ true }
					variant="success"
					onClose={ handleConnectNoticeClose }
				/>
			) }

			{ ! wccomSettings?.has_host_plan_orders &&
				wccomSettings?.connection_url_notice && (
					<Notice
						id={ 'woo-connection-url-notice' }
						description={ wccomSettings?.connection_url_notice }
						isDismissible={ false }
						variant="error"
					>
						<Button
							href={ connectUrl( 'wc-admin', true ) }
							variant="secondary"
						>
							{ __( 'Reconnect', 'woocommerce' ) }
						</Button>
					</Notice>
				) }

			{ wccomSettings?.maybe_deleted_connection && (
				<Notice
					id={ 'woo-deleted-connection-notice' }
					description={ wccomSettings?.maybe_deleted_connection }
					isDismissible={ false }
					variant="error"
				/>
			) }

			{ wccomSettings?.api_error_notice && ! refreshHasReported && (
				<Notice
					id={ 'woo-subscriptions-api-error-notice' }
					description={ wccomSettings?.api_error_notice }
					isDismissible={ false }
					variant="error"
				/>
			) }

			<div className="woocommerce-marketplace__my-subscriptions">
				<InstallModal />
				<section className="woocommerce-marketplace__my-subscriptions__notices">
					<Notices />
				</section>
				<MySubscriptionsAccount />
				<section className="woocommerce-marketplace__my-subscriptions-section woocommerce-marketplace__my-subscriptions__installed">
					<header className="woocommerce-marketplace__my-subscriptions__header">
						<div className="woocommerce-marketplace__my-subscriptions__header-content">
							<h2 className="woocommerce-marketplace__my-subscriptions__heading">
								{ __(
									'Installed on this store',
									'woocommerce'
								) }
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
		</>
	);
}
