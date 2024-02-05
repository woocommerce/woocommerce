/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';

/**
 * Internal dependencies
 */
import NoticeBanner, { NoticeBannerProps } from '../';
const availableStatus = [ 'default', 'success', 'error', 'warning', 'info' ];

export default {
	title: 'Base Components/NoticeBanner',
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
	component: NoticeBanner,
} as Meta< NoticeBannerProps >;

const Template: StoryFn< NoticeBannerProps > = ( args ) => {
	return <NoticeBanner { ...args } />;
};

export const Default = Template.bind( {} );
Default.args = {
	children: 'This is a default notice',
	status: 'default',
	isDismissible: true,
	summary: undefined,
	className: undefined,
	spokenMessage: undefined,
	politeness: undefined,
};

export const Error = Template.bind( {} );
Error.args = {
	children: 'This is an error notice',
	status: 'error',
};

export const Warning = Template.bind( {} );
Warning.args = {
	children: 'This is a warning notice',
	status: 'warning',
};

export const Info = Template.bind( {} );
Info.args = {
	children: 'This is an informational notice',
	status: 'info',
};

export const Success = Template.bind( {} );
Success.args = {
	children: 'This is a success notice',
	status: 'success',
};

export const ErrorSummary = Template.bind( {} );
ErrorSummary.args = {
	summary: 'Please fix the following errors',
	children: (
		<ul>
			<li>This is an error notice</li>
			<li>This is another error notice</li>
		</ul>
	),
	status: 'error',
};
