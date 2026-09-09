/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { RadioControl } from '@woocommerce/blocks-components';
import type { CartShippingPackageShippingRate } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { renderPackageRateOption } from './render-package-rate-option';
import type { PackageRateRenderOption } from '../shipping-rates-control-package/types';

interface PackageRates {
	onSelectRate: ( selectedRateId: string ) => void;
	rates: CartShippingPackageShippingRate[];
	renderOption?: PackageRateRenderOption | undefined;
	className?: string;
	noResultsMessage: JSX.Element;
	selectedRate: CartShippingPackageShippingRate | undefined;
	disabled?: boolean;
	// Should the selected rate be highlighted.
	highlightChecked?: boolean;
	// Whether to synchronize the initial rate selection on mount.
	selectRateOnMount?: boolean;
}

const PackageRates = ( {
	className = '',
	noResultsMessage,
	onSelectRate,
	rates,
	renderOption = renderPackageRateOption,
	selectedRate,
	disabled = false,
	highlightChecked = false,
	selectRateOnMount = true,
}: PackageRates ): JSX.Element => {
	const selectedRateId = selectedRate?.rate_id;

	// Store selected rate ID in local state so shipping rates changes are shown in the UI instantly.
	const [ selectedOption, setSelectedOption ] = useState<
		string | undefined
	>( selectedRateId ?? rates[ 0 ]?.rate_id );

	// Standalone controls synchronize on mount and replace pending selections.
	// Core disables this effect and coordinates initial selections in the parent.
	useEffect( () => {
		if ( selectRateOnMount && selectedOption ) {
			onSelectRate( selectedOption );
		}
		// We want this to run on mount only, beware of updating it as it may cause
		// shipping rate selection to end up in infinite loop
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Update the selected option if cart state changes in the data store.
	useEffect( () => {
		if (
			selectRateOnMount &&
			selectedRateId &&
			selectedRateId !== selectedOption
		) {
			setSelectedOption( selectedRateId );
		}
		// We want to explicitly react to changes in the data store only here, local state is managed
		// through different code path.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ selectedRateId, selectRateOnMount ] );

	if ( rates.length === 0 ) {
		return noResultsMessage;
	}

	return (
		<RadioControl
			className={ className }
			onChange={ ( value: string ) => {
				if ( selectRateOnMount ) {
					setSelectedOption( value );
				}
				onSelectRate( value );
			} }
			highlightChecked={ highlightChecked }
			disabled={ disabled }
			selected={
				( selectRateOnMount ? selectedOption : selectedRateId ) ?? ''
			}
			options={ rates.map( renderOption ) }
			descriptionStackingDirection="column"
		/>
	);
};

export default PackageRates;
