// ErrorBoundary.stories.tsx

/**
 * External dependencies
 */
import React, { createElement } from '@wordpress/element';
import { Meta, Story } from '@storybook/react';
/**
 * Internal dependencies
 */
import { ErrorBoundary, ErrorBoundaryProps } from '../';

const ChildComponent = () => {
	throw new Error( 'This is a test error' );
};

const Template: Story< ErrorBoundaryProps > = ( args ) => (
	<ErrorBoundary { ...args }>
		<ChildComponent />
	</ErrorBoundary>
);

export const Default = Template.bind( {} );
Default.args = {};

export const CustomErrorMessage = Template.bind( {} );
CustomErrorMessage.args = {
	errorMessage: 'Custom error message',
};

export const WithoutActionButton = Template.bind( {} );
WithoutActionButton.args = {
	showActionButton: false,
};

export const CustomActionLabel = Template.bind( {} );
CustomActionLabel.args = {
	actionLabel: 'Retry',
};

export const CustomActionCallback = Template.bind( {} );
CustomActionCallback.args = {
	actionCallback: () => {
		// eslint-disable-next-line no-alert
		window.alert( 'Custom action callback triggered' );
	},
};

export default {
	title: 'WooCommerce Admin/experimental/Error Boundary',
	component: ErrorBoundary,
	argTypes: {
		errorMessage: {
			control: 'text',
			defaultValue: 'Oops, something went wrong. Please try again',
		},
		showActionButton: {
			control: 'boolean',
			defaultValue: true,
		},
		actionLabel: {
			control: 'text',
			defaultValue: 'Reload',
		},
		actionCallback: {
			action: 'clicked',
		},
	},
} as Meta;
