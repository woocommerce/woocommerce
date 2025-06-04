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

export default function SaveAsDraftButton( {
	setError,
}: {
	setError: ( message: string | null ) => void;
} ) {
	const { setIsEditing } = useFulfillmentDrawerContext();
	const { order, fulfillment, notifyCustomer } = useFulfillmentContext();
	const [ isExecuting, setIsExecuting ] = useState( false );
	const { saveFulfillment } = useDispatch( FulfillmentStore );

	const handleFulfillItems = () => {
		setError( null );
		setIsExecuting( true );

		if ( ! fulfillment || ! order ) {
			setIsExecuting( false );
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setIsExecuting( false );
			setError( 'Select items to be fulfilled.' );
			return;
		}
		saveFulfillment( order.id, fulfillment, notifyCustomer )
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
			variant="secondary"
			onClick={ handleFulfillItems }
			__next40pxDefaultSize
			isBusy={ isExecuting }
		>
			{ __( 'Save as draft', 'woocommerce' ) }
		</Button>
	);
}
