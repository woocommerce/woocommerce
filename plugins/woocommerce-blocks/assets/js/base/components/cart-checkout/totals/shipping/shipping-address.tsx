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

export type ActiveShippingZones = {
	description: string;
}[];

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
	const activeShippingZones: ActiveShippingZones = getSetting(
		'activeShippingZones'
	);

	const hasMultipleAndDefaultZone =
		activeShippingZones.length > 1 &&
		activeShippingZones.some(
			( zone: { description: string } ) =>
				zone.description === 'Everywhere' ||
				zone.description === 'Locations outside all other zones'
		);

	const hasFormattedAddress = !! formatShippingAddress( shippingAddress );

	// If there is no default customer location set in the store,
	// and the customer hasn't provided their address,
	// and only one default shipping method is available for all locations,
	// then the shipping calculator will be hidden to avoid confusion.
	if ( ! hasFormattedAddress && ! isEditor && ! hasMultipleAndDefaultZone ) {
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
