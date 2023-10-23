/**
 * External dependencies
 */
import { Button, ButtonGroup, Icon, Modal } from '@wordpress/components';
import { useContext, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useDispatch } from '@wordpress/data';
import { activateProduct } from '~/marketplace/utils/functions';
import { SubscriptionsContext } from '../../../../contexts/subscriptions-context';
import { Subscription } from '../../types';

interface ActivateProps {
	subscription: Subscription;
	onClose: () => void;
}

export default function ActivateModal( props: ActivateProps ) {
	const [ isConnecting, setIsConnecting ] = useState( false );
	const { createWarningNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const { loadSubscriptions } = useContext( SubscriptionsContext );

	const connect = () => {
		setIsConnecting( true );
		activateProduct( props.subscription.product_key )
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
					props.onClose();
				} )
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
				props.onClose();
			} );
	};
	return (
		<Modal
			title={ __( 'Connect to update', 'woocommerce' ) }
			onRequestClose={ props.onClose }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			<p className="woocommerce-marketplace__header-account-modal-text">
				{ sprintf(
					// translators: %s is the product version number (e.g. 1.0.2).
					__(
						'Version %s is available. To enable this update you need to connect your subscription to this store.',
						'woocommerce'
					),
					props.subscription.version
				) }
			</p>
			<ButtonGroup className="woocommerce-marketplace__header-account-modal-button-group">
				<Button
					variant="tertiary"
					onClick={ props.onClose }
					className="woocommerce-marketplace__header-account-modal-button"
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ connect }
					isBusy={ isConnecting }
					disabled={ isConnecting }
					className="woocommerce-marketplace__header-account-modal-button"
				>
					{ __( 'Connect', 'woocommerce' ) }
				</Button>
			</ButtonGroup>
		</Modal>
	);
}
