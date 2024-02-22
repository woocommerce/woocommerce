/**
 * External dependencies
 */
import { useRef, useState, useCallback, useEffect } from '@wordpress/element';
//import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import classnames from 'classnames';
import { Label } from '@woocommerce/blocks-components';
import ReactCountryFlag from 'react-country-flag';
import { getSetting } from '@woocommerce/settings';
import { useSelect as useSelectSearch } from 'react-select-search';
import parsePhoneNumber, { AsYouType, CountryCode } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import './style.scss';
import type { PhoneInputProps } from './props';

const countries = getSetting< Record< string, string > >( 'countries', {} );
const formats = {
	US: {
		placeholder: '(555) 555-5555',
		mask: '(999) 999-9999',
		code: 1,
	},
	CA: {
		placeholder: '(555) 555-5555',
		mask: '(999) 999-9999',
		code: 1,
	},
	GB: {
		placeholder: '01234 567890',
		mask: '99999 999999',
		code: 44,
	},
};

const formatOptions = Object.entries( formats ).map( ( [ format ] ) => ( {
	value: format,
	name: countries[ format ] + ' ' + format + ' +' + formats[ format ].code,
} ) );

const getDialingCodeByCountry = ( countryCode: string | undefined ) => {
	if ( countryCode !== undefined && formats[ countryCode ] ) {
		return '+' + formats[ countryCode ].code;
	}
};

const DialingCode = ( { countryCode = '' } ) => {
	return (
		<span className="country_dialling_code">
			{ getDialingCodeByCountry( countryCode ) }
		</span>
	);
};

const CountryName = ( { countryCode = '' } ) => {
	return (
		<span className="country_name">{ countries[ countryCode ] || '' }</span>
	);
};

const removeDialingCode = ( inputString: string, dialingCode: string ) => {
	const regex = new RegExp( `\\${ dialingCode }`, 'g' );

	return inputString.replace( regex, '' ).trimStart();
};

export const PhoneInput = ( {
	country,
	className,
	id,
	type = 'text',
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
	const inputRef = useRef< HTMLInputElement >( null );
	const [ isActive, setIsActive ] = useState( false );

	const [ currentCountryCode, setCurrentCountryCode ] = useState( country );
	const [ phoneNumber, setPhoneNumber ] = useState( () => {
		const decodedValue = decodeEntities( value );
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
				newCountryCode as CountryCode
			);
			setCurrentCountryCode( newCountryCode as CountryCode );
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
				<button
					type="button"
					className="phone-input-country-button"
					onClick={ () => {
						valueProps?.ref?.current?.focus();
					} }
				>
					{ !! currentCountryCode ? (
						<>
							<ReactCountryFlag
								countryCode={ currentCountryCode }
							/>
							<DialingCode countryCode={ currentCountryCode } />
						</>
					) : (
						<>N/A</>
					) }
				</button>
				<input
					type="tel"
					id={ id }
					value={ phoneNumber }
					autoComplete={ autoComplete }
					onChange={ ( event ) => {
						const newValue = event.target.value;
						const phoneFormatter = parsePhoneNumber(
							newValue,
							currentCountryCode
						);

						setPhoneNumber( newValue );

						if (
							phoneFormatter?.country &&
							phoneFormatter?.country !== currentCountryCode
						) {
							setCurrentCountryCode( phoneFormatter?.country );
						}

						onChange(
							phoneFormatter?.formatInternational() || newValue
						);
					} }
					onFocus={ () => setIsActive( true ) }
					onBlur={ () => {
						const phoneFormatter = parsePhoneNumber(
							value,
							currentCountryCode
						);
						if ( phoneFormatter?.country ) {
							setPhoneNumber( phoneFormatter?.formatNational() );
						}
						onBlur( value );
						setIsActive( false );
					} }
					aria-label={ ariaLabel || label }
					disabled={ disabled }
					required={ required }
					{ ...rest }
				/>
				<input type="text" ref={ inputRef } value={ value } />
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
										<ReactCountryFlag
											countryCode={ option.value }
										/>{ ' ' }
										<CountryName
											countryCode={ option.value }
										/>{ ' ' }
										<DialingCode
											countryCode={ option.value }
										/>
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
