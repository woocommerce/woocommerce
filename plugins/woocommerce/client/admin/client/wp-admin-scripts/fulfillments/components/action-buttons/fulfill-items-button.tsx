/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
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
	const { order, fulfillment, notifyCustomer } = useFulfillmentContext();
	const [ isExecuting, setIsExecuting ] = useState( false );
	const { saveFulfillment } = useDispatch( FulfillmentStore );
	const { getError } = useSelect(
		( select ) => ( { getError: select( FulfillmentStore ).getError } ),
		[]
	);

	const handleFulfillItems = async () => {
		setIsExecuting( true );
		setError( null );
		if ( ! fulfillment || ! order ) {
			setIsExecuting( false );
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setError( __( 'Select items to be fulfilled.', 'woocommerce' ) );
			setIsExecuting( false );
			return;
		}
		fulfillment.is_fulfilled = true;
		fulfillment.status = 'fulfilled';

		await saveFulfillment( order.id, fulfillment, notifyCustomer );
		const error = getError( order.id );
		if ( error ) {
			setError( error );
		} else {
			setIsEditing( false );
		}
		setIsExecuting( false );
	};

	return (
		<Button
			variant="primary"
			onClick={ handleFulfillItems }
			__next40pxDefaultSize
			isBusy={ isExecuting }
			disabled={ isExecuting }
		>
			{ __( 'Fulfill items', 'woocommerce' ) }
		</Button>
	);
}
