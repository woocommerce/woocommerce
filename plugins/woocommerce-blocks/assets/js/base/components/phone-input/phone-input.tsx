/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import classnames from 'classnames';
import { Label } from '@woocommerce/blocks-components';
import ReactCountryFlag from 'react-country-flag';
import { getSetting } from '@woocommerce/settings';
import { useSelect as useSelectSearch } from 'react-select-search';
import parsePhoneNumber, {
	getCountryCallingCode,
	isSupportedCountry,
	isPossiblePhoneNumber,
	parseIncompletePhoneNumber,
	formatIncompletePhoneNumber,
} from 'libphonenumber-js';

import IntlTelInput from 'intl-tel-input/react/build/IntlTelInput.esm';

/**
 * Internal dependencies
 */
import './style.scss';
import type { PhoneInputProps } from './props';

const countries = getSetting< Record< string, string > >( 'countries', {} );
const formatOptions = [
	{
		value: '',
		name: 'International',
	},
	...Object.entries( countries )
		.map( ( [ countryCode, countryName ] ) => {
			if ( ! isSupportedCountry( countryCode ) ) {
				return null;
			}
			const code = getCountryCallingCode( countryCode );
			return {
				value: countryCode,
				name: countryName + ' +' + code,
			};
		} )
		.filter( Boolean ),
];

const DialingCode = ( { countryCode = '' } ) => {
	return (
		<span className="country_dialling_code">
			+{ getCountryCallingCode( countryCode ) }
		</span>
	);
};

const CountryName = ( { countryCode = '' } ) => {
	return (
		<span className="country_name">{ countries[ countryCode ] || '' }</span>
	);
};

export const PhoneInput = ( {
	country,
	className,
	id,
	ariaLabel,
	label,
	screenReaderLabel,
	disabled,
	autoComplete = 'off',
	value = '',
	onChange,
	required = false,
	onBlur = () => {
		/* Do nothing */
	},
	feedback,
	...rest
}: PhoneInputProps ): JSX.Element => {
	const [ isActive, setIsActive ] = useState( false );
	const [ currentCountryCode, setCurrentCountryCode ] = useState( country );
	const [ phoneNumber, setPhoneNumber ] = useState< string >( () => {
		const decodedValue = decodeEntities( value ) as string;
		const parsedPhoneNumber = parsePhoneNumber( decodedValue, country );
		setCurrentCountryCode( parsedPhoneNumber?.country || country );
		return parsedPhoneNumber?.nationalNumber || decodedValue;
	} );

	// Manages state of the country code dropdown.
	const [ snapshot, valueProps, optionProps ] = useSelectSearch( {
		options: formatOptions,
		value: currentCountryCode || '',
		search: true,
		onChange: ( newCountryCode ) => {
			const phoneFormatter = parsePhoneNumber(
				phoneNumber,
				newCountryCode
			);
			setCurrentCountryCode( newCountryCode );
			setPhoneNumber( phoneFormatter?.formatNational() || phoneNumber );
			onChange( phoneFormatter?.formatInternational() || phoneNumber );
		},
	} );

	return (
		<div
			className={ classnames(
				'wc-block-components-phone-input',
				className,
				{
					'is-active': isActive || value,
				}
			) }
		>
			<div className="field-wrapper">
				<IntlTelInput
					initialValue={ value }
					onChangeNumber={ ( newValue ) => onChange( newValue ) }
					initOptions={ {
						initialCountry: country,
					} }
				/>
			</div>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel || label }
				wrapperElement="label"
				wrapperProps={ {
					htmlFor: id,
					className: 'field-label',
				} }
			/>
			{ feedback }
			<div
				className={ classnames( 'select-search-container', {
					'select-search-is-focused': snapshot.focus,
					'visually-hidden': ! snapshot.focus,
				} ) }
			>
				<div className="select-search-value">
					<input
						type="search"
						className="select-search-input"
						{ ...valueProps }
					/>
				</div>
				<div className="select-search-select">
					{ snapshot.focus && (
						<ul className="select-search-options">
							{ snapshot.options.map( ( option ) => (
								<li
									key={ option.value }
									className="select-search-row"
									role="menuitem"
									data-index={ option.index }
								>
									<button
										type="button"
										{ ...optionProps }
										value={ option.value }
										className={ classnames(
											'select-search-option',
											{
												'select-search-is-highlighted':
													snapshot.highlighted ===
													option.index,
											}
										) }
										tabIndex={ -1 }
									>
										{ !! option.value ? (
											<>
												<ReactCountryFlag
													countryCode={ option.value }
												/>{ ' ' }
												<CountryName
													countryCode={ option.value }
												/>{ ' ' }
												<DialingCode
													countryCode={ option.value }
												/>
											</>
										) : (
											'International'
										) }
									</button>
								</li>
							) ) }
						</ul>
					) }
				</div>
			</div>
		</div>
	);
};

export default PhoneInput;
