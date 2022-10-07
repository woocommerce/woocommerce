/**
 * External dependencies
 */
import {
	createElement,
	useState,
	useEffect,
	useCallback,
	useRef,
} from '@wordpress/element';
import { Icon, calendar } from '@wordpress/icons';
import moment, { Moment } from 'moment';
import classNames from 'classnames';
import { sprintf, __ } from '@wordpress/i18n';
import { useDebounce, useInstanceId } from '@wordpress/compose';
import {
	BaseControl,
	Dropdown,
	DateTimePicker as WpDateTimePicker,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

export const default12HourDateTimeFormat = 'MM/DD/YYYY h:mm a';
export const default24HourDateTimeFormat = 'MM/DD/YYYY H:mm';

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
	dateTimeFormat = is12Hour
		? default12HourDateTimeFormat
		: default24HourDateTimeFormat,
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

	function hasFocusLeftInputAndDropdownContent(
		event: React.FocusEvent< HTMLInputElement >
	): boolean {
		return ! event.relatedTarget?.closest(
			'.components-dropdown__content'
		);
	}

	const onChangeCallback = useCallback(
		( newInputString: string, fireOnChange: boolean ) => {
			if ( ! isMounted.current ) return;

			const newDateTime = parseMoment( newInputString );
			const isValid = newDateTime.isValid();

			if ( isValid ) {
				setLastValidDate( newDateTime );
			}

			if ( fireOnChange && typeof onChange === 'function' ) {
				onChange(
					isValid ? formatMomentIso( newDateTime ) : newInputString,
					isValid
				);
			}
		},
		[ onChange ]
	);

	const debouncedOnChange = useDebounce(
		onChangeCallback,
		onChangeDebounceWait
	);

	function change( newInputString: string ) {
		setInputString( newInputString );
		debouncedOnChange( newInputString, true );
	}

	function changeImmediate( newInputString: string, fireOnChange: boolean ) {
		setInputString( newInputString );
		onChangeCallback( newInputString, fireOnChange );
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
		const fireOnChange = ! isInitialUpdate.current;
		if ( isInitialUpdate.current ) {
			isInitialUpdate.current = false;
		}

		const newDate = parseMomentIso( currentDate );

		if ( newDate.isValid() ) {
			changeImmediate( formatMoment( newDate ), fireOnChange );
		} else {
			changeImmediate( currentDate || '', fireOnChange );
		}
	}, [ currentDate, dateTimeFormat ] );

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
						changeImmediate( formattedDate, true );
					} }
					is12Hour={ is12Hour }
				/>
			) }
		/>
	);
};
