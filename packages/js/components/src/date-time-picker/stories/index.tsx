/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DateTimePicker } from '../';

export const Basic: React.FC = () => {
	return (
		<DateTimePicker
			// eslint-disable-next-line no-alert
			onChange={ ( date ) => alert( 'selected date: ' + date ) }
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

export const TwentyFourHour: React.FC = () => {
	return <DateTimePicker is12Hour={ false } onChange={ () => null } />;
};

export default {
	title: 'WooCommerce Admin/components/DateTimePicker',
	component: DateTimePicker,
};
