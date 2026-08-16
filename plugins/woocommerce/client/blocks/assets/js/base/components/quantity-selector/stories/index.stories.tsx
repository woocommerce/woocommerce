/**
 * External dependencies
 */
import { useArgs } from '@storybook/client-api';
import type { Story, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import QuantitySelector from '..';
import type { QuantitySelectorProps } from '../types';

export default {
	title: 'Base Components/QuantitySelector',
	component: QuantitySelector,
	args: {
		itemName: 'widgets',
		quantity: 1,
	},
} as Meta< QuantitySelectorProps >;

const Template: Story< QuantitySelectorProps > = ( args ) => {
	const [ {}, setArgs ] = useArgs();

	const onChange = ( newVal: number ) => {
		args.onChange?.( newVal );
		setArgs( { quantity: newVal } );
	};

	return <QuantitySelector { ...args } onChange={ onChange } />;
};

export const Default = Template.bind( {} );
Default.args = {};

export const NonEditable = Template.bind( {} );
NonEditable.args = {
	editable: false,
};

export const Disabled = Template.bind( {} );
Disabled.args = {
	disabled: true,
};

export const WithMinimum = Template.bind( {} );
WithMinimum.args = {
	minimum: 2,
	quantity: 2,
};

export const WithMaximum = Template.bind( {} );
WithMaximum.args = {
	maximum: 5,
	quantity: 3,
};
