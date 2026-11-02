/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { StoryFn } from '@storybook/react-webpack5';

/**
 * Internal dependencies
 */
import AnalyticsError from '../';

const Template: StoryFn = ( args ) => <AnalyticsError { ...args } />;

export const Basic = Template.bind( {} );

export default {
	title: 'Components/analytics/AnalyticsError',
	component: AnalyticsError,
};
