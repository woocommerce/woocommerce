/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { Button, Tooltip } from '@wordpress/components';
import { useContext } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { help } from '@wordpress/icons';

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
import {
	availableSubscriptionRow,
	installedSubscriptionRow,
} from './table/table-rows';
import { Subscription } from './types';

export default function MySubscriptions(): JSX.Element {
	const { subscriptions, isLoading } = useContext( SubscriptionsContext );
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

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

	const subscriptionsInstalled: Array< Subscription > = subscriptions.filter(
		( subscription: Subscription ) => subscription.local.installed
	);

	const subscriptionsAvailable: Array< Subscription > = subscriptions.filter(
		( subscription: Subscription ) =>
			! subscriptionsInstalled.includes( subscription )
	);

	if ( ! wccomSettings?.isConnected ) {
		return (
			<div className="woocommerce-marketplace__my-subscriptions--connect">
				<div className="woocommerce-marketplace__my-subscriptions__icon" />
				<h2 className="woocommerce-marketplace__my-subscriptions__header">
					{ __( 'Manage your subscriptions', 'woocommerce' ) }
				</h2>
				<p className="woocommerce-marketplace__my-subscriptions__description">
					{ __(
						'Connect your account to get updates, manage your subscriptions, and get seamless support. Once connected, your WooCommerce.com subscriptions will appear here.',
						'woocommerce'
					) }
				</p>
				<Button href={ wccomSettings?.connectURL } variant="primary">
					{ __( 'Connect Account', 'woocommerce' ) }
				</Button>
			</div>
		);
	}

	return (
		<div className="woocommerce-marketplace__my-subscriptions">
			<section>
				<h2 className="woocommerce-marketplace__my-subscriptions__header">
					{ __( 'Installed on this store', 'woocommerce' ) }
				</h2>
				<p className="woocommerce-marketplace__my-subscriptions__table-description">
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
				<InstalledSubscriptionsTable
					isLoading={ isLoading }
					rows={ subscriptionsInstalled.map( ( item ) => {
						return installedSubscriptionRow( item );
					} ) }
				/>
			</section>

			<section>
				<h2 className="woocommerce-marketplace__my-subscriptions__header">
					{ __( 'Not in use', 'woocommerce' ) }
				</h2>
				<p className="woocommerce-marketplace__my-subscriptions__table-description">
					{ __(
						'Your unused WooCommerce.com subscriptions.',
						'woocommerce'
					) }
				</p>
				<AvailableSubscriptionsTable
					isLoading={ isLoading }
					rows={ subscriptionsAvailable.map( ( item ) => {
						return availableSubscriptionRow( item );
					} ) }
				/>
			</section>

			<section>
				<h2 className="woocommerce-marketplace__my-subscriptions__header">
					{ __( 'Free to install', 'woocommerce' ) }
				</h2>
				<p className="woocommerce-marketplace__my-subscriptions__table-description">
					{ __(
						'Easily install your existing free to install WooCommerce.com subscriptions across sites.',
						'woocommerce'
					) }
				</p>
				<AvailableSubscriptionsTable
					isLoading={ isLoading }
					// TODO: fetch free and uninstalled subscriptions.
					rows={ [] }
				/>
			</section>
		</div>
	);
}
