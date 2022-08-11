/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import {
	Dropdown,
	DateTimePicker as WpDatePicker,
} from '@wordpress/components';
import moment from 'moment';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { default as DateInput } from '../calendar/input';

export type DateTimeProps = {
	dateTimeFormat: string;
	disabled: boolean;
} & React.HTMLAttributes< HTMLDivElement >;

export const DateTimePicker: React.FC< DateTimeProps > = ( {
	dateTimeFormat,
	disabled = false,
	...props
}: DateTimeProps ) => {
	const [ pickerDate, setPickerDate ] = useState( new Date().toISOString() );
	const [ error, setError ] = useState( null );

	return (
		<Dropdown
			position="bottom center"
			focusOnMount={ false }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<DateInput
					disabled={ disabled }
					value={ moment( pickerDate ).format( dateTimeFormat ) }
					onChange={ () => console.debug( 'onChange()' ) }
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
					error={ error }
					describedBy={ sprintf(
						__(
							'Date input describing a selected date in format %s',
							'woocommerce'
						),
						dateTimeFormat
					) }
					onFocus={ () => {
						if ( ! isOpen ) {
							onToggle();
						}
					} }
					aria-expanded={ isOpen }
					errorPosition="top center"
					{ ...props }
				/>
			) }
			renderContent={ ( { onToggle } ) => (
				<WpDatePicker
					currentDate={ pickerDate }
					onChange={ ( newDate ) => setPickerDate( newDate ) }
					is12Hour={ true }
				/>
			) }
		/>
	);
};
