/**
 * External dependencies
 */
import React from 'react';
import { Button } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DateTimePicker } from '../';

export const Basic: React.FC = () => {
	return (
		<DateTimePicker
			// eslint-disable-next-line no-console
			onChange={ ( date ) => console.log( 'selected date: ' + date ) }
		/>
	);
};

export const Disabled: React.FC = () => {
	return <DateTimePicker disabled onChange={ () => null } />;
};

export const DateFormat: React.FC = () => {
	return (
		<DateTimePicker onChange={ () => null } dateTimeFormat="DD.MM.YYYY" />
	);
};

export const ControlledDate: React.FC = () => {
	const [ currentDate, setCurrentDate ] = useState( new Date().toISOString() );

	return (
		<>
			<Button onClick={ () => setCurrentDate( new Date().toISOString() )  }>Reset to today</Button>
			<DateTimePicker onChange={ () => null } currentDate={ currentDate } />
		</>
	);
};

export const TwentyFourHour: React.FC = () => {
	return <DateTimePicker is12Hour={ false } onChange={ () => null } />;
};

export default {
	title: 'WooCommerce Admin/components/DateTimePicker',
	component: DateTimePicker,
};
