/**
 * External dependencies
 */
import {
	RadioControl,
	RadioControlOptionType,
} from '@woocommerce/blocks-components';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

interface LocalPickupSelectProps {
	title?: string | undefined;
	setSelectedOption: ( value: string ) => void;
	selectedOption: string;
	pickupLocations: CartShippingPackageShippingRate[];
	onSelectRate: ( value: string ) => void;
	renderPickupLocation: (
		location: CartShippingPackageShippingRate,
		pickupLocationsCount: number
	) => RadioControlOptionType;
	packageCount: number;
}
/**
 * Local pickup select component, used to render a package title and local pickup options.
 */
export const LocalPickupSelect = ( {
	title,
	setSelectedOption,
	selectedOption,
	pickupLocations,
	onSelectRate,
	renderPickupLocation,
	packageCount,
}: LocalPickupSelectProps ) => {
	const internalPackageCount = useSelect(
		( select ) =>
			select( CART_STORE_KEY )?.getCartData()?.shippingRates?.length
	);
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
				onChange={ ( value ) => {
					setSelectedOption( value );
					onSelectRate( value );
				} }
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
