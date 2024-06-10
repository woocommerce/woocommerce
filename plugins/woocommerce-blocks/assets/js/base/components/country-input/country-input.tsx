/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';
import type { CountryInputWithCountriesProps } from './CountryInputProps';
import { Select } from '../select';

export const CountryInput = ( {
	className,
	countries,
	id,
	label,
	onChange,
	value = '',
	autoComplete = 'off',
	required = false,
	errorId,
	errorMessage = __( 'Please select a country', 'woocommerce' ),
}: CountryInputWithCountriesProps ): JSX.Element => {
	const options = useMemo(
		() =>
			Object.entries( countries ).map(
				( [ countryCode, countryName ] ) => ( {
					value: countryCode,
					label: decodeEntities( countryName ),
				} )
			),
		[ countries ]
	);

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
