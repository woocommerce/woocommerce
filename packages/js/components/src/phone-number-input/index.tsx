/**
 * External dependencies
 */
import {
	createElement,
	useState,
	useRef,
	useLayoutEffect,
} from '@wordpress/element';
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
	/**
	 *	Phone number with spaces and hyphens.
	 */
	value: string;
	/**
	 * Callback function when the value changes.
	 *
	 * @param value   Phone number with spaces and hyphens. e.g. `+1 234-567-8901`
	 * @param e164    Phone number in E.164 format. e.g. `+12345678901`
	 * @param country Country alpha2 code. e.g. `US`
	 */
	onChange: ( value: string, e164: string, country: string ) => void;
	/**
	 * ID for the input element, to bind a `<label>`.
	 *
	 * @default undefined
	 */
	id?: string;
	/**
	 * Additional class name applied to parent `<div>`.
	 *
	 * @default undefined
	 */
	className?: string;
	/**
	 * Render function for the selected country.
	 * Displays the country flag and code by default.
	 *
	 * @default defaultSelectedRender
	 */
	selectedRender?: ( country: Country ) => React.ReactNode;
	/**
	 * Render function for each country in the dropdown.
	 * Displays the country flag, name, and code by default.
	 *
	 * @default defaultItemRender
	 */
	itemRender?: ( country: Country ) => React.ReactNode;
	/**
	 * Render function for the dropdown arrow.
	 * Displays a chevron down icon by default.
	 *
	 * @default defaultArrowRender
	 */
	arrowRender?: () => React.ReactNode;
}

const { countries, countryCodes } = parseData( data );

/**
 * An international phone number input with a country code select and a phone textfield which supports numbers, spaces and hyphens. And returns the full number as it is, in E.164 format, and the selected country alpha2.
 */
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
		onChange( number, numberToE164( number ), code );
	};

	const handleSelect = ( code: string ) => {
		setCountryKey( code );
		handleChange( code, phoneNumber );
	};

	const handleInput = ( event: React.ChangeEvent< HTMLInputElement > ) => {
		handleChange( countryKey, sanitizeInput( event.target.value ) );
	};

	const handleKeyDown = (
		event: React.KeyboardEvent< HTMLInputElement >
	) => {
		const pos = inputRef.current?.selectionStart || 0;
		const newValue =
			phoneNumber.slice( 0, pos ) + event.key + phoneNumber.slice( pos );
		if ( /[- ]{2,}/.test( newValue ) ) {
			event.preventDefault();
		}
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
				onKeyDown={ handleKeyDown }
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
