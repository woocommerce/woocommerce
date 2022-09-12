/**
 * External dependencies
 */
import { Fragment } from 'react';
import { createElement, useState, useEffect } from '@wordpress/element';
import { Icon, calendar } from '@wordpress/icons';
import moment, { Moment } from 'moment';
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
	onChange: ( date: string ) => void;
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
	label,
	placeholder,
	help,
}: DateTimePickerControlProps ) => {
	const [ inputString, setInputString ] = useState( '' );
	const [ lastValidDate, setLastValidDate ] = useState< Moment | null >(
		null
	);

	useEffect( () => {
		const newDate = moment( currentDate, moment.ISO_8601, true );

		if ( newDate.isValid() ) {
			setInputString( newDate.format( dateTimeFormat ) );
		} else {
			setInputString( currentDate || '' );
		}
	}, [ currentDate, dateTimeFormat ] );

	useEffect( () => {
		const newDate = moment( inputString, dateTimeFormat );

		if ( newDate.isValid() ) {
			setLastValidDate( newDate );
		}
	}, [ inputString ] );

	return (
		<Dropdown
			position="bottom center"
			focusOnMount={ false }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<>
					<InputControl
						disabled={ disabled }
						value={ inputString }
						onChange={ setInputString }
						onBlur={ (
							event: React.FocusEvent< HTMLInputElement >
						) => {
							if ( ! isOpen ) {
								return;
							}

							const newDate = moment(
								event.target.value,
								dateTimeFormat
							);
							onChange(
								newDate.isValid()
									? newDate.toISOString()
									: event.target.value
							);

							const relatedTargetParent =
								event.relatedTarget?.closest(
									'.components-dropdown'
								);
							const currentTargetParent =
								event.currentTarget?.closest(
									'.components-dropdown'
								);

							if (
								! relatedTargetParent ||
								relatedTargetParent !== currentTargetParent
							) {
								onToggle();
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
								return;
							}

							onToggle();
						} }
						aria-expanded={ isOpen }
					/>
					{ help && <p>{ help }</p> }
				</>
			) }
			renderContent={ () => (
				<WpDateTimePicker
					currentDate={ lastValidDate?.toISOString() }
					onChange={ ( date: string ) => {
						const formattedDate =
							moment( date ).format( dateTimeFormat );
						setInputString( formattedDate );
					} }
					is12Hour={ is12Hour }
				/>
			) }
		/>
	);
};
