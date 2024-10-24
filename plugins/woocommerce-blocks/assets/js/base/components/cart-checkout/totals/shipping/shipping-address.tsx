/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { formatShippingAddress } from '@woocommerce/base-utils';
import { ShippingAddress as ShippingAddressType } from '@woocommerce/settings';
import {
	ShippingLocation,
	PickupLocation,
	ShippingCalculatorButton,
} from '@woocommerce/base-components/cart-checkout';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

export interface ShippingAddressProps {
	shippingAddress: ShippingAddressType;
}

export const ShippingAddress = ( {
	shippingAddress,
}: ShippingAddressProps ): JSX.Element | null => {
	const prefersCollection = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).prefersCollection()
	);
	const formattedLocation = formatShippingAddress( shippingAddress );
	return (
		<>
			{ prefersCollection ? (
				<PickupLocation />
			) : (
				<ShippingLocation formattedLocation={ formattedLocation } />
			) }
			<ShippingCalculatorButton
				label={
					!! formattedLocation
						? __( 'Change address', 'woocommerce' )
						: __(
								'Enter address to check delivery options',
								'woocommerce'
						  )
				}
			/>
		</>
	);
};

export default ShippingAddress;
