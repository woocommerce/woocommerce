/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import {
	RadioControl,
	RadioControlOptionLayout,
} from '@woocommerce/blocks-components';
import type { CartShippingPackageShippingRate } from '@woocommerce/types';
import { usePrevious } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { renderPackageRateOption } from './render-package-rate-option';
import type { PackageRateRenderOption } from '../shipping-rates-control-package';

interface PackageRates {
	onSelectRate: ( selectedRateId: string ) => void;
	rates: CartShippingPackageShippingRate[];
	renderOption?: PackageRateRenderOption | undefined;
	className?: string;
	noResultsMessage: JSX.Element;
	selectedRate: CartShippingPackageShippingRate | undefined;
	disabled?: boolean;
}

const PackageRates = ( {
	className = '',
	noResultsMessage,
	onSelectRate,
	rates,
	renderOption = renderPackageRateOption,
	selectedRate,
	disabled = false,
}: PackageRates ): JSX.Element => {
	const selectedRateId = selectedRate?.rate_id || '';
	const previousSelectedRateId = usePrevious( selectedRateId );

	// Store selected rate ID in local state so shipping rates changes are shown in the UI instantly.
	const [ selectedOption, setSelectedOption ] = useState( () => {
		if ( selectedRateId ) {
			return selectedRateId;
		}
		// Default to first rate if no rate is selected.
		return rates[ 0 ]?.rate_id;
	} );

	// Update the selected option if cart state changes in the data store.
	useEffect( () => {
		if (
			selectedRateId &&
			selectedRateId !== previousSelectedRateId &&
			selectedRateId !== selectedOption
		) {
			setSelectedOption( selectedRateId );
		}
	}, [ selectedRateId, selectedOption, previousSelectedRateId ] );

	// Update the data store when the local selected rate changes.
	useEffect( () => {
		if ( selectedOption ) {
			onSelectRate( selectedOption );
		}
	}, [ onSelectRate, selectedOption ] );

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
				disabled={ disabled }
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
