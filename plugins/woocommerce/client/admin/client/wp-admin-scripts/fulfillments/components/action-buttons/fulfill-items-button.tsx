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

export default function FulfillItemsButton( {
	setError,
}: {
	setError: ( message: string | null ) => void;
} ) {
	const { setIsEditing } = useFulfillmentDrawerContext();
	const { order, fulfillment } = useFulfillmentContext();
	const [ isExecuting, setIsExecuting ] = useState( false );
	const { saveFulfillment } = useDispatch( FulfillmentStore );

	const handleFulfillItems = () => {
		setIsExecuting( true );
		setError( null );
		if ( ! fulfillment || ! order ) {
			setIsExecuting( false );
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setError( 'Select items to be fulfilled.' );
			setIsExecuting( false );
			return;
		}
		fulfillment.is_fulfilled = true;
		fulfillment.status = 'fulfilled';
		saveFulfillment( order.id, fulfillment )
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
			onClick={ handleFulfillItems }
			__next40pxDefaultSize
			isBusy={ isExecuting }
		>
			{ __( 'Fulfill items', 'woocommerce' ) }
		</Button>
	);
}
