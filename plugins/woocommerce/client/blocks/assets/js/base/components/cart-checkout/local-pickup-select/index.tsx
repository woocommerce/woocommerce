/**
 * External dependencies
 */
import {
	RadioControl,
	RadioControlOptionType,
} from '@woocommerce/blocks-components';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import { useShippingData } from '@woocommerce/base-context';

interface LocalPickupSelectProps {
	title?: string | undefined;
	selectedOption: string;
	pickupLocations: CartShippingPackageShippingRate[];
	renderPickupLocation: (
		location: CartShippingPackageShippingRate,
		pickupLocationsCount: number,
		// This arg signals that the rate is selected in the _client_ (not necessarily the selected shipping rate on the server, yet)
		// If the server returns a cart with a different selected shipping rate, then after "receiving" the updated cart (`receiveCart`)
		// this arg, `isSelectedInClient`, will be true for that rate, and the UI will update.
		isSelectedInClient?: boolean
	) => RadioControlOptionType;
	packageCount: number;
	onChange: ( value: string ) => void;
}
/**
 * Local pickup select component, used to render a package title and local pickup options.
 */
export const LocalPickupSelect = ( {
	title,
	selectedOption,
	pickupLocations,
	renderPickupLocation,
	packageCount,
	onChange,
}: LocalPickupSelectProps ) => {
	const { shippingRates } = useShippingData();
	const internalPackageCount = shippingRates?.length || 1;
	// Hacky way to check if there are multiple packages, this way is borrowed from  `assets/js/base/components/cart-checkout/shipping-rates-control-package/index.tsx`
	// We have no built-in way of checking if other extensions have added packages.
	const multiplePackages =
		internalPackageCount > 1 ||
		document.querySelectorAll(
			'.wc-block-components-local-pickup-select .wc-block-components-radio-control'
		).length > 1;
	return (
		<div className="wc-block-components-local-pickup-select">
			{ multiplePackages && title ? <div>{ title }</div> : false }
			<RadioControl
				onChange={ onChange }
				highlightChecked={ true }
				selected={ selectedOption }
				options={ pickupLocations.map( ( location ) =>
					renderPickupLocation(
						location,
						packageCount,
						selectedOption === location.rate_id
					)
				) }
			/>
		</div>
	);
};
export default LocalPickupSelect;
