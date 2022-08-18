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

export const Basic: React.FC = () => {
	return (
		<DateTimePickerControl
			// eslint-disable-next-line no-console
			onChange={ ( date ) => console.log( 'selected date: ' + date ) }
		/>
	);
};

export const Disabled: React.FC = () => {
	return <DateTimePickerControl disabled onChange={ () => null } />;
};

export const DateFormat: React.FC = () => {
	return (
		<DateTimePickerControl
			onChange={ () => null }
			dateTimeFormat="DD.MM.YYYY"
		/>
	);
};

export const ControlledDate: React.FC = () => {
	const [ currentDate, setCurrentDate ] = useState(
		new Date().toISOString()
	);

	return (
		<>
			<Button
				onClick={ () => setCurrentDate( new Date().toISOString() ) }
			>
				Reset to today
			</Button>
			<DateTimePickerControl
				onChange={ () => null }
				currentDate={ currentDate }
			/>
		</>
	);
};

export const TwentyFourHour: React.FC = () => {
	return <DateTimePickerControl is12Hour={ false } onChange={ () => null } />;
};

export default {
	title: 'WooCommerce Admin/components/DateTimePickerControl',
	component: DateTimePickerControl,
};
