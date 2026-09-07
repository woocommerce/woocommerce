/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useContext, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
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
 * Warns when a subscription's installed plugin won't update itself.
 *
 * Two different problems share this slot: the plugin's auto-update setting being off, which can be
 * fixed here, and the setting being on while something else holds the update back, which can't.
 *
 * Renders nothing for a healthy row, so the table stays uncluttered.
 */
export default function AutoUpdateStatus( props: {
	subscription: Subscription;
} ): React.JSX.Element | null {
	const { subscription } = props;
	const { loadSubscriptions } = useContext( SubscriptionsContext );
	const [ isEnabling, setIsEnabling ] = useState( false );

	const local = subscription.local;

	if ( ! local?.installed ) {
		return null;
	}

	function enableAutoUpdate() {
		recordEvent( 'marketplace_enable_auto_update_clicked', {
			product_id: subscription.product_id,
			product_zip_slug: subscription.zip_slug,
		} );

		setIsEnabling( true );
		removeNotice( subscription.product_key );

		setProductAutoUpdate( subscription, true )
			.then( () => loadSubscriptions( false ) )
			.then( () => {
				speak(
					sprintf(
						/* translators: %s is the product name. */
						__( 'Auto-updates enabled for %s.', 'woocommerce' ),
						subscription.product_name
					)
				);
			} )
			.catch( ( error: { data?: { message?: string } } ) => {
				// The endpoint answers with wp_send_json_error(), which nests the reason under data.
				const reason = error?.data?.message;

				addNotice(
					subscription.product_key,
					reason
						? sprintf(
								/* translators: %s is the reason the endpoint gave, a full sentence. */
								__(
									'Auto-updates could not be enabled. %s',
									'woocommerce'
								),
								reason
						  )
						: __(
								'Auto-updates could not be enabled.',
								'woocommerce'
						  ),
					NoticeStatus.Error
				);
			} )
			.finally( () => setIsEnabling( false ) );
	}

	if ( ! local.auto_update ) {
		const explanation = local.auto_update_manageable ? (
			<>
				{ __(
					'New versions will not install on their own, including security releases.',
					'woocommerce'
				) }{ ' ' }
				<Button
					variant="link"
					onClick={ enableAutoUpdate }
					isBusy={ isEnabling }
					disabled={ isEnabling }
				>
					{ __( 'Enable auto-updates', 'woocommerce' ) }
				</Button>
			</>
		) : (
			__(
				'New versions will not install on their own, including security releases. Auto-updates are controlled outside this screen.',
				'woocommerce'
			)
		);

		return (
			<StatusPopover
				text={ __( 'Auto-updates are off', 'woocommerce' ) }
				level={ StatusLevel.Warning }
				explanation={ explanation }
				explanationOnHover
			/>
		);
	}

	const blockers = getAutoUpdateBlockers( subscription );

	if ( blockers.length === 0 ) {
		return null;
	}

	return (
		<StatusPopover
			text={ __( 'Auto-updates blocked', 'woocommerce' ) }
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
