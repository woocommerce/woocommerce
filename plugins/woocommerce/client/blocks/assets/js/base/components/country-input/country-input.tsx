/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';

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
}: CountryInputWithCountriesProps ) => {
	const options = useMemo< SelectOption[] >( () => {
		return Object.entries( countries ).map(
			( [ countryCode, countryName ] ) => ( {
				value: countryCode,
				label: decodeEntities( countryName ),
			} )
		);
	}, [ countries ] );

	// Explain the select cannot be changed if there is only one country available, for screen readers.
	const ariaLabel =
		options.length === 1
			? /* translators: %s is the label for the country input on the Checkout page. */
			  sprintf( __( '%s, cannot be changed', 'woocommerce' ), label )
			: label;

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
			readonly={ options.length === 1 && !! value }
			aria-label={ ariaLabel }
		/>
	);
};

export default CountryInput;
