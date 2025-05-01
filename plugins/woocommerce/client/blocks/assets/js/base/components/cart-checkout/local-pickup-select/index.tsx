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
		pickupLocationsCount: number
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
					renderPickupLocation( location, packageCount )
				) }
			/>
		</div>
	);
};
export default LocalPickupSelect;
