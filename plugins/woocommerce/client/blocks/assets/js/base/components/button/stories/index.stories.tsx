/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react-webpack5';

/**
 * Internal dependencies
 */
import Button, { ButtonProps } from '..';
const availableTypes = [ 'button', 'input', 'submit' ];

export default {
	title: 'External Components/Button',
	argTypes: {
		children: {
			control: 'text',
		},
		type: {
			control: 'radio',
			options: availableTypes,
		},
	},
	component: Button,
} as Meta< ButtonProps >;

const Template: StoryFn< ButtonProps > = ( args ) => {
	return <Button { ...args } />;
};

export const Default = Template.bind( {} );
Default.args = {
	children: 'Buy Now',
	disabled: false,
	type: 'button',
};

export const Disabled = Template.bind( {} );
Disabled.args = {
	...Default.args,
	disabled: true,
};

export const Loading = Template.bind( {} );
Loading.args = {
	...Default.args,
	disabled: true,
};
