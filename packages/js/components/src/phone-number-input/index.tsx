/**
 * External dependencies
 */
import React, { useState, useRef, useLayoutEffect } from 'react';
import { createElement } from '@wordpress/element';
import { useSelect } from 'downshift';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import data from './data';
import {
	parseData,
	Country,
	sanitizeInput,
	guessCountryKey,
	numberToE164,
} from './utils';
import {
	defaultSelectedRender,
	defaultItemRender,
	defaultArrowRender,
} from './defaults';

interface Props {
	value: string;
	onChange: ( value: string, e164: string, country: string ) => void;
	id?: string;
	className?: string;
	selectedRender?: ( country: Country ) => React.ReactNode;
	itemRender?: ( country: Country ) => React.ReactNode;
	arrowRender?: () => React.ReactNode;
}

const { countries, countryCodes } = parseData( data );

const PhoneNumberInput: React.FC< Props > = ( {
	value,
	onChange,
	id,
	className,
	selectedRender = defaultSelectedRender,
	itemRender = defaultItemRender,
	arrowRender = defaultArrowRender,
} ) => {
	const menuRef = useRef< HTMLButtonElement >( null );
	const inputRef = useRef< HTMLInputElement >( null );

	const [ menuWidth, setMenuWidth ] = useState( 0 );
	const [ countryKey, setCountryKey ] = useState(
		guessCountryKey( value, countryCodes )
	);

	useLayoutEffect( () => {
		if ( menuRef.current ) {
			setMenuWidth( menuRef.current.offsetWidth );
		}
	}, [ menuRef, countryKey ] );

	const phoneNumber = sanitizeInput( value )
		.replace( countries[ countryKey ].code, '' )
		.trimStart();

	const handleChange = ( code: string, number: string ) => {
		// Return value, phone number in E.164 format, and country alpha2 code.
		number = `+${ countries[ code ].code } ${ number }`;
		onChange( number, numberToE164( number ), countryKey );
	};

	const handleSelect = ( code: string ) => {
		setCountryKey( code );
		handleChange( code, phoneNumber );
	};

	const handleInput = ( event: React.ChangeEvent< HTMLInputElement > ) => {
		handleChange( countryKey, sanitizeInput( event.target.value ) );
	};

	const {
		isOpen,
		getToggleButtonProps,
		getMenuProps,
		highlightedIndex,
		getItemProps,
	} = useSelect( {
		id,
		items: Object.keys( countries ),
		initialSelectedItem: countryKey,
		itemToString: ( item ) => countries[ item || '' ].name,
		onSelectedItemChange: ( { selectedItem } ) => {
			if ( selectedItem ) handleSelect( selectedItem );
		},
		stateReducer: ( state, { changes } ) => {
			if ( state.isOpen === true && changes.isOpen === false ) {
				inputRef.current?.focus();
			}

			return changes;
		},
	} );

	return (
		<div
			className={ classNames(
				className,
				'wcpay-component-phone-number-input'
			) }
		>
			<button
				{ ...getToggleButtonProps( {
					ref: menuRef,
					type: 'button',
					className: classNames(
						'wcpay-component-phone-number-input__button'
					),
				} ) }
			>
				{ selectedRender( countries[ countryKey ] ) }
				<span
					className={ classNames(
						'wcpay-component-phone-number-input__button-arrow',
						{ invert: isOpen }
					) }
				>
					{ arrowRender() }
				</span>
			</button>
			<input
				id={ id }
				ref={ inputRef }
				type="text"
				value={ phoneNumber }
				onChange={ handleInput }
				className="wcpay-component-phone-number-input__input"
				style={ { paddingLeft: `${ menuWidth }px` } }
			/>
			<ul
				{ ...getMenuProps( {
					'aria-hidden': ! isOpen,
					className: 'wcpay-component-phone-number-input__menu',
				} ) }
			>
				{ isOpen &&
					Object.keys( countries ).map( ( key, index ) => (
						// eslint-disable-next-line react/jsx-key
						<li
							{ ...getItemProps( {
								key,
								index,
								item: key,
								className: classNames(
									'wcpay-component-phone-number-input__menu-item',
									{ highlighted: highlightedIndex === index }
								),
							} ) }
						>
							{ itemRender( countries[ key ] ) }
						</li>
					) ) }
			</ul>
		</div>
	);
};

export default PhoneNumberInput;
