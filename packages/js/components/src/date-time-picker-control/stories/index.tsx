/**
 * External dependencies
 */
import React from 'react';
import { Button } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DateTimePickerControl } from '../';

export default {
	title: 'WooCommerce Admin/components/DateTimePickerControl',
	component: DateTimePickerControl,
	argTypes: {
		onChange: { action: 'onChange' },
		onBlur: { action: 'onBlur' },
	},
};

const Template = ( args ) => <DateTimePickerControl { ...args } />;

export const Basic = Template.bind( {} );
Basic.args = {
	label: 'Start date and time',
	placeholder: 'Enter the start date and time',
	help: 'Type a date and time or use the picker',
};

const customFormat = 'Y-m-d H:i';

export const CustomDateTimeFormat = Template.bind( {} );
CustomDateTimeFormat.args = {
	...Basic.args,
	help: 'Format: ' + customFormat,
	dateTimeFormat: customFormat,
};

function ControlledContainer( { children, ...props } ) {
	const [ controlledDate, setControlledDate ] = useState(
		new Date().toISOString()
	);

	return (
		<div { ...props }>
			<div>{ children( controlledDate, setControlledDate ) }</div>
			<div>
				<Button
					onClick={ () =>
						setControlledDate( new Date().toISOString() )
					}
				>
					Reset to now
				</Button>
			</div>
		</div>
	);
}

export const ReallyLongHelp = Template.bind( {} );
ReallyLongHelp.args = {
	...Basic.args,
	help: 'The help for this date time field is extremely long. Longer than the control itself should probably be.',
};

export const CustomClassName = Template.bind( {} );
CustomClassName.args = {
	...Basic.args,
	className: 'custom-class-name',
};

export const Controlled = Template.bind( {} );
Controlled.args = {
	...Basic.args,
	help: "I'm controlled by a container that uses React state",
};
Controlled.decorators = [
	( story, props ) => {
		return (
			<ControlledContainer>
				{ ( controlledDate, setControlledDate ) =>
					story( {
						args: {
							currentDate: controlledDate,
							onChange: setControlledDate,
							...props.args,
						},
					} )
				}
			</ControlledContainer>
		);
	},
];

export const ControlledDateOnly = Template.bind( {} );
ControlledDateOnly.args = {
	...Controlled.args,
	isDateOnlyPicker: true,
};
ControlledDateOnly.decorators = Controlled.decorators;

export const ControlledDateOnlyEndOfDay = Template.bind( {} );
ControlledDateOnlyEndOfDay.args = {
	...ControlledDateOnly.args,
	timeForDateOnly: 'end-of-day',
};
ControlledDateOnlyEndOfDay.decorators = Controlled.decorators;
