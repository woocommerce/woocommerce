/**
 * External dependencies
 */
import { useContext, useCallback } from '@wordpress/element';
import type { ShippingAddress } from '@woocommerce/settings';
import { useCustomerData } from '@woocommerce/base-context/hooks';
import { dispatch } from '@wordpress/data';
import { cartStore, processErrorResponse } from '@woocommerce/block-data';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { removeNoticesWithContext } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from './address';
import { ShippingCalculatorContext } from './context';
import './style.scss';

interface ShippingCalculatorProps {
	onUpdate?: ( newAddress: ShippingAddress ) => void;
	onCancel?: () => void;
	addressFields?: Partial< keyof ShippingAddress >[];
}

export const ShippingCalculator = ( {
	onUpdate = () => {
		/* Do nothing */
	},
	onCancel = () => {
		/* Do nothing */
	},
	addressFields = [ 'country', 'state', 'city', 'postcode' ],
}: ShippingCalculatorProps ): JSX.Element | null => {
	const {
		shippingCalculatorID,
		showCalculator,
		setIsShippingCalculatorOpen,
	} = useContext( ShippingCalculatorContext );
	const { shippingAddress } = useCustomerData();
	const noticeContext = 'wc/cart/shipping-calculator';

	const handleCancel = useCallback( () => {
		setIsShippingCalculatorOpen( false );
		onCancel();
	}, [ setIsShippingCalculatorOpen, onCancel ] );

	const handleUpdate = useCallback(
		( newAddress: ShippingAddress ) => {
			// Updates the address and waits for the result.
			dispatch( cartStore )
				.updateCustomerData(
					{
						shipping_address: newAddress,
					},
					false
				)
				.then( () => {
					removeNoticesWithContext( noticeContext );
					setIsShippingCalculatorOpen( false );
					onUpdate( newAddress );
				} )
				.catch( ( response ) => {
					processErrorResponse( response, noticeContext );
				} );
		},
		[ onUpdate, setIsShippingCalculatorOpen ]
	);

	if ( ! showCalculator ) {
		return null;
	}

	return (
		<div
			className="wc-block-components-shipping-calculator"
			id={ shippingCalculatorID }
		>
			<StoreNoticesContainer context={ noticeContext } />
			<ShippingCalculatorAddress
				address={ shippingAddress }
				addressFields={ addressFields }
				onCancel={ handleCancel }
				onUpdate={ handleUpdate }
			/>
		</div>
	);
};

export default ShippingCalculator;
