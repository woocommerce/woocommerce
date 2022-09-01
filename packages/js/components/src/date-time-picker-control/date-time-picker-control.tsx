/**
 * External dependencies
 */
import { createElement, useState, useEffect } from '@wordpress/element';
import {
	Dropdown,
	DateTimePicker as WpDateTimePicker,
} from '@wordpress/components';
import moment from 'moment';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { default as DateInput } from '../calendar/input';

export type DateTimePickerControlProps = {
	currentDate?: string | null;
	dateTimeFormat?: string;
	disabled?: boolean;
	is12Hour?: boolean;
	onChange: ( date: string ) => void;
} & Omit< React.HTMLAttributes< HTMLDivElement >, 'onChange' >;

export const DateTimePickerControl: React.FC< DateTimePickerControlProps > = ( {
	currentDate,
	is12Hour = true,
	dateTimeFormat = is12Hour ? 'MM/DD/YYYY h:mm a' : 'MM/DD/YYYY H:MM',
	disabled = false,
	onChange,
}: DateTimePickerControlProps ) => {
	const [ dateTime, setDateTime ] = useState(
		moment( currentDate || new Date().toISOString() )
	);
	const [ inputString, setInputString ] = useState(
		dateTime.format( dateTimeFormat )
	);
	const [ inputError, setInputError ] = useState( '' );

	useEffect( () => {
		setDateTime( moment( currentDate ) );
	}, [ currentDate ] );

	useEffect( () => {
		if ( ! moment( dateTime ).isValid() ) {
			setInputError( __( 'Invalid date', 'woocommerce' ) );
			return;
		}

		setInputString( dateTime.format( dateTimeFormat ) );
		setInputError( '' );
		onChange( dateTime.toISOString() );
	}, [ dateTime ] );

	return (
		<Dropdown
			position="bottom center"
			focusOnMount={ false }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<DateInput
					disabled={ disabled }
					value={ inputString }
					onChange={ ( {
						target,
					}: React.ChangeEvent< HTMLInputElement > ) =>
						setInputString( target.value )
					}
					onBlur={ (
						event: React.FocusEvent< HTMLInputElement >
					) => {
						if ( ! isOpen ) {
							return;
						}

						setDateTime(
							moment( event.target.value, dateTimeFormat )
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
					dateFormat={ dateTimeFormat }
					label={ __( 'Choose a date', 'woocommerce' ) }
					error={ inputError }
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
					errorPosition="top center"
				/>
			) }
			renderContent={ () => (
				<WpDateTimePicker
					currentDate={ dateTime.toISOString() }
					onChange={ ( newDate ) => {
						setDateTime( moment( newDate ) );
					} }
					is12Hour={ is12Hour }
				/>
			) }
		/>
	);
};
