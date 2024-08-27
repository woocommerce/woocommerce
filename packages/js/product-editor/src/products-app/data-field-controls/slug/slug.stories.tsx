/* eslint-disable @typescript-eslint/ban-types */
/**
 * External dependencies
 */
import type { StoryFn } from '@storybook/react';
import { createElement } from 'react';

/**
 * Internal dependencies
 */

export default {
	title: 'Data Field Controls/Slug',
	component: <h1>ciaone</h1>,
};

const Template: StoryFn< {} > = () => <h1>ciaone</h1>;

export const Default: StoryFn< {} > = Template.bind( {} );
Default.args = {};
