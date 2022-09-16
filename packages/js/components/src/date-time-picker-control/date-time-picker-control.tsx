/**
 * External dependencies
 */
import { Fragment } from 'react';
import {
	createElement,
	useState,
	useEffect,
	useCallback,
	useRef,
} from '@wordpress/element';
import { Icon, calendar } from '@wordpress/icons';
import moment, { Moment } from 'moment';
import { debounce } from 'lodash';
import classNames from 'classnames';
import { sprintf, __ } from '@wordpress/i18n';
import {
	Dropdown,
	DateTimePicker as WpDateTimePicker,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

export type DateTimePickerControlProps = {
	currentDate?: string | null;
	dateTimeFormat?: string;
	disabled?: boolean;
	is12Hour?: boolean;
	onChange?: ( date: string, isValid: boolean ) => void;
	onBlur?: () => void;
	label?: string;
	placeholder?: string;
	help?: string | null;
	onChangeDebounceWait?: number;
} & Omit< React.HTMLAttributes< HTMLDivElement >, 'onChange' >;

export const DateTimePickerControl: React.FC< DateTimePickerControlProps > = ( {
	currentDate,
	is12Hour = true,
	dateTimeFormat = is12Hour ? 'MM/DD/YYYY h:mm a' : 'MM/DD/YYYY H:MM',
	disabled = false,
	onChange,
	onBlur,
	label,
	placeholder,
	help,
	className = '',
	onChangeDebounceWait = 500,
}: DateTimePickerControlProps ) => {
	const inputControl = useRef< InputControl >();

	const [ inputString, setInputString ] = useState( '' );
	const [ lastValidDate, setLastValidDate ] = useState< Moment | null >(
		null
	);

	function parseMomentIso( dateString?: string | null ): Moment {
		return moment( dateString, moment.ISO_8601, true );
	}

	function parseMoment( dateString?: string | null ): Moment {
		return moment( dateString, dateTimeFormat );
	}

	function formatMomentIso( momentDate: Moment ): string {
		return momentDate.toISOString();
	}

	function formatMoment( momentDate: Moment ): string {
		return momentDate.format( dateTimeFormat );
	}

	function hasFocusLeftComponent(
		event: React.FocusEvent< HTMLInputElement >
	): boolean {
		const dropdownParentOfTargetReceivingFocus =
			event.relatedTarget?.closest( '.components-dropdown' );
		const dropdownParentOfTargetLosingFocus = event.currentTarget?.closest(
			'.components-dropdown'
		);

		return (
			! dropdownParentOfTargetReceivingFocus ||
			dropdownParentOfTargetReceivingFocus !==
				dropdownParentOfTargetLosingFocus
		);
	}

	const debouncedOnChange = useCallback(
		debounce( ( dateTime: string, isValid: boolean ) => {
			if ( onChange ) {
				onChange( dateTime, isValid );
			}
		}, onChangeDebounceWait ),
		[ onChangeDebounceWait ]
	);

	function change( newInputString: string ) {
		setInputString( newInputString );
		const newDateTime = parseMoment( newInputString );
		const isValid = newDateTime.isValid();

		if ( isValid ) {
			setLastValidDate( newDateTime );
		}

		debouncedOnChange(
			isValid ? formatMomentIso( newDateTime ) : newInputString,
			isValid
		);
	}

	function blur() {
		if ( onBlur ) {
			onBlur();
		}
	}

	function focusInputControl() {
		if ( inputControl.current ) {
			inputControl.current.focus();
		}
	}

	const isInitialUpdate = useRef( true );
	useEffect( () => {
		// Don't trigger the change handling on the initial update of the component
		if ( isInitialUpdate.current ) {
			isInitialUpdate.current = false;
			return;
		}

		const newDate = parseMomentIso( currentDate );

		if ( newDate.isValid() ) {
			change( formatMoment( newDate ) );
		} else {
			change( currentDate || '' );
		}
	}, [ currentDate, dateTimeFormat ] );

	return (
		<Dropdown
			className={ classNames(
				'woocommerce-date-time-picker-control',
				className
			) }
			position="bottom center"
			focusOnMount={ false }
			// @ts-expect-error `onToggle` does exist.
			onToggle={ ( willOpen ) => {
				if ( ! willOpen ) {
					blur();
				}
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<>
					<InputControl
						ref={ inputControl }
						disabled={ disabled }
						value={ inputString }
						onChange={ change }
						onBlur={ (
							event: React.FocusEvent< HTMLInputElement >
						) => {
							if ( hasFocusLeftComponent( event ) ) {
								onToggle(); // hide the dropdown
							}
						} }
						label={ label }
						suffix={
							<Icon
								icon={ calendar }
								className="calendar-icon"
								onClick={ focusInputControl }
							/>
						}
						placeholder={ placeholder }
						describedBy={ sprintf(
							/* translators: A datetime format */
							__(
								'Date input describing a selected date in format %s',
								'woocommerce'
							),
							dateTimeFormat
						) }
						onFocus={ () => {
							if ( isOpen ) {
								return; // the dropdown is already open, do we don't need to do anything
							}

							onToggle(); // show the dropdown
						} }
						aria-expanded={ isOpen }
					/>
					{ help && <p>{ help }</p> }
				</>
			) }
			renderContent={ () => (
				<WpDateTimePicker
					currentDate={
						lastValidDate
							? formatMomentIso( lastValidDate )
							: undefined
					}
					onChange={ ( date: string ) => {
						const formattedDate = formatMoment(
							parseMomentIso( date )
						);
						setInputString( formattedDate );
					} }
					is12Hour={ is12Hour }
				/>
			) }
		/>
	);
};
