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
import { useInstanceId } from '@wordpress/compose';
import {
	BaseControl,
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
	const instanceId = useInstanceId( DateTimePickerControl );
	const id = `inspector-date-time-picker-control-${ instanceId }`;
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
		[ onChange, onChangeDebounceWait ]
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
							if ( hasFocusLeftComponent( event ) ) {
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
						setInputString( formattedDate );
					} }
					is12Hour={ is12Hour }
				/>
			) }
		/>
	);
};
