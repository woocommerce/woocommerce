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

/**
 * Saves the fulfillment with the given status: creates it when it is new, updates it otherwise.
 *
 * Used for the local pickup steps, Ready for pickup and Picked up.
 *
 * @param props             Component props.
 * @param props.status      The fulfillment status to save.
 * @param props.isFulfilled Whether that status counts as fulfilled.
 * @param props.label       The button label.
 * @param props.busyLabel   The label while saving.
 * @param props.description Screen reader description of what the button does.
 * @param props.variant     The button variant.
 * @param props.setError    Reports an error to the form.
 */
export default function SetStatusButton( {
	status,
	isFulfilled,
	label,
	busyLabel,
	description,
	variant = 'primary',
	setError,
}: {
	status: string;
	isFulfilled: boolean;
	label: string;
	busyLabel: string;
	description: string;
	variant?: 'primary' | 'secondary';
	setError: ( message: string | null ) => void;
} ) {
	const { setIsEditing } = useFulfillmentDrawerContext();
	const { order, fulfillment, notifyCustomer, customerNote } =
		useFulfillmentContext();
	const [ isExecuting, setIsExecuting ] = useState( false );
	const { saveFulfillment, updateFulfillment } =
		useDispatch( FulfillmentStore );
	const descriptionId = useInstanceId(
		SetStatusButton,
		'set-status-description'
	) as string;

	const handleClick = async () => {
		setError( null );
		if ( ! fulfillment || ! order ) {
			return;
		}
		if ( getFulfillmentItems( fulfillment ).length === 0 ) {
			setError( __( 'Select items to be fulfilled.', 'woocommerce' ) );
			return;
		}

		setIsExecuting( true );

		const next = { ...fulfillment, status, is_fulfilled: isFulfilled };
		if ( next.id ) {
			await updateFulfillment(
				order.id,
				next,
				notifyCustomer,
				notifyCustomer ? customerNote : ''
			);
		} else {
			await saveFulfillment( order.id, next, notifyCustomer );
		}

		const error = select( FulfillmentStore ).getError( order.id );
		if ( error ) {
			setError( error );
		} else {
			void refreshOrderFulfillmentStatus( order.id );
			setIsEditing( false );
		}

		setIsExecuting( false );
	};

	return (
		<>
			<Button
				variant={ variant }
				onClick={ handleClick }
				__next40pxDefaultSize
				isBusy={ isExecuting }
				disabled={ isExecuting }
				aria-describedby={ descriptionId }
			>
				{ isExecuting ? busyLabel : label }
			</Button>
			<span id={ descriptionId } className="screen-reader-text">
				{ description }
			</span>
		</>
	);
}
