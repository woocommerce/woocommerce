/**
 * External dependencies
 */
import { Ref } from 'react';
import { format as formatDate } from '@wordpress/date';
import {
	createElement,
	useCallback,
	useState,
	useEffect,
	useMemo,
	useRef,
	forwardRef,
} from '@wordpress/element';
import { Icon, calendar } from '@wordpress/icons';
import moment, { Moment } from 'moment';
import classNames from 'classnames';
import { sprintf, __ } from '@wordpress/i18n';
import { useDebounce, useInstanceId } from '@wordpress/compose';
import {
	BaseControl,
	DatePicker,
	DateTimePicker as WpDateTimePicker,
	Dropdown,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

// PHP style formatting:
// https://wordpress.org/support/article/formatting-date-and-time/
export const defaultDateFormat = 'm/d/Y';
export const default12HourDateTimeFormat = 'm/d/Y h:i a';
export const default24HourDateTimeFormat = 'm/d/Y H:i';

export type DateTimePickerControlOnChangeHandler = (
	dateTimeIsoString: string,
	isValid: boolean
) => void;

export type DateTimePickerControlProps = {
	currentDate?: string | null;
	dateTimeFormat?: string;
	disabled?: boolean;
	isDateOnlyPicker?: boolean;
	is12HourPicker?: boolean;
	timeForDateOnly?: 'start-of-day' | 'end-of-day';
	onChange?: DateTimePickerControlOnChangeHandler;
	onBlur?: () => void;
	label?: string;
	placeholder?: string;
	help?: string | null;
	onChangeDebounceWait?: number;
	popoverProps?: Record< string, boolean | string >;
} & Omit< React.HTMLAttributes< HTMLInputElement >, 'onChange' >;

export const DateTimePickerControl = forwardRef(
	function ForwardedDateTimePickerControl(
		{
			currentDate,
			isDateOnlyPicker = false,
			is12HourPicker = true,
			timeForDateOnly = 'start-of-day',
			dateTimeFormat,
			disabled = false,
			onChange,
			onBlur,
			label,
			placeholder,
			help,
			className = '',
			onChangeDebounceWait = 500,
			popoverProps = {},
			...props
		}: DateTimePickerControlProps,
		ref: Ref< HTMLInputElement >
	) {
		const id = useInstanceId(
			DateTimePickerControl,
			'inspector-date-time-picker-control',
			props.id
		) as string;
		const inputControl = useRef< InputControl >();

		const displayFormat = useMemo( () => {
			if ( dateTimeFormat ) {
				return dateTimeFormat;
			}

			if ( isDateOnlyPicker ) {
				return defaultDateFormat;
			}

			if ( is12HourPicker ) {
				return default12HourDateTimeFormat;
			}

			return default24HourDateTimeFormat;
		}, [ dateTimeFormat, isDateOnlyPicker, is12HourPicker ] );

		function parseAsISODateTime(
			dateString?: string | null,
			assumeLocalTime = false
		): Moment {
			return assumeLocalTime
				? moment( dateString, moment.ISO_8601, true ).utc()
				: moment.utc( dateString, moment.ISO_8601, true );
		}

		function parseAsLocalDateTime( dateString: string | null ): Moment {
			// parse input date string as local time;
			// be lenient of user input and try to match any format Moment can
			return moment( dateString );
		}

		const maybeForceTime = useCallback(
			( momentDate: Moment ) => {
				if ( ! isDateOnlyPicker || ! momentDate.isValid() )
					return momentDate;

				// We want to set to the start/end of the local time, so
				// we need to put our Moment instance into "local" mode
				const updatedMomentDate = momentDate.clone().local();

				if ( timeForDateOnly === 'start-of-day' ) {
					updatedMomentDate.startOf( 'day' );
				} else if ( timeForDateOnly === 'end-of-day' ) {
					updatedMomentDate.endOf( 'day' );
				}

				return updatedMomentDate;
			},
			[ isDateOnlyPicker, timeForDateOnly ]
		);

		function hasFocusLeftInputAndDropdownContent(
			event: React.FocusEvent< HTMLInputElement >
		): boolean {
			return ! event.relatedTarget?.closest(
				'.components-dropdown__content'
			);
		}

		const formatDateTimeForDisplay = useCallback(
			( dateTime: Moment ) => {
				return dateTime.isValid()
					? formatDate( displayFormat, dateTime.local() )
					: dateTime.creationData().input?.toString() || '';
			},
			[ displayFormat ]
		);

		function formatDateTimeAsISO( dateTime: Moment ): string {
			return dateTime.isValid()
				? dateTime.utc().toISOString()
				: dateTime.creationData().input?.toString() || '';
		}

		const currentDateTime = parseAsISODateTime( currentDate );

		const [ inputString, setInputString ] = useState(
			currentDateTime.isValid()
				? formatDateTimeForDisplay( maybeForceTime( currentDateTime ) )
				: ''
		);

		const inputStringDateTime = useMemo( () => {
			return maybeForceTime( parseAsLocalDateTime( inputString ) );
		}, [ inputString, maybeForceTime ] );

		// We keep a ref to the onChange prop so that we can be sure we are
		// always using the more up-to-date value, even if it changes
		// it while a debounced onChange handler is in progress
		const onChangeRef = useRef<
			DateTimePickerControlOnChangeHandler | undefined
		>();
		useEffect( () => {
			onChangeRef.current = onChange;
		}, [ onChange ] );

		const setInputStringAndMaybeCallOnChange = useCallback(
			( newInputString: string, isUserTypedInput: boolean ) => {
				// InputControl doesn't fire an onChange if what the user has typed
				// matches the current value of the input field. To get around this,
				// we pull the value directly out of the input field. This fixes
				// the issue where the user ends up typing the same value. Unless they
				// are typing extra slow. Without this workaround, we miss the last
				// character typed.
				const lastTypedValue = inputControl.current.value;

				const newDateTime = maybeForceTime(
					isUserTypedInput
						? parseAsLocalDateTime( lastTypedValue )
						: parseAsISODateTime( newInputString, true )
				);
				const isDateTimeSame =
					newDateTime.isSame( inputStringDateTime );

				if ( isUserTypedInput ) {
					setInputString( lastTypedValue );
				} else if ( ! isDateTimeSame ) {
					setInputString( formatDateTimeForDisplay( newDateTime ) );
				}

				if (
					typeof onChangeRef.current === 'function' &&
					! isDateTimeSame
				) {
					onChangeRef.current(
						newDateTime.isValid()
							? formatDateTimeAsISO( newDateTime )
							: lastTypedValue,
						newDateTime.isValid()
					);
				}
			},
			[ formatDateTimeForDisplay, inputStringDateTime, maybeForceTime ]
		);

		const debouncedSetInputStringAndMaybeCallOnChange = useDebounce(
			setInputStringAndMaybeCallOnChange,
			onChangeDebounceWait
		);

		function focusInputControl() {
			if ( inputControl.current ) {
				inputControl.current.focus();
			}
		}

		const getUserInputOrUpdatedCurrentDate = useCallback( () => {
			if ( currentDate !== undefined ) {
				const newDateTime = maybeForceTime(
					parseAsISODateTime( currentDate, false )
				);

				if ( ! newDateTime.isValid() ) {
					// keep the invalid string, so the user can correct it
					return currentDate;
				}

				if ( ! newDateTime.isSame( inputStringDateTime ) ) {
					return formatDateTimeForDisplay( newDateTime );
				}

				// the new currentDate is the same date as the inputString,
				// so keep exactly what the user typed in
				return inputString;
			}

			// the component is uncontrolled (not using currentDate),
			// so just return the input string
			return inputString;
		}, [
			currentDate,
			formatDateTimeForDisplay,
			inputString,
			maybeForceTime,
		] );

		// We keep a ref to the onBlur prop so that we can be sure we are
		// always using the more up-to-date value, otherwise, we get in
		// any infinite loop when calling onBlur
		const onBlurRef = useRef< () => void >();
		useEffect( () => {
			onBlurRef.current = onBlur;
		}, [ onBlur ] );

		const callOnBlurIfDropdownIsNotOpening = useCallback( ( willOpen ) => {
			if (
				! willOpen &&
				typeof onBlurRef.current === 'function' &&
				inputControl.current
			) {
				// in case the component is blurred before a debounced
				// change has been processed, immediately set the input string
				// to the current value of the input field, so that
				// it won't be set back to the pre-change value
				setInputStringAndMaybeCallOnChange(
					inputControl.current.value,
					true
				);
				onBlurRef.current();
			}
		}, [] );

		return (
			<Dropdown
				className={ classNames(
					'woocommerce-date-time-picker-control',
					className
				) }
				focusOnMount={ false }
				// @ts-expect-error `onToggle` does exist.
				onToggle={ callOnBlurIfDropdownIsNotOpening }
				renderToggle={ ( { isOpen, onClose, onToggle } ) => (
					<BaseControl id={ id } label={ label } help={ help }>
						<InputControl
							{ ...props }
							id={ id }
							ref={ ( element: HTMLInputElement ) => {
								inputControl.current = element;
								if ( typeof ref === 'function' ) {
									ref( element );
								}
							} }
							disabled={ disabled }
							value={ getUserInputOrUpdatedCurrentDate() }
							onChange={ ( newValue: string ) =>
								debouncedSetInputStringAndMaybeCallOnChange(
									newValue,
									true
								)
							}
							onBlur={ (
								event: React.FocusEvent< HTMLInputElement >
							) => {
								if (
									hasFocusLeftInputAndDropdownContent( event )
								) {
									// close the dropdown, which will also trigger
									// the component's onBlur to be called
									onClose();
								}
							} }
							suffix={
								<Icon
									icon={ calendar }
									className="calendar-icon woocommerce-date-time-picker-control__input-control__suffix"
									onClick={ focusInputControl }
									size={ 16 }
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
					</BaseControl>
				) }
				popoverProps={ {
					anchor: inputControl.current,
					className: 'woocommerce-date-time-picker-control__popover',
					placement: 'bottom-start',
					...popoverProps,
				} }
				renderContent={ () => {
					const Picker = isDateOnlyPicker
						? DatePicker
						: WpDateTimePicker;

					return (
						<Picker
							// @ts-expect-error null is valid for currentDate
							currentDate={
								inputStringDateTime.isValid()
									? formatDateTimeAsISO( inputStringDateTime )
									: null
							}
							onChange={ ( newDateTimeISOString: string ) =>
								setInputStringAndMaybeCallOnChange(
									newDateTimeISOString,
									false
								)
							}
							is12Hour={ is12HourPicker }
							// Opt out of the Reset and Help buttons, as they are going to be removed.
							// These properties are removed in @wordpress/components 25.0.0 (Gutenberg 15.9.0).
							__nextRemoveResetButton
							__nextRemoveHelpButton
						/>
					);
				} }
			/>
		);
	}
);
