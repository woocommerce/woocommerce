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

export default {
	title: 'WooCommerce Admin/components/DateTimePicker',
	component: DateTimePicker,
};
