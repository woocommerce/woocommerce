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

export type DateTimeProps = {
	onChange: ( date: string ) => void;
	dateTimeFormat?: string;
	disabled?: boolean;
	initialDate?: string;
	is12Hour?: boolean;
} & React.HTMLAttributes< HTMLDivElement >;

export const DateTimePicker: React.FC< DateTimeProps > = ( {
	onChange,
	is12Hour = true,
	dateTimeFormat = is12Hour ? 'MM/DD/YYYY h:mm a' : 'MM/DD/YYYY H:MM',
	disabled = false,
	initialDate = new Date().toISOString(),
}: DateTimeProps ) => {
	const [ pickerDate, setPickerDate ] = useState( initialDate );
	const [ inputDate, setInputDate ] = useState(
		moment( initialDate ).format( dateTimeFormat )
	);
	const [ inputError, setInputError ] = useState( '' );

	useEffect( () => {
		if ( ! moment( inputDate ).isValid() ) {
			setInputError( __( 'Invalid date', 'woocommerce' ) );
			return;
		}

		setInputError( '' );
		setPickerDate( moment( inputDate ).toISOString() );
	}, [ inputDate ] );

	useEffect( () => {
		setInputDate( moment( pickerDate ).format( dateTimeFormat ) );
	}, [ pickerDate, dateTimeFormat ] );

	return (
		<Dropdown
			position="bottom center"
			focusOnMount={ false }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<DateInput
					disabled={ disabled }
					value={ inputDate }
					onChange={ ( { target } ) => {
						setInputDate( target.value );
					} }
					onBlur={ ( event ) => {
						if ( ! isOpen ) {
							return;
						}

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
					initialDate={ pickerDate }
					onChange={ ( newDate ) => {
						setPickerDate( newDate );
						onChange( newDate );
					} }
					is12Hour={ is12Hour }
				/>
			) }
		/>
	);
};
