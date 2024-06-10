/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { formatShippingAddress } from '@woocommerce/base-utils';
import { useEditorContext } from '@woocommerce/base-context';
import { ShippingAddress as ShippingAddressType } from '@woocommerce/settings';
import PickupLocation from '@woocommerce/base-components/cart-checkout/pickup-location';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

export interface ShippingAddressProps {
	setShippingCalculatorLabel: React.Dispatch<
		React.SetStateAction< string >
	>;
	setShippingCalculatorAddress: React.Dispatch<
		React.SetStateAction< string >
	>;
	shippingAddress: ShippingAddressType;
	hasRates: boolean;
}

export const ShippingAddress = ( {
	setShippingCalculatorLabel,
	setShippingCalculatorAddress,
	shippingAddress,
	hasRates,
}: ShippingAddressProps ): JSX.Element | null => {
	const { isEditor } = useEditorContext();
	const prefersCollection = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).prefersCollection()
	);

	const hasFormattedAddress = !! formatShippingAddress( shippingAddress );

	// If it's the editor and store has no formatted address,
	// then the shipping calculator labels will not be modified.
	// Shipping calculator will display the default label.
	if ( isEditor && ! hasFormattedAddress ) {
		return null;
	}

	if ( prefersCollection )
		return (
			<PickupLocation
				setShippingCalculatorLabel={ setShippingCalculatorLabel }
				setShippingCalculatorAddress={ setShippingCalculatorAddress }
			/>
		);
	const formattedAddress = formatShippingAddress( shippingAddress );
	let deliveryLabel;
	if ( hasRates ) {
		deliveryLabel = __( 'Delivers to', 'woocommerce' );
	} else {
		deliveryLabel = __(
			'No delivery options available for',
			'woocommerce'
		);
	}

	setShippingCalculatorLabel( deliveryLabel );
	if ( formattedAddress ) {
		setShippingCalculatorAddress( formattedAddress );
	}
	return <></>;
};

export default ShippingAddress;
