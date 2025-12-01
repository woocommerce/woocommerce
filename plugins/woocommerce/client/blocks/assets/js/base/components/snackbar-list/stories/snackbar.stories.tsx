/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import Snackbar, { SnackbarProps } from '../snackbar';
const availableStatus = [ 'default', 'success', 'error', 'warning', 'info' ];

export default {
	title: 'Base Components/SnackbarList/Snackbar',
	argTypes: {
		status: {
			control: 'radio',
			options: availableStatus,
			description:
				'Status determines the color of the notice and the icon.',
		},
		isDismissible: {
			control: 'boolean',
			description:
				'Determines whether the notice can be dismissed by the user. When set to true, a close icon will be displayed on the banner.',
		},
		summary: {
			description:
				'Optional summary text shown above notice content, used when several notices are listed together.',
			control: 'text',
		},
		className: {
			description: 'Additional class name to give to the notice.',
			control: 'text',
		},
		spokenMessage: {
			description:
				'Optionally provided to change the spoken message for assistive technology. If not provided, the `children` prop will be used as the spoken message.',
			control: 'text',
		},
		politeness: {
			control: 'radio',
			options: [ 'polite', 'assertive' ],
			description:
				'Determines the level of politeness for the notice for assistive technology.',
		},
		children: {
			description:
				'The displayed message of a notice. Also used as the spoken message for assistive technology, unless `spokenMessage` is provided as an alternative message.',
			disable: true,
		},
		onRemove: {
			description:
				'Function called when dismissing the notice. When the close icon is clicked or the Escape key is pressed, this function will be called.',
			disable: true,
		},
	},
	component: Snackbar,
} as Meta< SnackbarProps >;

const Template: StoryFn< SnackbarProps > = ( args ) => {
	return <Snackbar { ...args } />;
};

export const Default = Template.bind( {} );
Default.args = {
	children: 'This is a default snackbar notice',
	status: 'default',
	isDismissible: true,
	summary: undefined,
	className: undefined,
	spokenMessage: undefined,
	politeness: undefined,
};

export const Error = Template.bind( {} );
Error.args = {
	children: 'This is an error snackbar notice',
	status: 'error',
};

export const Warning = Template.bind( {} );
Warning.args = {
	children: 'This is a warning snackbar notice',
	status: 'warning',
};

export const Info = Template.bind( {} );
Info.args = {
	children: 'This is an informational snackbar notice',
	status: 'info',
};

export const Success = Template.bind( {} );
Success.args = {
	children: 'This is a success snackbar notice',
	status: 'success',
};
