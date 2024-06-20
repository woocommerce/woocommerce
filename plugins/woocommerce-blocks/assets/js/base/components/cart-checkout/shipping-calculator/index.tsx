/**
 * External dependencies
 */
import type { ShippingAddress } from '@woocommerce/settings';
import { useCustomerData } from '@woocommerce/base-context/hooks';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY, processErrorResponse } from '@woocommerce/block-data';
import { StoreNoticesContainer, Panel } from '@woocommerce/blocks-components';
import { removeNoticesWithContext } from '@woocommerce/base-utils';

import { createInterpolateElement } from '@wordpress/element';
/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from './address';
import './style.scss';

interface ShippingCalculatorProps {
	onUpdate?: ( newAddress: ShippingAddress ) => void;
	onCancel?: () => void;
	addressFields?: Partial< keyof ShippingAddress >[];
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: React.Dispatch<
		React.SetStateAction< boolean >
	>;
	shippingCalculatorAddress: string;
	label: string;
}

const ShippingCalculator = ( {
	onUpdate = () => {
		/* Do nothing */
	},
	onCancel = () => {
		/* Do nothing */
	},
	addressFields = [ 'country', 'state', 'city', 'postcode' ],
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	shippingCalculatorAddress,
	label,
}: ShippingCalculatorProps ): JSX.Element => {
	const { shippingAddress } = useCustomerData();
	const noticeContext = 'wc/cart/shipping-calculator';
	let shippingCalculatorLabel;

	if ( shippingCalculatorAddress ) {
		shippingCalculatorLabel = createInterpolateElement(
			`${ label } <CalculatorAddress />`,
			{
				CalculatorAddress: (
					<strong>{ shippingCalculatorAddress }</strong>
				),
			}
		);
	} else {
		shippingCalculatorLabel = label;
	}

	return (
		<Panel
			className="wc-block-components-totals-shipping-panel"
			initialOpen={ isShippingCalculatorOpen }
			hasBorder={ false }
			title={ shippingCalculatorLabel }
			state={ [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] }
		>
			<div className="wc-block-components-shipping-calculator">
				<StoreNoticesContainer context={ noticeContext } />
				<ShippingCalculatorAddress
					address={ shippingAddress }
					addressFields={ addressFields }
					onCancel={ onCancel }
					onUpdate={ ( newAddress ) => {
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
		</Panel>
	);
};

export default ShippingCalculator;
