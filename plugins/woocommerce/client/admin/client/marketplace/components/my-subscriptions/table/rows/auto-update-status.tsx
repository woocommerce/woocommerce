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

/**
 * Warns that a subscription's installed plugin won't update itself, and offers to turn
 * auto-updates on where that is possible.
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

	// Themes have their own auto-update option, which this screen doesn't manage.
	if ( ! local?.installed || local.type !== 'plugin' || local.auto_update ) {
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
			.catch( ( error: { message?: string } ) => {
				addNotice(
					subscription.product_key,
					error?.message ??
						__(
							'Auto-updates could not be enabled.',
							'woocommerce'
						),
					NoticeStatus.Error
				);
			} )
			.finally( () => setIsEnabling( false ) );
	}

	const explanation = local.auto_update_manageable ? (
		<>
			{ __(
				'This extension will not install new versions on its own, including security releases.',
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
			'This extension will not install new versions on its own, including security releases. Auto-updates for it are controlled outside this screen.',
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
