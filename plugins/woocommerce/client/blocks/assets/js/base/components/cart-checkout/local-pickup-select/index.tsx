/**
 * External dependencies
 */
import {
	RadioControl,
	RadioControlOptionType,
} from '@woocommerce/blocks-components';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import { useShippingData } from '@woocommerce/base-context';
import clsx from 'clsx';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import {
	PackageItems,
	ShippingPackageItemIcon,
} from '@woocommerce/base-components/cart-checkout';

/**
 * Internal dependencies
 */
import type { PackageData } from '../shipping-rates-control-package/types';

import './style.scss';

interface LocalPickupSelectProps {
	title?: string | undefined;
	packageData?: PackageData;
	showItems?: boolean;
	selectedOption: string;
	pickupLocations: CartShippingPackageShippingRate[];
	renderPickupLocation: (
		location: CartShippingPackageShippingRate,
		pickupLocationsCount: number,
		// This is the ID of the rate that is selected in the _client_ (not necessarily the selected shipping rate on the server, yet)
		// If the server returns a cart with a different selected shipping rate, then after "receiving" the updated cart (`receiveCart`)
		// this arg, `clientSelectedOption`, will change to be the ID for that rate, and the UI will update.
		clientSelectedOption?: string
	) => RadioControlOptionType;
	packageCount: number;
	onChange: ( value: string ) => void;
}
/**
 * Local pickup select component, used to render a package title and local pickup options.
 */
export const LocalPickupSelect = ( {
	title,
	packageData = undefined,
	showItems,
	selectedOption,
	pickupLocations,
	renderPickupLocation,
	packageCount,
	onChange,
}: LocalPickupSelectProps ) => {
	const { shippingRates } = useShippingData();
	const { cartItems } = useStoreCart();
	const internalPackageCount = shippingRates?.length || 1;
	// Hacky way to check if there are multiple packages, this way is borrowed from  `assets/js/base/components/cart-checkout/shipping-rates-control-package/index.tsx`
	// We have no built-in way of checking if other extensions have added packages.
	const multiplePackages =
		internalPackageCount > 1 ||
		document.querySelectorAll(
			'.wc-block-components-local-pickup-select .wc-block-components-radio-control'
		).length > 1;

	// If showItems is not set, we check if we have multiple packages.
	// We sometimes don't want to show items even if we have multiple packages.
	const shouldShowItems = showItems ?? multiplePackages;

	let header = multiplePackages && title && <div>{ title }</div>;

	// packageData was added in version 10.4
	if ( ( multiplePackages || shouldShowItems ) && packageData ) {
		header = (
			<div className="wc-block-components-shipping-rates-control__package-header">
				<div
					className="wc-block-components-shipping-rates-control__package-title"
					dangerouslySetInnerHTML={ {
						__html: sanitizeHTML(
							String( packageData.name ?? '' )
						),
					} }
				/>
				{ shouldShowItems && (
					<PackageItems packageData={ packageData } />
				) }
			</div>
		);

		if ( multiplePackages ) {
			const packageItems = packageData.items || [];

			header = (
				<div className="wc-block-components-shipping-rates-control__package-container">
					{ header }
					<div className="wc-block-components-shipping-rates-control__package-thumbnails">
						{ packageItems.slice( 0, 3 ).map( ( item ) => (
							<ShippingPackageItemIcon
								key={ item.key }
								packageItem={ item }
								cartItems={ cartItems }
							/>
						) ) }
					</div>
				</div>
			);
		}
	}

	return (
		<div
			className={ clsx(
				'wc-block-components-local-pickup-select',
				multiplePackages &&
					'wc-block-components-local-pickup-select--multiple'
			) }
		>
			{ header }
			<RadioControl
				onChange={ onChange }
				highlightChecked={ true }
				selected={ selectedOption }
				options={ pickupLocations.map( ( location ) =>
					renderPickupLocation(
						location,
						packageCount,
						selectedOption
					)
				) }
			/>
		</div>
	);
};
export default LocalPickupSelect;
