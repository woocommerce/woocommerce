/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, select } from '@wordpress/data';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { useFulfillmentContext } from '../../context/fulfillment-context';
import { store as FulfillmentStore } from '../../data/store';
import { useFulfillmentDrawerContext } from '../../context/drawer-context';
import CustomerNotificationBox from '../customer-notification-form';
import { refreshOrderFulfillmentStatus } from '../../utils/fulfillment-utils';

export default function RemoveButton( {
	setError,
}: {
	setError: ( message: string | null ) => void;
} ) {
	const { setIsEditing, setOpenSection } = useFulfillmentDrawerContext();
	const { order, fulfillment, notifyCustomer } = useFulfillmentContext();
	const [ isExecuting, setIsExecuting ] = useState< boolean >( false );
	const { deleteFulfillment } = useDispatch( FulfillmentStore );

	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	const handleDeleteFulfillment = async () => {
		setError( null );
		if ( ! fulfillment || ! fulfillment.id || ! order || ! order.id ) {
			return;
		}
		setIsExecuting( true );
		await deleteFulfillment( order.id, fulfillment.id, notifyCustomer );
		const error = select( FulfillmentStore ).getError( order.id );
		if ( error ) {
			setError( error );
		} else {
			refreshOrderFulfillmentStatus( order.id );
			setOpenSection( 'order' );
			setIsEditing( false );
		}
		setIsExecuting( false );
	};

	const handleRemoveButtonClick = ( event: React.MouseEvent ) => {
		event.stopPropagation();
		event.preventDefault();
		if ( ! fulfillment || isExecuting ) {
			return;
		}

		if ( fulfillment.is_fulfilled ) {
			openModal();
		} else {
			handleDeleteFulfillment();
		}
	};

	return (
		<>
			<Button
				variant="secondary"
				onClick={ handleRemoveButtonClick }
				isBusy={ isExecuting }
				__next40pxDefaultSize
			>
				{ __( 'Remove', 'woocommerce' ) }
			</Button>
			{ isOpen && (
				<Modal
					title={ __( 'Remove fulfillment', 'woocommerce' ) }
					onRequestClose={ closeModal }
					size="medium"
					isDismissible={ false }
					className="woocommerce-fulfillment-modal"
				>
					<p className="woocommerce-fulfillment-modal-text">
						{ __(
							'Are you sure you want to remove this fulfillment?',
							'woocommerce'
						) }
					</p>
					<CustomerNotificationBox type="remove" />
					<div className="woocommerce-fulfillment-modal-actions">
						<Button
							variant="link"
							onClick={ closeModal }
							__next40pxDefaultSize
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ () => {
								handleDeleteFulfillment();
								closeModal();
							} }
							isBusy={ isExecuting }
							__next40pxDefaultSize
						>
							{ __( 'Remove fulfillment', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
}
