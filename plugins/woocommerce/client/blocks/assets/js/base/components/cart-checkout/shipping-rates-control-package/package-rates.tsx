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
	// Whether this control owns its selection state. When true it selects a rate
	// on mount, mirrors later store changes into local state, and renders that
	// local value so clicks show instantly. When false it renders the store's
	// selected rate and leaves initial selection to the parent.
	manageSelectionLocally?: boolean;
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
	manageSelectionLocally = true,
}: PackageRates ): JSX.Element => {
	const selectedRateId = selectedRate?.rate_id;

	// Store selected rate ID in local state so shipping rates changes are shown in the UI instantly.
	const [ selectedOption, setSelectedOption ] = useState<
		string | undefined
	>( selectedRateId ?? rates[ 0 ]?.rate_id );

	// Standalone controls synchronize on mount and replace pending selections.
	// Core disables this effect and coordinates initial selections in the parent.
	useEffect( () => {
		if ( manageSelectionLocally && selectedOption ) {
			onSelectRate( selectedOption );
		}
		// We want this to run on mount only, beware of updating it as it may cause
		// shipping rate selection to end up in infinite loop
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Update the selected option if cart state changes in the data store.
	useEffect( () => {
		if (
			manageSelectionLocally &&
			selectedRateId &&
			selectedRateId !== selectedOption
		) {
			setSelectedOption( selectedRateId );
		}
		// We want to explicitly react to changes in the data store only here, local state is managed
		// through different code path.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ selectedRateId, manageSelectionLocally ] );

	if ( rates.length === 0 ) {
		return noResultsMessage;
	}

	// Local state drives the radio when this control owns the selection, so a
	// click shows immediately; otherwise the store's selected rate drives it.
	const checkedRateId = manageSelectionLocally
		? selectedOption
		: selectedRateId;

	return (
		<RadioControl
			className={ className }
			onChange={ ( value: string ) => {
				if ( manageSelectionLocally ) {
					setSelectedOption( value );
				}
				onSelectRate( value );
			} }
			highlightChecked={ highlightChecked }
			disabled={ disabled }
			selected={ checkedRateId ?? '' }
			options={ rates.map( renderOption ) }
			descriptionStackingDirection="column"
		/>
	);
};

export default PackageRates;
