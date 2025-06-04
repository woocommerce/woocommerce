/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { useFulfillmentContext } from '../../context/fulfillment-context';
import { store as FulfillmentStore } from '../../data/store';
import { getFulfillmentItems } from '../../utils/fulfillment-utils';
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

	const handleUpdateFulfillment = () => {
		setIsExecuting( true );
		setError( null );
		if ( ! fulfillment || ! order ) {
			setIsExecuting( false );
			setError(
				__(
					'An unexpected error has occurred. Please refresh the page and try again.',
					'woocommerce'
				)
			);
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setIsExecuting( false );
			setError( __( 'Select items to be fulfilled.', 'woocommerce' ) );
			return;
		}
		updateFulfillment( order.id, fulfillment, notifyCustomer )
			.then( () => {
				setIsEditing( false );
			} )
			.catch( ( error ) => {
				setError( error );
			} )
			.finally( () => {
				setIsExecuting( false );
			} );
	};

	return (
		<Button
			variant="primary"
			onClick={ handleUpdateFulfillment }
			isBusy={ isExecuting }
			__next40pxDefaultSize
		>
			{ __( 'Update', 'woocommerce' ) }
		</Button>
	);
}
