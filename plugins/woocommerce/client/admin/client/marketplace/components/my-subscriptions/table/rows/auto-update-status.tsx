/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useContext, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { Icon, info } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import RefreshIcon from '../../../../assets/images/refresh.svg';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { NoticeStatus } from '../../../../contexts/types';
import {
	addNotice,
	removeNotice,
	setProductAutoUpdate,
} from '../../../../utils/functions';
import { StatusLevel, Subscription } from '../../types';
import StatusPopover from './status-popover';
import { getAdminSetting } from '../../../../../utils/admin-settings';

/**
 * Reasons a product won't auto-update even though the plugin's own auto-update setting is on.
 *
 * Every reason is about WooCommerce.com delivering the update, so none of them applies to a copy
 * installed from WordPress.org: core updates that one on its own.
 *
 * Whether WordPress runs automatic updates at all is deliberately not one of them: that is a
 * site-wide decision, and repeating it on every row says nothing about the product.
 */
export function getAutoUpdateBlockers( subscription: Subscription ): string[] {
	const blockers: string[] = [];

	if ( ! subscription.local?.updates_from_wccom ) {
		return blockers;
	}

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	if ( ! wccomSettings?.wooUpdateManagerActive ) {
		blockers.push(
			__(
				'WooCommerce.com Update Manager is not active, and it delivers these updates.',
				'woocommerce'
			)
		);
	}

	if ( subscription.product_key === '' ) {
		blockers.push(
			__( 'There is no subscription for it.', 'woocommerce' )
		);

		return blockers;
	}

	if ( subscription.expired && ! subscription.lifetime ) {
		blockers.push( __( 'The subscription has expired.', 'woocommerce' ) );
	}

	if ( ! subscription.active ) {
		blockers.push(
			__(
				'The subscription is not connected to this store.',
				'woocommerce'
			)
		);
	}

	return blockers;
}

/**
 * The "Automatic updates" cell of an installed row.
 *
 * Offers to turn the plugin's or theme's auto-update setting on or off. When the setting is on
 * but something else holds the update back, shows "Blocked" with the reasons instead, since none
 * of them can be fixed here. A setting that can't be changed from here is shown as plain text.
 */
export default function AutoUpdateStatus( props: {
	subscription: Subscription;
} ): React.JSX.Element | null {
	const { subscription } = props;
	const { loadSubscriptions } = useContext( SubscriptionsContext );
	const [ isSaving, setIsSaving ] = useState( false );

	const local = subscription.local;

	if ( ! local?.installed ) {
		return null;
	}

	function setAutoUpdate( enabled: boolean ) {
		recordEvent(
			enabled
				? 'marketplace_enable_auto_update_clicked'
				: 'marketplace_disable_auto_update_clicked',
			{
				product_id: subscription.product_id,
				product_zip_slug: subscription.zip_slug,
			}
		);

		setIsSaving( true );
		removeNotice( subscription.product_key );

		setProductAutoUpdate( subscription, enabled )
			.then( () => {
				const announcement = enabled
					? /* translators: %s is the product name. */
					  __( 'Auto-updates enabled for %s.', 'woocommerce' )
					: /* translators: %s is the product name. */
					  __( 'Auto-updates disabled for %s.', 'woocommerce' );

				speak( sprintf( announcement, subscription.product_name ) );

				// The setting is saved by now. A failed refresh only leaves the row stale, so it
				// gets its own notice rather than the toggle failure below.
				return loadSubscriptions( false ).catch( () =>
					addNotice(
						subscription.product_key,
						__(
							'The setting was saved, but the list could not be refreshed. Reload the page to see the change.',
							'woocommerce'
						),
						NoticeStatus.Error
					)
				);
			} )
			.catch( ( error: { data?: { message?: string } } ) => {
				const failure = enabled
					? __( 'Auto-updates could not be enabled.', 'woocommerce' )
					: __(
							'Auto-updates could not be disabled.',
							'woocommerce'
					  );

				// The endpoint answers with wp_send_json_error(), which nests the reason under data.
				const reason = error?.data?.message;

				addNotice(
					subscription.product_key,
					reason ? `${ failure } ${ reason }` : failure,
					NoticeStatus.Error
				);
			} )
			.finally( () => setIsSaving( false ) );
	}

	const blockers = local.auto_update
		? getAutoUpdateBlockers( subscription )
		: [];

	if ( blockers.length > 0 ) {
		return (
			<StatusPopover
				icon={ <Icon icon={ info } size={ 16 } /> }
				text={ __( 'Blocked', 'woocommerce' ) }
				level={ StatusLevel.Error }
				explanation={
					<>
						<p>
							{ __(
								'Auto-updates are on, but it will not update because:',
								'woocommerce'
							) }
						</p>
						<ul className="woocommerce-marketplace__my-subscriptions__auto-update-blockers">
							{ blockers.map( ( blocker ) => (
								<li key={ blocker }>{ blocker }</li>
							) ) }
						</ul>
					</>
				}
				explanationOnHover
			/>
		);
	}

	if ( ! local.auto_update_manageable ) {
		return (
			<StatusPopover
				text={
					local.auto_update
						? __( 'On', 'woocommerce' )
						: __( 'Off', 'woocommerce' )
				}
				level={ StatusLevel.Info }
				explanation={ __(
					'Auto-updates for this product are controlled outside this screen.',
					'woocommerce'
				) }
				explanationOnHover
			/>
		);
	}

	if ( isSaving ) {
		return (
			<Button variant="link" disabled accessibleWhenDisabled>
				<img
					src={ RefreshIcon }
					alt=""
					className="woocommerce-marketplace__my-subscriptions__auto-updates-saving-icon"
				/>
				{ local.auto_update
					? __( 'Disabling…', 'woocommerce' )
					: __( 'Enabling…', 'woocommerce' ) }
			</Button>
		);
	}

	return (
		<Button
			variant="link"
			onClick={ () => setAutoUpdate( ! local.auto_update ) }
		>
			{ local.auto_update
				? __( 'Disable auto-updates', 'woocommerce' )
				: __( 'Enable auto-updates', 'woocommerce' ) }
		</Button>
	);
}
