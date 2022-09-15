/**
 * External dependencies
 */
import { Fragment } from 'react';
import { createElement, useState, useEffect } from '@wordpress/element';
import { Icon, calendar } from '@wordpress/icons';
import moment, { Moment } from 'moment';
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
	onChange?: ( date: string ) => void;
	onBlur?: () => void;
	label?: string;
	placeholder?: string;
	help?: string | null;
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
}: DateTimePickerControlProps ) => {
	const [ inputString, setInputString ] = useState( '' );
	const [ lastValidDate, setLastValidDate ] = useState< Moment | null >(
		null
	);
	const [ lastSentChange, setLastSentChange ] = useState( '' );

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

	function change() {
		const newDate = parseMoment( inputString );
		setLastSentChange(
			newDate.isValid() ? formatMomentIso( newDate ) : inputString
		);
	}

	function blur() {
		change();

		if ( onBlur ) {
			onBlur();
		}
	}

	useEffect( () => {
		const newDate = parseMomentIso( currentDate );

		if ( newDate.isValid() ) {
			setInputString( formatMoment( newDate ) );
		} else {
			setInputString( currentDate || '' );
		}
	}, [ currentDate, dateTimeFormat ] );

	useEffect( () => {
		const newDate = parseMoment( inputString );

		if ( newDate.isValid() ) {
			setLastValidDate( newDate );
		}
	}, [ inputString ] );

	useEffect( () => {
		if ( onChange ) {
			onChange( lastSentChange );
		}
	}, [ lastSentChange ] );

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
						disabled={ disabled }
						value={ inputString }
						onChange={ setInputString }
						onBlur={ (
							event: React.FocusEvent< HTMLInputElement >
						) => {
							if ( hasFocusLeftComponent( event ) ) {
								onToggle(); // hide the dropdown
							}
						} }
						label={ label }
						suffix={
							<Icon icon={ calendar } className="calendar-icon" />
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
