/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useContext, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import {
	addNotice,
	connectProduct,
	removeNotice,
} from '../../../../utils/functions';
import { Subscription } from '../../types';
import { NoticeStatus } from '../../../../contexts/types';

interface ConnectProps {
	subscription: Subscription;
	onClose?: () => void;
	variant?: Button.ButtonVariant;
}

export default function ConnectButton( props: ConnectProps ) {
	const [ isConnecting, setIsConnecting ] = useState( false );
	const { loadSubscriptions } = useContext( SubscriptionsContext );

	const connect = () => {
		recordEvent( 'marketplace_product_connect_button_clicked', {
			product_zip_slug: props.subscription.zip_slug,
			product_id: props.subscription.product_id,
		} );

		setIsConnecting( true );
		removeNotice( props.subscription.product_key );
		connectProduct( props.subscription )
			.then( () => {
				loadSubscriptions( false ).then( () => {
					addNotice(
						props.subscription.product_key,
						sprintf(
							// translators: %s is the product name.
							__( '%s successfully connected.', 'woocommerce' ),
							props.subscription.product_name
						),
						NoticeStatus.Success
					);
					setIsConnecting( false );
					if ( props.onClose ) {
						props.onClose();
					}
				} );
			} )
			.catch( () => {
				addNotice(
					props.subscription.product_key,
					sprintf(
						// translators: %s is the product name.
						__( '%s couldn’t be connected.', 'woocommerce' ),
						props.subscription.product_name
					),
					NoticeStatus.Error,
					{
						actions: [
							{
								label: __( 'Try again', 'woocommerce' ),
								onClick: connect,
							},
						],
					}
				);
				setIsConnecting( false );
				if ( props.onClose ) {
					props.onClose();
				}
			} );
	};
	return (
		<Button
			onClick={ connect }
			variant={ props.variant ?? 'secondary' }
			isBusy={ isConnecting }
			disabled={ isConnecting }
		>
			{ __( 'Connect', 'woocommerce' ) }
		</Button>
	);
}
