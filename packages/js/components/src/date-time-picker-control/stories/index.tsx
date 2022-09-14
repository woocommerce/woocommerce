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

function ControlledContainer( { children, ...props } ) {
	const [ controlledDate, setControlledDate ] = useState(
		new Date().toISOString()
	);

	return (
		<div { ...props }>
			{ children( controlledDate, setControlledDate ) }
			<Button
				onClick={ () => setControlledDate( new Date().toISOString() ) }
			>
				Reset to now
			</Button>
		</div>
	);
}

export const Controlled = ( args ) => <DateTimePickerControl { ...args } />;
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
