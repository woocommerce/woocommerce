/**
 * External dependencies
 */
import { useEffect, useMemo, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { LineItem, Order } from '../../data/types';
import { FulfillmentProvider } from '../../context/fulfillment-context';
import SaveAsDraftButton from '../action-buttons/save-draft-button';
import FulfillItemsButton from '../action-buttons/fulfill-items-button';
import { getItemsNotInAnyFulfillment } from '../../utils/order-utils';
import ItemSelector from './item-selector';
import { useFulfillmentDrawerContext } from '../../context/drawer-context';
import ErrorLabel from '../user-interface/error-label';
import { ShipmentFormProvider } from '../../context/shipment-form-context';
import ShipmentForm from '../shipment-form';
import CustomerNotificationBox from '../customer-notification-form';

const NewFulfillmentForm: React.FC = () => {
	const {
		order,
		fulfillments,
		refunds,
		openSection,
		setOpenSection,
		isEditing,
	} = useFulfillmentDrawerContext();
	const [ error, setError ] = useState< string | null >( null );

	// Reset error when order changes
	useEffect( () => {
		setError( null );
	}, [ order?.id ] );

	const remainingItems = useMemo(
		() =>
			getItemsNotInAnyFulfillment(
				fulfillments,
				order ?? ( { line_items: [] as LineItem[] } as Order ),
				refunds ?? []
			).map( ( item ) => ( {
				...item,
				selection: item.selection.map( ( selection ) => ( {
					...selection,
					checked: true,
				} ) ),
			} ) ),
		[ fulfillments, order, refunds ]
	);

	if ( ! order ) {
		return null;
	}

	if ( remainingItems.length === 0 ) {
		return null;
	}

	return (
		<div
			className={ [
				'woocommerce-fulfillment-new-fulfillment-form',
				isEditing
					? 'woocommerce-fulfillment-new-fulfillment-form__disabled'
					: '',
				fulfillments.length === 0
					? 'woocommerce-fulfillment-new-fulfillment-form__first'
					: '',
			].join( ' ' ) }
		>
			<div
				className={ [
					'woocommerce-fulfillment-new-fulfillment-form__header',
					openSection === 'order' ? 'is-open' : '',
				].join( ' ' ) }
				onClick={ () => {
					if ( fulfillments.length > 0 ) {
						setOpenSection(
							openSection === 'order' ? '' : 'order'
						);
					}
				} }
				onKeyDown={ ( event ) => {
					if ( fulfillments.length > 0 ) {
						if ( event.key === 'Enter' || event.key === ' ' ) {
							setOpenSection(
								openSection === 'order' ? '' : 'order'
							);
						}
					}
				} }
				tabIndex={ 0 }
				role="button"
			>
				<h3>
					{ fulfillments.length === 0
						? __( 'Order Items', 'woocommerce' )
						: __( 'Pending Items', 'woocommerce' ) }
				</h3>
				{ fulfillments.length > 0 && (
					<Button __next40pxDefaultSize size="small">
						<Icon
							icon={
								openSection === 'order'
									? 'arrow-up-alt2'
									: 'arrow-down-alt2'
							}
							size={ 16 }
						/>
					</Button>
				) }
			</div>
			{ ! isEditing && openSection === 'order' && (
				<div className="woocommerce-fulfillment-new-fulfillment-form__content">
					{ error && <ErrorLabel error={ error } /> }
					<ShipmentFormProvider>
						<FulfillmentProvider
							order={ order }
							fulfillment={ null }
							items={ remainingItems }
						>
							<ItemSelector editMode={ true } />

							<ShipmentForm />
							<CustomerNotificationBox type="fulfill" />
							<div className="woocommerce-fulfillment-item-actions">
								<SaveAsDraftButton setError={ setError } />
								<FulfillItemsButton setError={ setError } />
							</div>
						</FulfillmentProvider>
					</ShipmentFormProvider>
				</div>
			) }
		</div>
	);
};

export default NewFulfillmentForm;
