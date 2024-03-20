/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { NoticeBannerProps } from '@woocommerce/base-components/notice-banner';
/**
 * Internal dependencies
 */
import StoreNotice from '../';

export default {
	title: 'External Components/StoreNotice',
	argTypes: {
		status: {
			control: 'radio',
			options: [ 'default', 'success', 'error', 'warning', 'info' ],
			description:
				'Status determines the color of the notice and the icon.',
		},
		isDismissible: {
			control: 'boolean',
			description:
				'Determines whether the notice can be dismissed by the user. When set to `true`, a close icon will be displayed on the banner.',
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
				'Determines the level of politeness for the notice for assistive technology. This will be `polite` for all `status` values except `error` when it will be `assertive`.',
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
	component: StoreNotice,
} as Meta< NoticeBannerProps >;

const Template: StoryFn< NoticeBannerProps > = ( { children, ...args } ) => {
	return <StoreNotice { ...args }>{ children }</StoreNotice>;
};

export const Default = Template.bind( {} );
Default.args = {
	children: 'This is a default notice',
	status: 'default',
	isDismissible: true,
	politeness: 'polite',
};

export const Error = Template.bind( {} );
Error.args = {
	children: 'This is an error notice',
	status: 'error',
	politeness: 'assertive',
};

export const Warning = Template.bind( {} );
Warning.args = {
	children: 'This is a warning notice',
	status: 'warning',
	politeness: 'polite',
};

export const Info = Template.bind( {} );
Info.args = {
	children: 'This is an informational notice',
	status: 'info',
	politeness: 'polite',
};

export const Success = Template.bind( {} );
Success.args = {
	children: 'This is a success notice',
	status: 'success',
	politeness: 'polite',
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
	politeness: 'assertive',
};
