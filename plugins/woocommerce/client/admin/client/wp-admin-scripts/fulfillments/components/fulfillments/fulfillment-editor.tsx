/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { useEffect, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Fulfillment } from '../../data/types';
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
import CustomerNotificationBox from '../customer-notification-form';
import FulfillmentStatusBadge from './fulfillment-status-badge';
import ErrorLabel from '../user-interface/error-label';
import { useFulfillmentDrawerContext } from '../../context/drawer-context';
import ShipmentViewer from '../shipment-form/shipment-viewer';
import ShipmentForm from '../shipment-form';
import { ShipmentFormProvider } from '../../context/shipment-form-context';
import MetadataViewer from '../metadata-viewer';
import { getFulfillmentLockState } from '../../utils/fulfillment-utils';
import LockLabel from '../user-interface/lock-label';

interface FulfillmentEditorProps {
	index: number;
	expanded: boolean;
	onExpand: () => void;
	onCollapse: () => void;
	fulfillment: Fulfillment;
	disabled?: boolean;
}
export default function FulfillmentEditor( {
	index,
	expanded,
	onExpand,
	onCollapse,
	fulfillment,
	disabled = false,
}: FulfillmentEditorProps ) {
	const { order, fulfillments, refunds } = useFulfillmentDrawerContext();
	const { isEditing, setIsEditing } = useFulfillmentDrawerContext();
	const [ error, setError ] = useState< string | null >( null );
	const itemsInFulfillment = order
		? getItemsFromFulfillment( order, fulfillment )
		: [];
	const itemsNotInAnyFulfillment = order
		? getItemsNotInAnyFulfillment( fulfillments, order, refunds )
		: [];
	const selectableItems = combineItems(
		[ ...itemsInFulfillment ],
		[ ...itemsNotInAnyFulfillment ]
	);

	const fulfillmentLockState = getFulfillmentLockState( fulfillment );

	// Reset error when order changes
	useEffect( () => {
		setError( null );
	}, [ order?.id ] );

	const handleChevronClick = () => {
		if ( isEditing ) return;
		if (
			itemsNotInAnyFulfillment.length === 0 &&
			fulfillments.length === 1
		)
			return;
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
							isEditing
								? /* translators: %s: Fulfillment ID */
								  __( 'Editing fulfillment #%s', 'woocommerce' )
								: /* translators: %s: Fulfillment ID */
								  __( 'Fulfillment #%s', 'woocommerce' ),
							index + 1
						)
					}
				</h3>
				<FulfillmentStatusBadge fulfillment={ fulfillment } />
				{ ( itemsNotInAnyFulfillment.length > 0 ||
					fulfillments.length > 1 ) && (
					<Button __next40pxDefaultSize size="small">
						<Icon
							icon={
								expanded ? 'arrow-up-alt2' : 'arrow-down-alt2'
							}
							size={ 16 }
							color={ isEditing ? '#dddddd' : undefined }
						/>
					</Button>
				) }
			</div>
			{ expanded && (
				<div className="woocommerce-fulfillment-stored-fulfillment-list-item-content">
					{ error && <ErrorLabel error={ error } /> }

					<ShipmentFormProvider fulfillment={ fulfillment }>
						<FulfillmentProvider
							order={ order }
							fulfillment={ fulfillment }
							items={
								isEditing ? selectableItems : itemsInFulfillment
							}
						>
							<ItemSelector editMode={ isEditing } />
							{ isEditing && <ShipmentForm /> }
							{ ! isEditing && (
								<>
									<ShipmentViewer />
									<MetadataViewer
										fulfillment={ fulfillment }
									/>
								</>
							) }
							{ ( ( fulfillment.is_fulfilled && isEditing ) ||
								( ! fulfillment.is_fulfilled &&
									! isEditing ) ) && (
								<CustomerNotificationBox type="update" />
							) }
							{ fulfillmentLockState.isLocked ? (
								<div className="woocommerce-fulfillment-item-lock-container">
									<LockLabel
										message={ fulfillmentLockState.reason }
									/>
								</div>
							) : (
								<div className="woocommerce-fulfillment-item-actions">
									{ ! isEditing ? (
										<>
											<EditFulfillmentButton
												onClick={ () => {
													setIsEditing( true );
												} }
											/>
											{ ! fulfillment.is_fulfilled && (
												<FulfillItemsButton
													setError={ setError }
												/>
											) }
										</>
									) : (
										<>
											<CancelLink
												onClick={ () => {
													setError( null );
													setIsEditing( false );
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
							) }
						</FulfillmentProvider>
					</ShipmentFormProvider>
				</div>
			) }
		</div>
	);
}
