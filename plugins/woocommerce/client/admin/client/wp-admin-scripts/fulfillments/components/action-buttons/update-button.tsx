/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, select } from '@wordpress/data';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { useFulfillmentContext } from '../../context/fulfillment-context';
import { store as FulfillmentStore } from '../../data/store';
import {
	getFulfillmentItems,
	refreshOrderFulfillmentStatus,
} from '../../utils/fulfillment-utils';
import { useFulfillmentDrawerContext } from '../../context/drawer-context';

export default function UpdateButton( {
	setError,
}: {
	setError: ( message: string | null ) => void;
} ) {
	const { setIsEditing } = useFulfillmentDrawerContext();
	const { order, fulfillment, notifyCustomer } = useFulfillmentContext();
	const { updateFulfillment } = useDispatch( FulfillmentStore );
	const [ isExecuting, setIsExecuting ] = useState< boolean >( false );

	const handleUpdateFulfillment = async () => {
		if ( ! fulfillment || ! order ) {
			setError(
				__(
					'An unexpected error has occurred. Please refresh the page and try again.',
					'woocommerce'
				)
			);
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setError( __( 'Select items to be fulfilled.', 'woocommerce' ) );
			return;
		}

		setError( null );
		setIsExecuting( true );
		await updateFulfillment( order.id, fulfillment, notifyCustomer );
		const error = select( FulfillmentStore ).getError( order.id );
		if ( error ) {
			setError( error );
		} else {
			refreshOrderFulfillmentStatus( order.id );
			setIsEditing( false );
		}
		setIsExecuting( false );
	};

	return (
		<Button
			variant="primary"
			onClick={ handleUpdateFulfillment }
			disabled={ isExecuting }
			isBusy={ isExecuting }
			__next40pxDefaultSize
			aria-label={
				isExecuting
					? __( 'Updating fulfillment, please wait', 'woocommerce' )
					: __( 'Update fulfillment with changes', 'woocommerce' )
			}
			aria-describedby="update-button-description"
		>
			{ isExecuting
				? __( 'Updating…', 'woocommerce' )
				: __( 'Update', 'woocommerce' ) }
			<span id="update-button-description" className="screen-reader-text">
				{ __(
					'Applies changes to the existing fulfillment',
					'woocommerce'
				) }
			</span>
		</Button>
	);
}
