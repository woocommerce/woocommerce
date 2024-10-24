/**
 * External dependencies
 */
import type { ShippingAddress } from '@woocommerce/settings';
import { useCustomerData } from '@woocommerce/base-context/hooks';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY, processErrorResponse } from '@woocommerce/block-data';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { removeNoticesWithContext } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from './address';
import './style.scss';
import { CalculatorButtonProps } from '../totals/shipping/calculator-button';

interface ShippingCalculatorProps
	extends Pick< CalculatorButtonProps, 'shippingCalculatorID' > {
	onUpdate?: ( newAddress: ShippingAddress ) => void;
	onCancel?: () => void;
	addressFields?: Partial< keyof ShippingAddress >[];
}

const ShippingCalculator = ( {
	onUpdate = () => {
		/* Do nothing */
	},
	onCancel = () => {
		/* Do nothing */
	},
	addressFields = [ 'country', 'state', 'city', 'postcode' ],
	shippingCalculatorID,
}: ShippingCalculatorProps ): JSX.Element => {
	const { shippingAddress } = useCustomerData();
	const noticeContext = 'wc/cart/shipping-calculator';
	return (
		<div
			className="wc-block-components-shipping-calculator"
			id={ shippingCalculatorID }
		>
			<StoreNoticesContainer context={ noticeContext } />
			<ShippingCalculatorAddress
				address={ shippingAddress }
				addressFields={ addressFields }
				onCancel={ onCancel }
				onUpdate={ ( newAddress ) => {
					// Updates the address and waits for the result.
					dispatch( CART_STORE_KEY )
						.updateCustomerData(
							{
								shipping_address: newAddress,
							},
							false
						)
						.then( () => {
							removeNoticesWithContext( noticeContext );
							onUpdate( newAddress );
						} )
						.catch( ( response ) => {
							processErrorResponse( response, noticeContext );
						} );
				} }
			/>
		</div>
	);
};

export default ShippingCalculator;
