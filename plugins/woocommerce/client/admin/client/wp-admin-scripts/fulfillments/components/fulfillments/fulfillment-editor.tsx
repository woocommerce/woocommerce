/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Fulfillment, Order } from '../../data/types';
import {
	combineItems,
	getItemsFromFulfillment,
	getItemsNotInAnyFulfillment,
} from '../../utils/order-utils';
import { FulfillmentProvider } from '../../context/fulfillment-context';
import ItemSelector from './item-selector';
import EditFulfillmentButton from '../action-buttons/edit-fulfillment-button';
import FulfillItemsButton from '../action-buttons/fulfill-items-button';
import CancelLink from '../action-buttons/cancel-link';
import RemoveButton from '../action-buttons/remove-button';
import UpdateButton from '../action-buttons/update-button';
import FulfillmentStatusBadge from './fulfillment-status-badge';
import ErrorLabel from '../user-interface/error-label';
import { useFulfillmentDrawerContext } from '../../context/drawer-context';
import ShipmentViewer from '../shipment-form/shipment-viewer';
import ShipmentForm from '../shipment-form';
import { ShipmentFormProvider } from '../../context/shipment-form-context';

interface FulfillmentEditorProps {
	index: number;
	expanded: boolean;
	onExpand: () => void;
	onCollapse: () => void;
	fulfillment: Fulfillment;
	fulfillments: Fulfillment[];
	order: Order;
	disabled?: boolean;
}
export default function FulfillmentEditor( {
	index,
	expanded,
	onExpand,
	onCollapse,
	fulfillment,
	fulfillments,
	order,
	disabled = false,
}: FulfillmentEditorProps ) {
	const [ editMode, setEditMode ] = useState( false );
	const { setIsEditing } = useFulfillmentDrawerContext();
	const [ error, setError ] = useState< string | null >( null );
	const itemsInFulfillment = getItemsFromFulfillment( order, fulfillment );
	const itemsNotInAnyFulfillment = getItemsNotInAnyFulfillment(
		fulfillments,
		order
	);
	const selectableItems = combineItems(
		[ ...itemsInFulfillment ],
		[ ...itemsNotInAnyFulfillment ]
	);

	const handleChevronClick = () => {
		if ( editMode ) return;
		if ( ! expanded ) {
			onExpand();
		} else {
			onCollapse();
		}
	};

	return (
		<div
			className={ [
				'woocommerce-fulfillment-stored-fulfillment-list-item',
				disabled
					? 'woocommerce-fulfillment-stored-fulfillment-list-item__disabled'
					: '',
			].join( ' ' ) }
		>
			<div
				className={ [
					'woocommerce-fulfillment-stored-fulfillment-list-item-header',
					expanded ? 'is-open' : '',
				].join( ' ' ) }
				onClick={ handleChevronClick }
				onKeyUp={ ( event ) => {
					if ( event.key === 'Enter' ) {
						handleChevronClick();
					}
				} }
				role="button"
				tabIndex={ -1 }
			>
				<h3>
					{
						// eslint-disable-next-line @wordpress/valid-sprintf
						sprintf(
							editMode
								? /* translators: %s: Fulfillment ID */
								  __( 'Editing fulfillment #%s', 'woocommerce' )
								: /* translators: %s: Fulfillment ID */
								  __( 'Fulfillment #%s', 'woocommerce' ),
							index + 1
						)
					}
				</h3>
				<FulfillmentStatusBadge fulfillment={ fulfillment } />
				<Button __next40pxDefaultSize size="small">
					<Icon
						icon={ expanded ? 'arrow-up-alt2' : 'arrow-down-alt2' }
						size={ 16 }
						color={ editMode ? '#dddddd' : undefined }
					/>
				</Button>
			</div>
			{ expanded && (
				<div className="woocommerce-fulfillment-stored-fulfillment-list-item-content">
					{ error && <ErrorLabel error={ error } /> }

					<ShipmentFormProvider fulfillment={ fulfillment }>
						<FulfillmentProvider
							order={ order }
							fulfillment={ fulfillment }
							items={
								editMode ? selectableItems : itemsInFulfillment
							}
						>
							<ItemSelector editMode={ editMode } />
							{ editMode && <ShipmentForm /> }
							{ ! editMode && <ShipmentViewer /> }

							<div className="woocommerce-fulfillment-item-actions">
								{ ! editMode ? (
									<>
										<EditFulfillmentButton
											onClick={ () => {
												setEditMode( true );
												setIsEditing( true );
											} }
										/>
										<FulfillItemsButton
											setError={ setError }
										/>
									</>
								) : (
									<>
										<CancelLink
											onClick={ () => {
												setError( null );
												setIsEditing( false );
												setEditMode( false );
											} }
										/>
										<RemoveButton
											setError={ ( message ) =>
												setError( message )
											}
										/>
										<UpdateButton
											setError={ ( message ) =>
												setError( message )
											}
										/>
									</>
								) }
							</div>
						</FulfillmentProvider>
					</ShipmentFormProvider>
				</div>
			) }
		</div>
	);
}
