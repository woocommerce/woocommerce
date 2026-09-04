/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { objectHasProp } from '@woocommerce/types';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';
import type { CountryInputWithCountriesProps } from './CountryInputProps';
import { Select, SelectOption } from '../select';

export const CountryInput = ( {
	className,
	countries,
	id,
	errorId,
	label,
	onChange,
	value = '',
	autoComplete = 'off',
	required = false,
}: CountryInputWithCountriesProps ): JSX.Element => {
	const options = useMemo< SelectOption[] >( () => {
		const countryOptions: SelectOption[] = Object.entries( countries ).map(
			( [ countryCode, countryName ] ) => ( {
				value: countryCode,
				label: decodeEntities( countryName ),
			} )
		);
		// Keep an unavailable saved country in the list as a disabled option.
		// With no matching option the select drifts off the stored value, and
		// re-picking the displayed country fires no change event, so the error
		// could never be cleared.
		const selectedCountry = typeof value === 'string' ? value : '';
		if (
			selectedCountry &&
			! objectHasProp( countries, selectedCountry )
		) {
			const allCountries = getSetting< Record< string, string > >(
				'countries',
				{}
			);
			countryOptions.push( {
				value: selectedCountry,
				label: decodeEntities(
					allCountries[ selectedCountry ] || selectedCountry
				),
				disabled: true,
			} );
		}
		return countryOptions;
	}, [ countries, value ] );

	return (
		<Select
			className={ clsx( className, 'wc-block-components-country-input' ) }
			id={ id }
			errorId={ errorId }
			label={ label || '' }
			onChange={ onChange }
			options={ options }
			value={ value }
			required={ required }
			autoComplete={ autoComplete }
		/>
	);
};

export default CountryInput;
