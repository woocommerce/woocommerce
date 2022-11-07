/**
 * External dependencies
 */
import { format as formatDate } from '@wordpress/date';
import {
	createElement,
	useCallback,
	useState,
	useEffect,
	useMemo,
	useRef,
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
} & Omit< React.HTMLAttributes< HTMLDivElement >, 'onChange' >;

export const DateTimePickerControl: React.FC< DateTimePickerControlProps > = ( {
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
}: DateTimePickerControlProps ) => {
	const instanceId = useInstanceId( DateTimePickerControl );
	const id = `inspector-date-time-picker-control-${ instanceId }`;
	const inputControl = useRef< InputControl >();

	const [ inputString, setInputString ] = useState( '' );

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
			const newDateTime = maybeForceTime(
				isUserTypedInput
					? parseAsLocalDateTime( newInputString )
					: parseAsISODateTime( newInputString, true )
			);
			const isDateTimeSame = newDateTime.isSame( inputStringDateTime );

			if ( isUserTypedInput ) {
				setInputString( newInputString );
			} else if ( ! isDateTimeSame ) {
				setInputString( formatDateTimeForDisplay( newDateTime ) );
			}

			if (
				typeof onChangeRef.current === 'function' &&
				! isDateTimeSame
			) {
				onChangeRef.current(
					formatDateTimeAsISO( newDateTime ),
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

	function getUserInputOrUpdatedCurrentDate() {
		const newDateTime = maybeForceTime(
			parseAsISODateTime( currentDate, false )
		);

		if (
			! newDateTime.isValid() ||
			newDateTime.isSame(
				maybeForceTime( parseAsLocalDateTime( inputString ) )
			)
		) {
			// keep the input string as the user entered it
			return inputString;
		}

		return formatDateTimeForDisplay( newDateTime );
	}

	return (
		<Dropdown
			className={ classNames(
				'woocommerce-date-time-picker-control',
				className
			) }
			position="bottom left"
			focusOnMount={ false }
			// @ts-expect-error `onToggle` does exist.
			onToggle={ ( willOpen ) => {
				if ( ! willOpen && typeof onBlur === 'function' ) {
					onBlur();
				}
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<BaseControl id={ id } label={ label } help={ help }>
					<InputControl
						id={ id }
						ref={ inputControl }
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
								onToggle(); // hide the dropdown
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
				className: 'woocommerce-date-time-picker-control__popover',
			} }
			renderContent={ () => {
				const Picker = isDateOnlyPicker ? DatePicker : WpDateTimePicker;
				const inputDateTime = parseAsLocalDateTime( inputString );

				return (
					<Picker
						currentDate={
							inputDateTime.isValid()
								? formatDateTimeAsISO( inputDateTime )
								: undefined
						}
						onChange={ ( newDateTimeISOString: string ) =>
							setInputStringAndMaybeCallOnChange(
								newDateTimeISOString,
								false
							)
						}
						is12Hour={ is12HourPicker }
					/>
				);
			} }
		/>
	);
};
