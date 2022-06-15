/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import RadioControl, {
	RadioControlOptionLayout,
} from '@woocommerce/base-components/radio-control';
import type { PackageRateOption } from '@woocommerce/type-defs/shipping';
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';

/**
 * Internal dependencies
 */
import { renderPackageRateOption } from './render-package-rate-option';

interface PackageRates {
	onSelectRate: ( selectedRateId: string ) => void;
	rates: CartShippingPackageShippingRate[];
	renderOption?: (
		option: CartShippingPackageShippingRate
	) => PackageRateOption;
	className?: string;
	noResultsMessage: JSX.Element;
	selectedRate: CartShippingPackageShippingRate | undefined;
}

const PackageRates = ( {
	className = '',
	noResultsMessage,
	onSelectRate,
	rates,
	renderOption = renderPackageRateOption,
	selectedRate,
}: PackageRates ): JSX.Element => {
	const selectedRateId = selectedRate?.rate_id || '';

	// Store selected rate ID in local state so shipping rates changes are shown in the UI instantly.
	const [ selectedOption, setSelectedOption ] = useState( selectedRateId );

	// Update the selected option if cart state changes in the data stores.
	useEffect( () => {
		if ( selectedRateId ) {
			setSelectedOption( selectedRateId );
		}
	}, [ selectedRateId ] );

	if ( rates.length === 0 ) {
		return noResultsMessage;
	}

	if ( rates.length > 1 ) {
		return (
			<RadioControl
				className={ className }
				onChange={ ( value: string ) => {
					setSelectedOption( value );
					onSelectRate( value );
				} }
				selected={ selectedOption }
				options={ rates.map( renderOption ) }
			/>
		);
	}

	const { label, secondaryLabel, description, secondaryDescription } =
		renderOption( rates[ 0 ] );

	return (
		<RadioControlOptionLayout
			label={ label }
			secondaryLabel={ secondaryLabel }
			description={ description }
			secondaryDescription={ secondaryDescription }
		/>
	);
};

export default PackageRates;
