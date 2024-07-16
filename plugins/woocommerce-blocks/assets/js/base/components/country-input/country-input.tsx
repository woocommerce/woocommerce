/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import type { CountryInputWithCountriesProps } from './CountryInputProps';
import { Select, SelectOption } from '../select';

const emptyCountryOption = {
	label: __( 'Select a country', 'woocommerce' ),
	value: '',
	disabled: true,
};

export const CountryInput = ( {
	className,
	countries,
	id,
	label,
	onChange,
	value = '',
	autoComplete = 'off',
	required = false,
}: CountryInputWithCountriesProps ): JSX.Element => {
	const options = useMemo< SelectOption[] >( () => {
		return [
			emptyCountryOption,
			...Object.entries( countries ).map(
				( [ countryCode, countryName ] ) => ( {
					value: countryCode,
					label: decodeEntities( countryName ),
				} )
			),
		];
	}, [ countries ] );

	return (
		<div
			className={ clsx( className, 'wc-block-components-country-input' ) }
		>
			<Select
				id={ id }
				label={ label || '' }
				onChange={ onChange }
				options={ options }
				value={ value }
				required={ required }
				autoComplete={ autoComplete }
			/>
		</div>
	);
};

export default CountryInput;
