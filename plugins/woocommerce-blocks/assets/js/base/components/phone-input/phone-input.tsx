/**
 * External dependencies
 */
import { useRef, useState } from '@wordpress/element';
//import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import classnames from 'classnames';
import InputMask from 'react-input-mask';
import { Label } from '@woocommerce/blocks-components';
import ReactCountryFlag from 'react-country-flag';
import { getSetting } from '@woocommerce/settings';
import SelectSearch, { useSelect } from 'react-select-search';

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
		code: '+1',
	},
	CA: {
		placeholder: '(555) 555-5555',
		mask: '(999) 999-9999',
		code: '+1',
	},
	GB: {
		placeholder: '01234 567890',
		mask: '99999 999999',
		code: '+44',
	},
};

const formatOptions = Object.entries( formats ).map( ( [ format ] ) => ( {
	value: format,
	name: countries[ format ] + ' ' + format + ' ' + formats[ format ].code,
} ) );

const DiallingCode = ( { countryCode = '' } ) => {
	return (
		<span className="country_dialling_code">
			{ formats[ countryCode ]?.code || '' }
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
	type = 'text',
	ariaLabel,
	label,
	screenReaderLabel,
	disabled,
	autoCapitalize = 'off',
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
	const [ snapshot, valueProps, optionProps ] = useSelect( {
		options: formatOptions,
		value: currentCountryCode,
		search: true,
		onChange: ( newValue ) => {
			setCurrentCountryCode( newValue );
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
					<ReactCountryFlag countryCode={ currentCountryCode } />{ ' ' }
					<DiallingCode countryCode={ currentCountryCode } />
				</button>
				{
					<InputMask
						type="tel"
						mask={ formats[ currentCountryCode ]?.mask }
						id={ id }
						value={ decodeEntities( value ) }
						inputRef={ inputRef }
						autoCapitalize={ autoCapitalize }
						autoComplete={ autoComplete }
						onChange={ ( event ) => {
							onChange( event.target.value );
						} }
						onFocus={ () => setIsActive( true ) }
						onBlur={ ( event ) => {
							onBlur( event.target.value );
							setIsActive( false );
						} }
						aria-label={ ariaLabel || label }
						disabled={ disabled }
						required={ required }
						{ ...rest }
					/>
				}
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
										<DiallingCode
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

/*
<ComboboxControl
				label={ __( 'Phone Number Country', 'woocommerce' ) }
				hideLabelFromVision={ true }
				allowReset={ false }
				onChange={ ( newFormat: string ) => {
					setFormat( newFormat );
				} }
				className={ 'phone-country-combobox' }
				options={ formatOptions }
				value={ format }
				autoComplete={ 'country' }
				__experimentalRenderItem={ ( { item } ) => {
					return (
						<>
							<ReactCountryFlag countryCode={ item.value } />{ ' ' }
							<span className="country_name">
								{ countries[ item.value ] || '' }
							</span>{ ' ' }
							<span className="country_dialling_code">
								{ formats[ item.value ]?.code || '' }
							</span>
						</>
					);
				} }
			/>
			*/
export default PhoneInput;
