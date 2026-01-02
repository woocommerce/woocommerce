/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useContext,
	useState,
	useEffect,
} from '@wordpress/element';
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
import Notice from '../notice/notice';
import MySubscriptionsAccount from './my-subscriptions-account';

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

	const handleConnectNoticeClose = () => {
		const data = {
			notice_id: 'woo-connect-notice',
			dismiss_notice_nonce: wccomSettings?.dismissNoticeNonce || '',
		};
		apiFetch( {
			path: `/wc-admin/notice/dismiss`,
			method: 'POST',
			data,
		} );
		localStorage.setItem(
			'wc-marketplaceNoticeClosed-woo-connect-notice',
			'false'
		);
	};

	const [ isConnecting, setIsConnecting ] = useState( false );

	/**
	 * Attempts to connect to WooCommerce.com via Jetpack.
	 * Returns true if successful, false if it failed (and should fall back to direct connect).
	 */
	const tryWccomConnectViaJetpack = async (): Promise< boolean > => {
		try {
			const response: { success: boolean } = await apiFetch( {
				path: '/wc-admin/onboarding/plugins/wccom-connect-via-jetpack',
				method: 'POST',
			} );

			if ( response?.success ) {
				// Connection successful, reload the page to show connected state.
				window.location.reload();
				return true;
			}
		} catch ( error ) {
			// Failed to connect via Jetpack, will fall back to direct connect.
		}
		return false;
	};

	/**
	 * Redirects to Jetpack authorization flow.
	 */
	const redirectToJetpackAuth = async (): Promise< void > => {
		const returnUrl = new URL( window.location.href );
		returnUrl.searchParams.set( 'page', 'wc-admin' );
		returnUrl.searchParams.set( 'tab', 'my-subscriptions' );
		returnUrl.searchParams.set( 'path', '/extensions' );
		returnUrl.searchParams.set( 'jp_wccom_connect', '1' );

		const response: {
			success: boolean;
			errors: string[];
			url: string;
		} = await apiFetch( {
			path: `/wc-admin/onboarding/plugins/jetpack-authorization-url?redirect_url=${ encodeURIComponent(
				returnUrl.toString()
			) }&from=woocommerce-onboarding`,
			method: 'GET',
		} );

		if ( response?.url ) {
			window.location.href = response.url;
		}
	};

	/**
	 * Handles the Connect button click.
	 * Flow:
	 * 1. If user is Jetpack connected, try to connect via Jetpack first.
	 * 2. If site is Jetpack connected but user is not, redirect to Jetpack user auth.
	 * 3. If site is not Jetpack connected, redirect to full Jetpack auth.
	 * 4. On any failure, fall back to direct WCCOM connect.
	 */
	const handleConnect = async () => {
		setIsConnecting( true );
		try {
			if ( wccomSettings?.isJetpackUserConnected ) {
				// User is Jetpack connected, try to connect via Jetpack.
				const success = await tryWccomConnectViaJetpack();
				if ( success ) {
					return;
				}
				// Fall back to direct WCCOM connect if Jetpack connect failed.
				window.location.href = connectUrl();
				return;
			}

			// User is not Jetpack connected, redirect to Jetpack auth.
			// This works for both cases: site connected (user auth only) or site not connected (full auth).
			await redirectToJetpackAuth();
		} catch ( error ) {
			// Fall back to direct WCCOM connect on any error.
			window.location.href = connectUrl();
		}
	};

	// Handle return from Jetpack authorization.
	useEffect( () => {
		const params = new URLSearchParams( window.location.search );
		if (
			params.get( 'jp_wccom_connect' ) === '1' &&
			wccomSettings?.isJetpackUserConnected &&
			! wccomSettings?.isConnected
		) {
			// Returned from Jetpack auth, now try to connect via Jetpack.
			setIsConnecting( true );
			tryWccomConnectViaJetpack().then( ( success ) => {
				if ( ! success ) {
					// Fall back to direct WCCOM connect.
					window.location.href = connectUrl();
				}
			} );
		}
	}, [ wccomSettings?.isJetpackUserConnected, wccomSettings?.isConnected ] );

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
			apiFetch( {
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
					<div className="woocommerce-marketplace__my-subscriptions__buttons">
						<Button
							onClick={ handleConnect }
							variant="primary"
							isBusy={ isConnecting }
							disabled={ isConnecting }
						>
							{ __( 'Connect', 'woocommerce' ) }
						</Button>
					</div>
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
