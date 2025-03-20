/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Story } from '@storybook/react';

/**
 * Internal dependencies
 */
import AnalyticsError from '../';

const Template: Story = ( args ) => <AnalyticsError { ...args } />;

export const Basic = Template.bind( {} );

export default {
	title: 'Components/analytics/AnalyticsError',
	component: AnalyticsError,
};
