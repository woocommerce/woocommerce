/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { ValidationInputError } from '@woocommerce/blocks-components';

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
	label,
	onChange,
	value = '',
	autoComplete = 'off',
	required = false,
	errorId,
}: CountryInputWithCountriesProps ): JSX.Element => {
	const emptyCountryOption: SelectOption = {
		value: '',
		label: sprintf(
			// translators: %s will be label of the country input. For example "country/region".
			__( 'Select a %s', 'woocommerce' ),
			label?.toLowerCase()
		),
		disabled: true,
	};
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
