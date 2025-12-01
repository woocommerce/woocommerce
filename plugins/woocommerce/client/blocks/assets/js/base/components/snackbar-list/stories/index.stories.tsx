/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import SnackbarList, { SnackbarListProps } from '../';

export default {
	title: 'Base Components/SnackbarList',
	args: {
		notices: [
			{
				id: '1',
				content: 'This is a snackbar notice.',
				status: 'success',
				isDismissible: true,
			},
		],
		className: undefined,
		onRemove: () => void 0,
	},
	argTypes: {
		className: {
			description: 'Additional class name to give to the notice.',
			control: 'text',
		},
		notices: {
			description:
				'A list of notices to display as snackbars. Each notice must have an `id` and `content` prop.',
			disable: true,
		},
		onRemove: {
			description:
				'Function called when dismissing the notice. When the close icon is clicked or the Escape key is pressed, this function will be called. This is also called when the notice times out after 10000ms.',
			disable: true,
		},
	},
	component: SnackbarList,
} as Meta< SnackbarListProps >;

const Template: StoryFn< SnackbarListProps > = ( args ) => {
	return <SnackbarList { ...args } />;
};

export const Default = Template.bind( {} );
Default.args = {
	notices: [
		{
			id: '1',
			content: 'This is a snackbar notice.',
			status: 'default',
			isDismissible: true,
		},
		{
			id: '2',
			content: 'This is an informational snackbar notice.',
			status: 'info',
			isDismissible: true,
		},
		{
			id: '3',
			content: 'This is a snackbar error notice.',
			status: 'error',
			isDismissible: true,
		},
		{
			id: '4',
			content: 'This is a snackbar warning notice.',
			status: 'warning',
			isDismissible: true,
		},
		{
			id: '5',
			content: 'This is a snackbar success notice.',
			status: 'success',
			isDismissible: true,
		},
	],
	className: undefined,
	onRemove: () => void 0,
};
