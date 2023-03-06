/**
 * External dependencies
 */
import React from 'react';
import { Button, Popover, SlotFillProvider } from '@wordpress/components';
import { createElement, useCallback, useState } from '@wordpress/element';

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

function ControlledDecorator( Story, props ) {
	function nowWithZeroedSeconds() {
		const now = new Date();
		now.setSeconds( 0 );
		now.setMilliseconds( 0 );
		return now;
	}

	const [ controlledDate, setControlledDate ] = useState(
		nowWithZeroedSeconds().toISOString()
	);

	const onChange = useCallback( ( newDateTimeISOString ) => {
		setControlledDate( newDateTimeISOString );
		// eslint-disable-next-line no-console
		console.log( 'onChange', newDateTimeISOString );
	}, [] );

	return (
		<div>
			<Story
				args={ {
					...props.args,
					currentDate: controlledDate,
					onChange,
				} }
			/>
			<div>
				<Button
					onClick={ () =>
						setControlledDate(
							nowWithZeroedSeconds().toISOString()
						)
					}
				>
					Reset to now
				</Button>
				<div>
					<div>
						Controlled date:
						<br /> <span>{ controlledDate }</span>
					</div>
				</div>
			</div>
		</div>
	);
}

export const Controlled = Template.bind( {} );
Controlled.args = {
	...Basic.args,
	help: "I'm controlled by a container that uses React state",
};
Controlled.decorators = [ ControlledDecorator ];

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

function PopoverSlotDecorator( Story, props ) {
	return (
		<div>
			<SlotFillProvider>
				<div>
					<Story
						args={ {
							...props.args,
						} }
					/>
				</div>
				<Popover.Slot />
			</SlotFillProvider>
		</div>
	);
}

export const WithPopoverSlot = Template.bind( {} );
WithPopoverSlot.args = {
	...Basic.args,
	label: 'Start date',
	placeholder: 'Enter the start date',
	help: 'There is a SlotFillProvider and Popover.Slot on the page',
	isDateOnlyPicker: true,
};
WithPopoverSlot.decorators = [ PopoverSlotDecorator ];
