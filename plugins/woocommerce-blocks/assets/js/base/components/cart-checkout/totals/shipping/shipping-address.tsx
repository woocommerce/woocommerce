/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { formatShippingAddress } from '@woocommerce/base-utils';
import { useEditorContext } from '@woocommerce/base-context';
import {
	ShippingAddress as ShippingAddressType,
	getSetting,
} from '@woocommerce/settings';
import PickupLocation from '@woocommerce/base-components/cart-checkout/pickup-location';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ShippingLocation from '../../shipping-location';
import { CalculatorButton, CalculatorButtonProps } from './calculator-button';

export interface ShippingAddressProps {
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: CalculatorButtonProps[ 'setIsShippingCalculatorOpen' ];
	shippingAddress: ShippingAddressType;
	activeShippingRates: number;
}

type ShippingMethodSettings = {
	hasOnlyDefaultShippingMethod: boolean;
	activeMethodsCount: number;
};

export const ShippingAddress = ( {
	showCalculator,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	shippingAddress,
	activeShippingRates = 0,
}: ShippingAddressProps ): JSX.Element | null => {
	const { isEditor } = useEditorContext();
	const prefersCollection = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).prefersCollection()
	);

	const hasFormattedAddress = !! formatShippingAddress( shippingAddress );

	const {
		hasOnlyDefaultShippingMethod,
		activeMethodsCount,
	}: ShippingMethodSettings = getSetting( 'shippingMethodSettings' );

	const displayCalculatorButton =
		! hasOnlyDefaultShippingMethod ||
		activeShippingRates > activeMethodsCount;

	if ( ! hasFormattedAddress && ! isEditor && ! displayCalculatorButton ) {
		return null;
	}

	const label = hasFormattedAddress
		? __( 'Change address', 'woocommerce' )
		: __( 'Calculate shipping for your location', 'woocommerce' );
	const formattedLocation = formatShippingAddress( shippingAddress );
	return (
		<>
			{ prefersCollection ? (
				<PickupLocation />
			) : (
				<ShippingLocation formattedLocation={ formattedLocation } />
			) }
			{ showCalculator && (
				<CalculatorButton
					label={ label }
					isShippingCalculatorOpen={ isShippingCalculatorOpen }
					setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
				/>
			) }
		</>
	);
};

export default ShippingAddress;
