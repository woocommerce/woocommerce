/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
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
		shippingCalculatorId,
		showCalculator,
		setIsShippingCalculatorOpen,
	} = useContext( ShippingCalculatorContext );
	const { shippingAddress } = useCustomerData();
	const noticeContext = 'wc/cart/shipping-calculator';

	if ( ! showCalculator ) {
		return null;
	}

	return (
		<div
			className="wc-block-components-shipping-calculator"
			id={ shippingCalculatorId }
		>
			<StoreNoticesContainer context={ noticeContext } />
			<ShippingCalculatorAddress
				address={ shippingAddress }
				addressFields={ addressFields }
				onCancel={ () => {
					setIsShippingCalculatorOpen( false );
					onCancel();
				} }
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
							setIsShippingCalculatorOpen( false );
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
