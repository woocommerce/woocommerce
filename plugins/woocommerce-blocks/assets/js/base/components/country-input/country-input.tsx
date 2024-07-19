/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import type { CountryInputWithCountriesProps } from './CountryInputProps';
import { Select, SelectOption } from '../select';
import { VALIDATION_STORE_KEY } from '../../../data';
import { ValidationInputError } from '../../../../../packages/checkout';

const emptyCountryOption: SelectOption = {
	value: '',
	label: __( 'Select a country', 'woocommerce' ),
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
	errorId,
}: CountryInputWithCountriesProps ): JSX.Element => {
	const options = useMemo< SelectOption[] >( () => {
		return [ emptyCountryOption ].concat(
			Object.entries( countries ).map(
				( [ countryCode, countryName ] ) => ( {
					value: countryCode,
					label: decodeEntities( countryName ),
				} )
			)
		);
	}, [ countries ] );

	const validationError = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return (
			store.getValidationError( errorId || '' ) || {
				hidden: true,
			}
		);
	} );

	return (
		<div
			className={ clsx( className, 'wc-block-components-country-input', {
				'has-error': ! validationError.hidden,
			} ) }
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
			{ validationError && validationError.hidden !== true && (
				<ValidationInputError
					errorMessage={ validationError.message }
				/>
			) }
		</div>
	);
};

export default CountryInput;
