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
import { Panel } from '@woocommerce/blocks-components';

export interface ShippingAddressProps {
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: React.Dispatch<
		React.SetStateAction< boolean >
	>;
	shippingAddress: ShippingAddressType;
}

export type ActiveShippingZones = {
	description: string;
}[];

export const ShippingAddress = ( {
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	shippingAddress,
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
	const formattedLocation = formatShippingAddress( shippingAddress );
	const deliveryLabel = (
		<>
			{ __( 'Delivery to ', 'woocommerce' ) }
			<strong>{ formattedLocation }</strong>
		</>
	);
	if ( prefersCollection ) return <PickupLocation />;

	return (
		<Panel
			className="wc-block-components-totals-shipping-panel"
			initialOpen={ isShippingCalculatorOpen }
			hasBorder={ false }
			title={ deliveryLabel }
			state={ [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] }
		>
			<></>
		</Panel>
	);
};

export default ShippingAddress;
