/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useContext, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SubscriptionsContext } from '~/marketplace/contexts/subscriptions-context';
import { connectProduct } from '~/marketplace/utils/functions';
import { Subscription } from '../../types';

interface ConnectProps {
	subscription: Subscription;
	onClose?: () => void;
	variant?: Button.ButtonVariant;
}

export default function ConnectButton( props: ConnectProps ) {
	const [ isConnecting, setIsConnecting ] = useState( false );
	const { createWarningNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const { loadSubscriptions } = useContext( SubscriptionsContext );

	const connect = () => {
		setIsConnecting( true );
		connectProduct( props.subscription.product_key )
			.then( () => {
				loadSubscriptions( false ).then( () => {
					createSuccessNotice(
						sprintf(
							// translators: %s is the product name.
							__( '%s successfully connected.', 'woocommerce' ),
							props.subscription.product_name
						),
						{
							icon: <Icon icon="yes" />,
						}
					);
					setIsConnecting( false );
					if ( props.onClose ) {
						props.onClose();
					}
				} );
			} )
			.catch( () => {
				createWarningNotice(
					sprintf(
						// translators: %s is the product name.
						__( '%s couldn’t be connected.', 'woocommerce' ),
						props.subscription.product_name
					),
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
