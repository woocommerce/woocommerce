/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { getNewPath } from '@woocommerce/navigation';
import { help } from '@wordpress/icons';
import { Table } from '@woocommerce/components';
import { useContext, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../../utils/admin-settings';
import { Subscription } from './types';
import './my-subscriptions.scss';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { fetchSubscriptions } from '../../../marketplace/utils/functions';

export default function MySubscriptions(): JSX.Element {
	const [ subscriptions, setSubscriptions ] = useState<
		Array< Subscription >
	>( [] );
	const { setIsLoading } = useContext( MarketplaceContext );

	// Get the content for this screen
	useEffect( () => {
		setIsLoading( true );

		fetchSubscriptions()
			.then( ( subscriptionResponse ) => {
				setSubscriptions( subscriptionResponse );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	const updateConnectionUrl = getNewPath(
		{
			page: 'wc-addons',
			section: 'helper',
			filter: 'all',
			'wc-helper-refresh': 1,
			'wc-helper-nonce': getAdminSetting( 'wc_helper_nonces' ).refresh,
			'redirect-to-wc-admin': 1,
		},
		''
	);
	const updateConnectionHTML = sprintf(
		// translators: %s is a link to the update connection page.
		__(
			'If you don\'t see your subscription, try <a href="%s">updating</a> your connection.',
			'woocommerce'
		),
		updateConnectionUrl
	);

	const tableHeadersInstalled = [
		{
			key: 'name',
			label: __( 'Name', 'woocommerce' ),
		},
		{
			key: 'status',
			label: __( 'Status', 'woocommerce' ),
		},
		{
			key: 'expiry',
			label: __( 'Expiry/Renewal date', 'woocommerce' ),
		},
		{
			key: 'autoRenew',
			label: __( 'Auto-renew', 'woocommerce' ),
		},
		{
			key: 'version',
			label: __( 'Version', 'woocommerce' ),
			isNumeric: true,
		},
		{
			key: 'activated',
			label: __( 'Activated', 'woocommerce' ),
		},
		{
			key: 'actions',
			label: __( 'Actions', 'woocommerce' ),
		},
	];
	const subscriptionsInstalled: Array< Subscription > = subscriptions.filter(
		( subscription: Subscription ) => subscription.local.installed
	);

	const tableHeadersAvailable = [
		{
			key: 'name',
			label: __( 'Name', 'woocommerce' ),
		},
		{
			key: 'status',
			label: __( 'Status', 'woocommerce' ),
		},
		{
			key: 'expiry',
			label: __( 'Expiry/Renewal date', 'woocommerce' ),
		},
		{
			key: 'autoRenew',
			label: __( 'Auto-renew', 'woocommerce' ),
		},
		{
			key: 'version',
			label: __( 'Version', 'woocommerce' ),
			isNumeric: true,
		},
		{
			key: 'install',
			label: __( 'Install', 'woocommerce' ),
		},
		{
			key: 'actions',
			label: __( 'Actions', 'woocommerce' ),
		},
	];
	const subscriptionsAvailable: Array< Subscription > = subscriptions.filter(
		( subscription: Subscription ) =>
			! subscriptionsInstalled.includes( subscription )
	);

	const getStatus = ( subscription: Subscription ): string => {
		// TODO add statuses for subscriptions
		if ( subscription.product_key === '' ) {
			return __( 'Not found', 'woocommerce' );
		} else if ( subscription.expired ) {
			return __( 'Expired', 'woocommerce' );
		} else if ( subscription.active ) {
			return __( 'Active', 'woocommerce' );
		}
		return __( 'Inactive', 'woocommerce' );
	};

	const getVersion = ( subscription: Subscription ): string => {
		if ( subscription.local.version === subscription.version ) {
			return subscription.local.version;
		}
		if ( subscription.local.version && subscription.version ) {
			return subscription.local.version + ' > ' + subscription.version;
		}
		if ( subscription.version ) {
			return subscription.version;
		}
		if ( subscription.local.version ) {
			return subscription.local.version;
		}
		return '';
	};

	return (
		<div className="woocommerce-marketplace__my-subscriptions">
			<section>
				<h2>{ __( 'Installed on this store', 'woocommerce' ) }</h2>
				<p>
					<span
						dangerouslySetInnerHTML={ {
							__html: updateConnectionHTML,
						} }
					/>
					<Tooltip
						text={
							<>
								<h3>
									{ __(
										"Still don't see your subscription?",
										'woocommerce'
									) }
								</h3>
								<p
									dangerouslySetInnerHTML={ {
										__html: __(
											'To see all your subscriptions go to <a href="https://woocommerce.com/my-account/" target="_blank" class="woocommerce-marketplace__my-subscriptions__tooltip-external-link">your account</a> on WooCommerce.com.',
											'woocommerce'
										),
									} }
								/>
							</>
						}
					>
						<Button
							icon={ help }
							iconSize={ 20 }
							isSmall={ true }
							label={ __( 'Help', 'woocommerce' ) }
						/>
					</Tooltip>
				</p>
				<Table
					headers={ tableHeadersInstalled }
					rows={ subscriptionsInstalled.map( ( item ) => {
						return [
							{ display: item.product_name },
							{ display: getStatus( item ) },
							{ display: item.expires },
							{ display: item.autorenew ? 'true' : 'false' },
							{ display: getVersion( item ) },
							{ display: item.active ? 'true' : 'false' },
							{ display: '...' },
						];
					} ) }
				/>
			</section>

			<section>
				<h2>{ __( 'Available', 'woocommerce' ) }</h2>
				<p>
					{ __(
						'Your unused and free WooCommerce.com subscriptions.',
						'woocommerce'
					) }
				</p>
				<Table
					headers={ tableHeadersAvailable }
					rows={ subscriptionsAvailable.map( ( item ) => {
						return [
							{ display: item.product_name },
							{ display: getStatus( item ) },
							{ display: item.expires },
							{ display: item.autorenew ? 'true' : 'false' },
							{ display: getVersion( item ) },
							{ display: '...' },
							{ display: '...' },
						];
					} ) }
				/>
			</section>
		</div>
	);
}
