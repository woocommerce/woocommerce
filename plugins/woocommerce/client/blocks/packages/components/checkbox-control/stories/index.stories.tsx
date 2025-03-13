/**
 * External dependencies
 */
import type { Story, Meta } from '@storybook/react';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CheckboxControl, { CheckboxControlProps } from '..';

export default {
	title: 'External Components/CheckboxControl',
	component: CheckboxControl,
	args: {
		instanceId: 'my-checkbox-id',
		label: 'Check me out',
		checked: false,
	},
} as Meta< CheckboxControlProps >;

const Template: Story< CheckboxControlProps > = ( args ) => {
	const [ checked, setChecked ] = useState( args.checked );
	useEffect( () => {
		setChecked( args.checked );
	}, [ args.checked ] );
	return (
		<CheckboxControl
			{ ...args }
			onChange={ ( value ) => setChecked( value ) }
			checked={ checked }
		/>
	);
};

export const Default = Template.bind( {} );
Default.args = {};
