/**
 * External dependencies
 */
import { format as formatDate } from '@wordpress/date';
import {
	createElement,
	useState,
	useEffect,
	useLayoutEffect,
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

	const isMounted = useRef( false );
	useEffect( () => {
		isMounted.current = true;
		return () => {
			isMounted.current = false;
		};
	} );

	const [ inputString, setInputString ] = useState( '' );
	const [ lastValidDate, setLastValidDate ] = useState< Moment | null >(
		null
	);

	const displayFormat = ( () => {
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
	} )();

	function parseMomentIso(
		dateString?: string | null,
		assumeLocalTime = false
	): Moment {
		if ( assumeLocalTime ) {
			return moment( dateString, moment.ISO_8601, true ).utc();
		}

		return moment.utc( dateString, moment.ISO_8601, true );
	}

	function parseMoment( dateString: string | null ): Moment {
		// parse input date string as local time;
		// be lenient of user input and try to match any format Moment can
		return moment( dateString );
	}

	function formatMomentIso( momentDate: Moment ): string {
		return momentDate.utc().toISOString();
	}

	function formatMoment( momentDate: Moment ): string {
		return formatDate( displayFormat, momentDate.local() );
	}

	function maybeForceTime( momentDate: Moment ): Moment {
		if ( ! isDateOnlyPicker ) return momentDate;

		// We want to set to the start/end of the local time, so
		// we need to put our Moment instance into "local" mode
		const updatedMomentDate = momentDate.clone().local();

		if ( timeForDateOnly === 'start-of-day' ) {
			updatedMomentDate.startOf( 'day' );
		} else if ( timeForDateOnly === 'end-of-day' ) {
			updatedMomentDate.endOf( 'day' );
		}

		return updatedMomentDate;
	}

	function hasFocusLeftInputAndDropdownContent(
		event: React.FocusEvent< HTMLInputElement >
	): boolean {
		return ! event.relatedTarget?.closest(
			'.components-dropdown__content'
		);
	}

	// We setup the debounced handling of the input string changes using
	// useRef because useCallback does *not* guarantee that the resulting
	// callback function will not be recreated, even if the dependencies
	// haven't changed (this is because of it's use of useMemo under the
	// hood, which also makes not guarantee). And, even if it did, the
	// equality check for useCallback dependencies is by reference. So, if
	// the "same" function is passed in, but it is a different instance, it
	// will trigger the recreation of the callback.
	//
	// With useDebounce, if the callback function changes, the current
	// debounce is canceled. This results in the callback function never being
	// called.
	//
	// We *need* to ensure that our handler is called at least once,
	// and also that we call the passed in onChange callback.
	//
	// We guarantee this by keeping references to both our handler and the
	// passed in prop.
	//
	// The consumer of DateTimePickerControl should ensure that the
	// function passed into onChange does not change (using references or
	// useCallbackOne). But, even if they do not, and the function changes,
	// things will likely function as expected unless the consumer is doing
	// something really convoluted.
	//
	// See also:
	// - [note regarding useMemo not being a semantic guarantee](https://reactjs.org/docs/hooks-reference.html#usememo)
	// - [useDebounce hook loses function calls if the dependency changes](https://github.com/WordPress/gutenberg/issues/35505)
	// - [useMemoOne and useCallbackOne](https://github.com/alexreardon/use-memo-one)

	const onChangeRef = useRef<
		DateTimePickerControlOnChangeHandler | undefined
	>();
	useLayoutEffect( () => {
		onChangeRef.current = onChange;
	}, [ onChange ] );

	function updateInputString( dateTime: Moment ) {
		const newInputString = dateTime.isValid()
			? formatMoment( dateTime )
			: dateTime.creationData().input?.toString() || '';

		setInputString( newInputString );
	}

	function callOnChange( dateTime: Moment ) {
		const valueToSend = dateTime.isValid()
			? formatMomentIso( dateTime )
			: dateTime.creationData().input?.toString() || '';

		if ( typeof onChangeRef.current === 'function' ) {
			onChangeRef.current( valueToSend, dateTime.isValid() );
		}
	}

	function updateStateWithNewInputString(
		newInputString: string,
		maybeFireOnChange: boolean
	) {
		if ( ! isMounted.current ) return;

		let newDateTime = parseMoment( newInputString );
		const isValid = newDateTime.isValid();

		if ( isValid ) {
			newDateTime = maybeForceTime( newDateTime );
			setLastValidDate( newDateTime );
		}

		// we don't want to reformat what the user manually entered in,
		// so we just set it directly
		setInputString( newInputString );

		if ( maybeFireOnChange && ! newDateTime.isSame( lastValidDate ) ) {
			callOnChange( newDateTime );
		}
	}

	const updateStateWithNewInputStringRef = useRef<
		( newInputString: string, fireOnChange: boolean ) => void
	>( updateStateWithNewInputString );
	// whenever currentDate or timeForDateOnly changes, we need to reset the ref to inputStringChangeHandlerFunction
	// so that we are using the most current values inside of it
	useEffect( () => {
		updateStateWithNewInputStringRef.current =
			updateStateWithNewInputString;
	}, [ currentDate, timeForDateOnly ] );

	const debouncedUpdateStateWithNewInputString = useDebounce(
		updateStateWithNewInputStringRef.current,
		onChangeDebounceWait
	);

	function change( newInputString: string ) {
		debouncedUpdateStateWithNewInputString( newInputString, true );
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

	function updateStateWithNewDateTime(
		newDateTimeIsoString: string,
		assumeLocalTime: boolean,
		maybeFireOnChange: boolean
	) {
		let newDateTime = parseMomentIso(
			newDateTimeIsoString,
			assumeLocalTime
		);
		const isValid = newDateTime.isValid();

		if ( isValid ) {
			newDateTime = maybeForceTime( newDateTime );
			setLastValidDate( newDateTime );
		}

		updateInputString( newDateTime );

		if ( maybeFireOnChange && ! newDateTime.isSame( lastValidDate ) ) {
			callOnChange( newDateTime );
		}
	}

	const isInitialUpdate = useRef( true );
	useEffect( () => {
		updateStateWithNewDateTime(
			currentDate || '',
			false,
			! isInitialUpdate.current
		);
	}, [ currentDate, displayFormat, timeForDateOnly ] );

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
				if ( ! willOpen ) {
					blur();
				}
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<BaseControl id={ id } label={ label } help={ help }>
					<InputControl
						id={ id }
						ref={ inputControl }
						disabled={ disabled }
						value={ inputString }
						onChange={ change }
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
			renderContent={ () => {
				const Picker = isDateOnlyPicker ? DatePicker : WpDateTimePicker;

				return (
					<Picker
						currentDate={
							lastValidDate
								? formatMomentIso( lastValidDate )
								: undefined
						}
						onChange={ ( localDateTimeIsoString: string ) => {
							updateStateWithNewDateTime(
								localDateTimeIsoString,
								true,
								true
							);
						} }
						is12Hour={ is12HourPicker }
					/>
				);
			} }
		/>
	);
};
