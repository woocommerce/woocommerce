/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, select } from '@wordpress/data';
import { useState } from 'react';
import { useInstanceId } from '@wordpress/compose';

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

export default function FulfillItemsButton( {
	setError,
}: {
	setError: ( message: string | null ) => void;
} ) {
	const { setIsEditing } = useFulfillmentDrawerContext();
	const { order, fulfillment, notifyCustomer } = useFulfillmentContext();
	const [ isExecuting, setIsExecuting ] = useState( false );
	const { saveFulfillment } = useDispatch( FulfillmentStore );
	const descriptionId = useInstanceId(
		FulfillItemsButton,
		'fulfill-items-description'
	) as string;

	const handleFulfillItems = async () => {
		setError( null );
		if ( ! fulfillment || ! order ) {
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setError( __( 'Select items to be fulfilled.', 'woocommerce' ) );
			return;
		}

		setIsExecuting( true );

		// Mark fulfillment as fulfilled.
		fulfillment.is_fulfilled = true;
		fulfillment.status = 'fulfilled';
		await saveFulfillment( order.id, fulfillment, notifyCustomer );

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
		<>
			<Button
				variant="primary"
				onClick={ handleFulfillItems }
				__next40pxDefaultSize
				isBusy={ isExecuting }
				disabled={ isExecuting }
				aria-describedby={ descriptionId }
			>
				{ isExecuting
					? __( 'Fulfilling…', 'woocommerce' )
					: __( 'Fulfill items', 'woocommerce' ) }
			</Button>
			<span id={ descriptionId } className="screen-reader-text">
				{ __(
					'Marks the selected items as fulfilled and updates their status',
					'woocommerce'
				) }
			</span>
		</>
	);
}
